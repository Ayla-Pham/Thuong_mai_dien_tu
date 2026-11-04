<?php
/** Quản lý sản phẩm: danh sách, thêm, sửa, xóa. */
class AdminProductController extends AdminController
{
    public function index(): void
    {
        $q = trim($_GET['q'] ?? '');
        $this->view('admin/products/index', ['title' => 'Sản phẩm', 'q' => $q, 'products' => (new ProductManager())->list($q)]);
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function edit(string $id): void
    {
        $p = (new ProductManager())->find((int)$id);
        if (!$p) { flash('danger', 'Không tìm thấy sản phẩm.'); redirect('/admin/san-pham'); }
        $this->form($p);
    }

    private function form(?array $p): void
    {
        $m = new ProductManager();
        $this->view('admin/products/form', [
            'title' => $p ? 'Sửa sản phẩm' : 'Thêm sản phẩm', 'p' => $p,
            'categories' => $m->categories(), 'publishers' => $m->publishers(),
        ]);
    }

    /** POST /admin/san-pham (thêm mới) */
    public function store(): void { $this->save(null); }

    /** POST /admin/san-pham/{id} (cập nhật) */
    public function update(string $id): void { $this->save((int)$id); }

    /** Validate dữ liệu form dùng chung cho thêm và sửa, rồi lưu. */
    private function save(?int $id): void
    {
        csrf_verify();
        $m = new ProductManager();
        $back = $id ? "/admin/san-pham/$id/sua" : '/admin/san-pham/them';
        $intOrNull = fn($v) => ($v === '' || $v === null) ? null : (int)$v;

        $d = [
            'name'         => trim($_POST['name'] ?? ''),
            'slug'         => trim($_POST['slug'] ?? ''),
            'category_id'  => (int)($_POST['category_id'] ?? 0),
            'publisher_id' => $intOrNull($_POST['publisher_id'] ?? ''),
            'type'         => $_POST['type'] ?? 'book',
            'description'  => trim($_POST['description'] ?? '') ?: null,
            'price'        => (int)($_POST['price'] ?? 0),
            'sale_price'   => $intOrNull($_POST['sale_price'] ?? ''),
            'stock'        => (int)($_POST['stock'] ?? 0),
            'pages'        => $intOrNull($_POST['pages'] ?? ''),
            'publish_year' => $intOrNull($_POST['publish_year'] ?? ''),
            'is_active'    => isset($_POST['is_active']) ? 1 : 0,
            'image'        => null,
        ];
        // Giữ lại dữ liệu đã nhập nếu phải quay lại form
        $_SESSION['old'] = array_map(fn($v) => (string)$v, $_POST);

        $errors = [];
        if (mb_strlen($d['name']) < 2 || mb_strlen($d['name']) > 200)      $errors[] = 'Tên sản phẩm phải từ 2 đến 200 ký tự.';
        if (!in_array($d['type'], ['book', 'stationery'], true))            $errors[] = 'Loại sản phẩm không hợp lệ.';
        if (!in_array($d['category_id'], array_column($m->categories(), 'id'))) $errors[] = 'Vui lòng chọn danh mục.';
        if ($d['publisher_id'] !== null && !in_array($d['publisher_id'], array_column($m->publishers(), 'id'))) $errors[] = 'Nhà xuất bản không hợp lệ.';
        if ($d['price'] < 1)                                                $errors[] = 'Giá bán phải lớn hơn 0.';
        if ($d['sale_price'] !== null && ($d['sale_price'] < 1 || $d['sale_price'] >= $d['price'])) $errors[] = 'Giá khuyến mãi phải nhỏ hơn giá gốc.';
        if ($d['stock'] < 0)                                                $errors[] = 'Tồn kho không được âm.';
        if ($d['pages'] !== null && ($d['pages'] < 1 || $d['pages'] > 65000)) $errors[] = 'Số trang không hợp lệ.';
        if ($d['publish_year'] !== null && ($d['publish_year'] < 1900 || $d['publish_year'] > 2100)) $errors[] = 'Năm xuất bản không hợp lệ.';

        try {
            $d['image'] = ProductManager::upload($_FILES['image'] ?? null);
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
        if ($errors) {
            foreach ($errors as $er) flash('danger', $er);
            redirect($back);
        }

        $newId = $m->save($id, $d, $_POST['authors'] ?? '', $_POST['tags'] ?? '');
        unset($_SESSION['old']);
        flash('success', $id ? 'Đã cập nhật sản phẩm.' : 'Đã thêm sản phẩm mới.');
        redirect('/admin/san-pham');
    }

    public function delete(string $id): void
    {
        csrf_verify();
        $removed = (new ProductManager())->delete((int)$id);
        flash('success', $removed ? 'Đã xóa sản phẩm.' : 'Sản phẩm đã có trong đơn hàng nên được ẩn khỏi cửa hàng thay vì xóa.');
        redirect('/admin/san-pham');
    }
}
