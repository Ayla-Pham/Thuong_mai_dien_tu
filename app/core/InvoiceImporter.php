<?php
/**
 * InvoiceImporter: đọc file Excel (.xlsx) hoặc CSV của nhà cung cấp và chuyển thành các dòng hàng nhập.
 *
 * Quy ước file (có thể tải file mẫu trong trang lập hóa đơn):
 *   Dòng đầu là tiêu đề với các cột: "Mã SP" hoặc "Tên sản phẩm" | "Số lượng" | "Giá nhập"
 *   Cột sản phẩm có thể ghi: mã sản phẩm (id), slug, hoặc đúng tên sản phẩm trong hệ thống.
 *   Không có dòng tiêu đề thì hiểu theo thứ tự cột: sản phẩm, số lượng, giá nhập.
 *
 * Không dùng thư viện ngoài: .xlsx thực chất là file ZIP chứa XML nên đọc bằng ZipArchive + SimpleXML.
 */
class InvoiceImporter
{
    private const MAX_ROWS = 500;

    /**
     * @param array $products danh sách sản phẩm hệ thống: mỗi phần tử có id, name, slug
     * @return array ['items' => [...đã khớp], 'errors' => [...thông báo từng dòng lỗi]] hoặc ['fatal' => lỗi]
     */
    public static function parse(string $path, string $ext, array $products, array $suppliers = []): array
    {
        $rows = $ext === 'xlsx' ? self::readXlsx($path) : self::readCsv($path);
        if ($rows === null)    return ['fatal' => 'Không đọc được file. Hãy dùng đúng định dạng XLSX hoặc CSV.'];
        if (count($rows) > self::MAX_ROWS) return ['fatal' => 'File quá nhiều dòng (tối đa ' . self::MAX_ROWS . ').'];

        // Tìm dòng tiêu đề bảng hàng (trong 15 dòng đầu) để biết cột nào là gì
        $col = ['product' => 0, 'qty' => 1, 'cost' => 2];
        $start = 0; $hasHeader = false;
        foreach (array_slice($rows, 0, 15, true) as $i => $r) {
            $map = self::headerMap($r);
            if (isset($map['product'], $map['qty'])) { $col = $map + ['cost' => 2]; $start = $i + 1; $hasHeader = true; break; }
        }

        // Các dòng phía trên bảng hàng là thông tin chung: Nhà cung cấp / Số hóa đơn / Ngày hóa đơn
        $errors = [];
        $invoice = ['supplier_id' => null, 'supplier_name' => null, 'invoice_no' => null, 'invoice_date' => null];
        if ($hasHeader) {
            $meta = self::readMeta(array_slice($rows, 0, $start - 1));
            if (!empty($meta['supplier'])) {
                $key = slugify($meta['supplier']);
                foreach ($suppliers as $s) {
                    if (slugify($s['name']) === $key || ($s['tax_code'] && $s['tax_code'] === trim($meta['supplier']))) {
                        $invoice['supplier_id'] = (int)$s['id']; $invoice['supplier_name'] = $s['name']; break;
                    }
                }
                if (!$invoice['supplier_id']) $errors[] = 'Không tìm thấy nhà cung cấp "' . $meta['supplier'] . '" (hoặc đã ngừng hợp tác). Hãy chọn thủ công.';
            }
            $invoice['invoice_no'] = !empty($meta['invoice_no']) ? mb_substr(trim($meta['invoice_no']), 0, 50) : null;
            if (!empty($meta['date'])) {
                $invoice['invoice_date'] = self::parseDate($meta['date']);
                if (!$invoice['invoice_date']) $errors[] = 'Không hiểu ngày hóa đơn "' . $meta['date'] . '" (dùng dd/mm/yyyy hoặc yyyy-mm-dd).';
            }
        }

        // Bảng tra sản phẩm theo id / slug / tên (đã chuẩn hóa bỏ dấu, chữ thường)
        $byKey = [];
        foreach ($products as $p) {
            $byKey['id:' . $p['id']] = $p;
            $byKey['k:' . slugify($p['slug'])] = $p;
            $byKey['k:' . slugify($p['name'])] = $p;
        }

        $items = [];
        for ($i = $start; $i < count($rows); $i++) {
            $r = $rows[$i]; $line = $i + 1;
            $name = trim((string)($r[$col['product']] ?? ''));
            $qty  = self::number($r[$col['qty']] ?? '');
            $cost = self::number($r[$col['cost']] ?? '');
            if ($name === '' && $qty === 0) continue;                       // bỏ qua dòng trống

            $p = ctype_digit($name) ? ($byKey['id:' . $name] ?? null) : null;
            $p = $p ?? ($byKey['k:' . slugify($name)] ?? null);
            if (!$p)         { $errors[] = "Dòng $line: không tìm thấy sản phẩm \"$name\" trong hệ thống."; continue; }
            if ($qty < 1)    { $errors[] = "Dòng $line: số lượng của \"{$p['name']}\" không hợp lệ."; continue; }

            // Cùng sản phẩm xuất hiện nhiều dòng thì cộng dồn số lượng (giá nhập lấy của dòng sau cùng)
            $id = (int)$p['id'];
            $items[$id] = ['product_id' => $id, 'name' => $p['name'], 'quantity' => ($items[$id]['quantity'] ?? 0) + $qty, 'unit_cost' => $cost];
        }
        return ['items' => array_values($items), 'errors' => $errors, 'invoice' => $invoice];
    }

