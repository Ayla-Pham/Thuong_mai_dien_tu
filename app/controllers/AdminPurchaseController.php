<?php
/**
 * Quản lý hóa đơn nhập hàng từ nhà cung cấp, kèm file hóa đơn (PDF/ảnh/Word/Excel) có thể tải về.
 *
 * BẢO MẬT FILE UPLOAD:
 *  - Chỉ nhận đuôi trong whitelist + kiểm tra MIME thật bằng finfo (không tin tên file/MIME do trình duyệt gửi).
 *  - Giới hạn 5MB; đổi tên ngẫu nhiên khi lưu (chống ghi đè và chống chạy file .php).
 *  - Lưu ở thư mục storage/ NẰM NGOÀI public/ => không truy cập trực tiếp bằng URL được,
 *    chỉ tải qua controller này (đã kiểm tra quyền admin trong AdminController).
 */
class AdminPurchaseController extends AdminController
{
    private const MAX_SIZE = 5 * 1024 * 1024;
    /** đuôi file => các MIME hợp lệ */
    private const ALLOWED = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'csv'  => ['text/csv', 'text/plain', 'application/csv'],
    ];

    /**
     * POST (AJAX): đọc file XLSX/CSV người dùng vừa chọn và trả về JSON danh sách dòng hàng đã khớp sản phẩm,
     * để trình duyệt tự điền vào form. KHÔNG ghi gì vào CSDL - người dùng xem lại rồi mới bấm Lưu.
     */
    public function parseFile(): void
    {
        csrf_verify();
        header('Content-Type: application/json; charset=UTF-8');
        $f = $_FILES['invoice_file'] ?? null;
        $ext = $f ? strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) : '';
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || !in_array($ext, ['xlsx', 'csv'], true) || $f['size'] > self::MAX_SIZE) {
            exit(json_encode(['fatal' => 'Chỉ tự động đọc được file XLSX hoặc CSV (tối đa 5MB).']));
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!in_array($mime, self::ALLOWED[$ext], true)) exit(json_encode(['fatal' => 'Nội dung file không khớp định dạng ' . strtoupper($ext) . '.']));

        $db = Database::getConnection();
        $products  = $db->query("SELECT id, name, slug FROM products")->fetchAll();
        $suppliers = $db->query("SELECT id, name, tax_code FROM suppliers WHERE is_active = 1")->fetchAll();
        $res = InvoiceImporter::parse($f['tmp_name'], $ext, $products, $suppliers);
        if (isset($res['fatal'])) exit(json_encode($res, JSON_UNESCAPED_UNICODE));

        $inv = &$res['invoice'];
        $inv['generated'] = false;
        // File không ghi số hóa đơn => hệ thống tự sinh mã phiếu nhập dạng PN-yyyymmdd-NNN (NNN: số thứ tự trong ngày)
        if (!$inv['invoice_no']) {
            $date = $inv['invoice_date'] ?: date('Y-m-d');
            $st = $db->prepare("SELECT COUNT(*) FROM purchase_invoices WHERE invoice_no LIKE ?");
            $prefix = 'PN-' . str_replace('-', '', $date) . '-';
            $st->execute([$prefix . '%']);
            $inv['invoice_no'] = $prefix . str_pad((string)($st->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
            $inv['generated'] = true;
        }
        // Cảnh báo sớm nếu nhà cung cấp này đã có hóa đơn cùng số (CSDL không cho trùng)
        if ($inv['supplier_id']) {
            $st = $db->prepare("SELECT COUNT(*) FROM purchase_invoices WHERE supplier_id = ? AND invoice_no = ?");
            $st->execute([$inv['supplier_id'], $inv['invoice_no']]);
            if ($st->fetchColumn()) $res['errors'][] = 'Cảnh báo: nhà cung cấp này đã có hóa đơn số ' . $inv['invoice_no'] . '. Hãy đổi số hóa đơn trước khi lưu.';
        }
        unset($inv);
        exit(json_encode($res, JSON_UNESCAPED_UNICODE));
    }

    /** GET: tải file CSV mẫu (có BOM để Excel hiển thị đúng tiếng Việt). */
    public function template(): void
    {
        $db  = Database::getConnection();
        $sup = $db->query("SELECT name FROM suppliers WHERE is_active = 1 ORDER BY name LIMIT 1")->fetchColumn() ?: 'Tên nhà cung cấp';
        $csv = fn(string $v) => '"' . str_replace('"', '""', $v) . '"';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="mau-hoa-don-nhap.csv"');
        echo "\xEF\xBB\xBF";
        echo 'Nhà cung cấp,' . $csv($sup) . "\r\n";                      // phải trùng tên (hoặc mã số thuế) trong mục Nhà cung cấp
        echo 'Số hóa đơn,HD-0001' . "\r\n";                              // bỏ trống/xóa dòng này nếu muốn hệ thống tự sinh mã PN-...
        echo 'Ngày hóa đơn,' . date('d/m/Y') . "\r\n\r\n";
        echo "Mã SP,Tên sản phẩm,Số lượng,Giá nhập\r\n";
        foreach ($db->query("SELECT id, name FROM products ORDER BY id LIMIT 3")->fetchAll() as $p) {
            echo $p['id'] . ',"' . str_replace('"', '""', $p['name']) . '",10,50000' . "\r\n";
        }
        exit;
    }

    public function index(): void
    {
        $f = ['supplier_id' => (int)($_GET['supplier'] ?? 0), 'q' => trim($_GET['q'] ?? ''),
              'from' => $this->date($_GET['from'] ?? ''), 'to' => $this->date($_GET['to'] ?? '')];
        $invoices = (new PurchaseInvoice())->search($f);
        $sum = array_sum(array_map(fn($i) => $i['status'] === 'received' ? $i['total'] : 0, $invoices));
        $this->view('admin/purchases/index', ['title' => 'Hóa đơn nhập hàng', 'invoices' => $invoices, 'f' => $f,
                    'suppliers' => (new Supplier())->all(), 'sum' => $sum]);
    }

    public function create(): void
    {
        $this->view('admin/purchases/form', ['title' => 'Lập hóa đơn nhập hàng',
            'suppliers' => (new Supplier())->all(true), 'products' => (new PurchaseInvoice())->productOptions()]);
    }

    /** POST: lưu hóa đơn + chi tiết + cộng tồn kho + lưu file. */
    public function store(): void
    {
        csrf_verify();
        $_SESSION['old'] = array_map('strval', array_filter($_POST, 'is_string'));
        $d = ['supplier_id' => (int)($_POST['supplier_id'] ?? 0), 'created_by' => auth_user()['id'],
              'invoice_no' => trim($_POST['invoice_no'] ?? ''), 'invoice_date' => $this->date($_POST['invoice_date'] ?? ''),
              'note' => trim($_POST['note'] ?? '') ?: null, 'file_path' => null, 'file_name' => null];

        // Gom các dòng sản phẩm; bỏ dòng trống, gộp dòng trùng sản phẩm
        $items = [];
        foreach ((array)($_POST['product_id'] ?? []) as $k => $pid) {
            $pid = (int)$pid; $qty = (int)($_POST['qty'][$k] ?? 0); $cost = (int)($_POST['cost'][$k] ?? 0);
            if ($pid <= 0 && $qty <= 0) continue;
            $items[$pid] = ['product_id' => $pid, 'quantity' => ($items[$pid]['quantity'] ?? 0) + $qty, 'unit_cost' => $cost];
        }

        $errors = [];
        $sup = (new Supplier())->find($d['supplier_id']);
        if (!$sup || !$sup['is_active'])                          $errors[] = 'Vui lòng chọn nhà cung cấp hợp lệ.';
        if ($d['invoice_no'] === '' || mb_strlen($d['invoice_no']) > 50) $errors[] = 'Số hóa đơn là bắt buộc (tối đa 50 ký tự).';
        if (!$d['invoice_date'] || $d['invoice_date'] > date('Y-m-d')) $errors[] = 'Ngày hóa đơn không hợp lệ (không được ở tương lai).';
        if (!$items)                                              $errors[] = 'Hóa đơn cần ít nhất một sản phẩm.';
        foreach ($items as $i) {
            if ($i['product_id'] <= 0 || $i['quantity'] < 1 || $i['quantity'] > 100000 || $i['unit_cost'] < 0) {
                $errors[] = 'Mỗi dòng cần chọn sản phẩm, số lượng ≥ 1 và giá nhập ≥ 0.'; break;
            }
        }
        $stored = null;
        if (!$errors) {
            [$stored, $fileErr] = $this->storeUpload($_FILES['invoice_file'] ?? null);
            if ($fileErr) $errors[] = $fileErr;
            elseif ($stored) { $d['file_path'] = $stored['path']; $d['file_name'] = $stored['name']; }
        }
        if (!$errors) {
            $err = (new PurchaseInvoice())->create($d, array_values($items));
            if ($err) $errors[] = $err;
        }
        if ($errors) {
            if ($stored) @unlink($this->dir() . '/' . $stored['path']);   // lưu DB lỗi thì xóa file vừa upload
            foreach ($errors as $er) flash('danger', $er);
            $_SESSION['old_items'] = array_values($items);
            redirect('/admin/hoa-don-nhap/them');
        }
        unset($_SESSION['old'], $_SESSION['old_items']);
        flash('success', 'Đã lập hóa đơn nhập và cập nhật tồn kho.');
        redirect('/admin/hoa-don-nhap');
    }

    public function show(string $id): void
    {
        $inv = $this->findOrBack($id);
        $this->view('admin/purchases/show', ['title' => 'Hóa đơn nhập ' . $inv['invoice_no'], 'inv' => $inv,
                    'items' => (new PurchaseInvoice())->items($inv['id'])]);
    }

    /** GET: tải file hóa đơn (chỉ admin). */
    public function download(string $id): void
    {
        $inv  = $this->findOrBack($id);
        $file = $inv['file_path'] ? $this->dir() . '/' . basename($inv['file_path']) : null;
        if (!$file || !is_file($file)) { flash('warning', 'Hóa đơn này chưa có file đính kèm.'); redirect('/admin/hoa-don-nhap/' . $inv['id']); }

        $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $safe = preg_replace('/[^\p{L}\p{N}._ -]/u', '_', $inv['file_name'] ?: ('hoa-don.' . $ext));
        header('Content-Type: ' . (self::ALLOWED[$ext][0] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($file));
        header("Content-Disposition: attachment; filename=\"" . addslashes($safe) . "\"; filename*=UTF-8''" . rawurlencode($safe));
        header('X-Content-Type-Options: nosniff');
        readfile($file);
        exit;
    }

    /** POST: đính kèm / thay file cho hóa đơn đã lập. */
    public function uploadFile(string $id): void
    {
        csrf_verify();
        $inv = $this->findOrBack($id);
        [$stored, $err] = $this->storeUpload($_FILES['invoice_file'] ?? null);
        if ($err || !$stored) { flash('danger', $err ?: 'Vui lòng chọn file.'); redirect('/admin/hoa-don-nhap/' . $inv['id']); }
        (new PurchaseInvoice())->setFile($inv['id'], $stored['path'], $stored['name']);
        if ($inv['file_path']) @unlink($this->dir() . '/' . basename($inv['file_path']));   // xóa file cũ
        flash('success', 'Đã cập nhật file hóa đơn.');
        redirect('/admin/hoa-don-nhap/' . $inv['id']);
    }

    /** POST: hủy hóa đơn (trừ lại tồn kho). */
    public function cancel(string $id): void
    {
        csrf_verify();
        $err = (new PurchaseInvoice())->cancel((int)$id);
        $err ? flash('danger', $err) : flash('success', 'Đã hủy hóa đơn và trừ lại tồn kho.');
        redirect('/admin/hoa-don-nhap/' . (int)$id);
    }

    /* ----------------------------- hàm phụ ----------------------------- */

    private function dir(): string { return ROOT_PATH . '/storage/invoices'; }

    private function date(string $s): ?string { return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) && strtotime($s) ? $s : null; }

    private function findOrBack(string $id): array
    {
        $inv = (new PurchaseInvoice())->find((int)$id);
        if (!$inv) { flash('danger', 'Không tìm thấy hóa đơn.'); redirect('/admin/hoa-don-nhap'); }
        return $inv;
    }

    /**
     * Kiểm tra và lưu file upload.
     * @return array [['path'=>tên lưu, 'name'=>tên gốc]|null, lỗi|null]
     */
    private function storeUpload(?array $f): array
    {
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) return [null, null];          // không bắt buộc đính kèm file
        if ($f['error'] !== UPLOAD_ERR_OK)              return [null, 'Upload file thất bại (mã lỗi ' . $f['error'] . ').'];
        if ($f['size'] > self::MAX_SIZE)                return [null, 'File quá lớn (tối đa 5MB).'];

        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED[$ext]))                return [null, 'Chỉ nhận file PDF, JPG, PNG, DOCX, XLSX.'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);               // MIME thật dựa trên nội dung file
        if (!in_array($mime, self::ALLOWED[$ext], true)) return [null, 'Nội dung file không khớp với định dạng ' . strtoupper($ext) . '.'];

        if (!is_dir($this->dir())) mkdir($this->dir(), 0775, true);
        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $this->dir() . '/' . $stored)) return [null, 'Không lưu được file lên máy chủ.'];
        return [['path' => $stored, 'name' => mb_substr(basename($f['name']), 0, 150)], null];
    }
}