    /** Đọc các dòng thông tin chung dạng "Nhãn | Giá trị" phía trên bảng hàng. */
    private static function readMeta(array $rows): array
    {
        $meta = [];
        foreach ($rows as $r) {
            $label = slugify((string)($r[0] ?? ''));
            $value = '';
            foreach (array_slice($r, 1) as $cell) { if (trim((string)$cell) !== '') { $value = trim((string)$cell); break; } }
            if ($value === '') continue;
            if (in_array($label, ['nha-cung-cap', 'ncc', 'nha-cc'], true))                                       $meta['supplier'] ??= $value;
            elseif (in_array($label, ['so-hoa-don', 'ma-hoa-don', 'ma-hd', 'so-hd', 'hoa-don'], true))           $meta['invoice_no'] ??= $value;
            elseif (in_array($label, ['ngay-hoa-don', 'ngay-nhap', 'ngay', 'ngay-hd'], true))                     $meta['date'] ??= $value;
        }
        return $meta;
    }

    /** Chuyển dd/mm/yyyy, d-m-yyyy, yyyy-mm-dd hoặc số serial của Excel thành Y-m-d; sai thì trả null. */
    private static function parseDate(string $s): ?string
    {
        $s = trim($s);
        if (preg_match('/^\d{5}$/', $s)) return date('Y-m-d', strtotime('1899-12-30 +' . (int)$s . ' days'));   // ô ngày trong Excel lưu dạng số
        if (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#', $s, $m)) [$d, $mo, $y] = [$m[1], $m[2], $m[3]];
        elseif (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})$#', $s, $m))      [$y, $mo, $d] = [$m[1], $m[2], $m[3]];
        else return null;
        return checkdate((int)$mo, (int)$d, (int)$y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
    }

    /** Nhận diện tiêu đề cột (không phân biệt dấu/hoa thường). */
    private static function headerMap(array $row): array
    {
        $map = [];
        foreach ($row as $i => $cell) {
            $k = slugify((string)$cell);
            if (in_array($k, ['ma-sp', 'ma-san-pham', 'ma', 'id', 'san-pham', 'ten-san-pham', 'ten-sp', 'ten', 'slug'], true)) $map['product'] ??= $i;
            elseif (in_array($k, ['so-luong', 'sl', 'qty', 'quantity'], true))                                                $map['qty'] ??= $i;
            elseif (in_array($k, ['gia-nhap', 'don-gia', 'gia', 'gia-von', 'unit-cost'], true))                                $map['cost'] ??= $i;
        }
        return $map;
    }

    /** "60.000", "60,000", "60000đ" => 60000 (giữ lại chữ số). */
    private static function number($v): int
    {
        return (int)preg_replace('/\D+/', '', (string)$v);
    }

    private static function readCsv(string $path): ?array
    {
        $text = file_get_contents($path);
        if ($text === false) return null;
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);                 // bỏ BOM UTF-8 do Excel thêm vào
        if (!mb_check_encoding($text, 'UTF-8')) $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1258');
        $first = strtok($text, "\n") ?: '';
        $delim = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';   // Excel VN thường xuất dấu ;
        $rows = [];
        foreach (preg_split('/\r\n|\n|\r/', $text) as $line) {
            if (trim($line) !== '') $rows[] = str_getcsv($line, $delim, '"', '');
        }
        return $rows;
    }

    private static function readXlsx(string $path): ?array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) return null;
        libxml_use_internal_errors(true);
        try {
            // Chuỗi dùng chung (shared strings): ô văn bản trong xlsx chỉ lưu chỉ số trỏ tới bảng này
            $shared = [];
            if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false && ($sx = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET))) {
                foreach ($sx->si as $si) {
                    $t = '';
                    if (isset($si->t)) $t = (string)$si->t;
                    else foreach ($si->r as $run) $t .= (string)$run->t;
                    $shared[] = $t;
                }
            }
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheet === false) return null;
            $sx = simplexml_load_string($sheet, 'SimpleXMLElement', LIBXML_NONET);
            if (!$sx) return null;

            $rows = [];
            foreach ($sx->sheetData->row as $row) {
                $cells = [];
                foreach ($row->c as $c) {
                    // Cột tính từ tham chiếu ô, ví dụ "C5" => cột 2 (để không lệch khi có ô trống)
                    preg_match('/^([A-Z]+)/', (string)$c['r'], $m);
                    $idx = 0; foreach (str_split($m[1] ?? 'A') as $ch) $idx = $idx * 26 + (ord($ch) - 64);
                    $type = (string)$c['t'];
                    $val  = $type === 's' ? ($shared[(int)$c->v] ?? '') : ($type === 'inlineStr' ? (string)$c->is->t : (string)$c->v);
                    $cells[$idx - 1] = $val;
                }
                if ($cells) { $max = max(array_keys($cells)); $out = []; for ($i = 0; $i <= $max; $i++) $out[$i] = $cells[$i] ?? ''; $rows[] = $out; }
            }
            return $rows;
        } finally {
            $zip->close();
        }
    }
}
