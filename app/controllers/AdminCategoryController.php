<?php
/** Quản lý danh mục (hỗ trợ danh mục cha - con). */
class AdminCategoryController extends AdminController
{
    public function index(): void
    {
        $this->view('admin/categories/index', ['title' => 'Danh mục', 'categories' => (new Category())->allWithCounts()]);
    }

    public function create(): void { $this->form(null); }

    public function edit(string $id): void
    {
        $c = (new Category())->find((int)$id);
        if (!$c) { flash('danger', 'Không tìm thấy danh mục.'); redirect('/admin/danh-muc'); }
        $this->form($c);
    }

    private function form(?array $c): void
    {
        $this->view('admin/categories/form', [
            'title' => $c ? 'Sửa danh mục' : 'Thêm danh mục', 'c' => $c,
            'parents' => array_filter((new Category())->allWithCounts(), fn($x) => $x['parent_id'] === null && (!$c || $x['id'] != $c['id'])),
        ]);
    }

    public function store(): void { $this->save(null); }
    public function update(string $id): void { $this->save((int)$id); }

    private function save(?int $id): void
    {
        csrf_verify();
        $name   = trim($_POST['name'] ?? '');
        $parent = ($_POST['parent_id'] ?? '') === '' ? null : (int)$_POST['parent_id'];
        $desc   = trim($_POST['description'] ?? '') ?: null;
        $_SESSION['old'] = $_POST;

        $cat = new Category();
        $back = $id ? "/admin/danh-muc/$id/sua" : '/admin/danh-muc/them';
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) { flash('danger', 'Tên danh mục phải từ 2 đến 100 ký tự.'); redirect($back); }
        if ($parent !== null && !$cat->find($parent))       { flash('danger', 'Danh mục cha không hợp lệ.'); redirect($back); }
        if ($desc !== null && mb_strlen($desc) > 255)       { flash('danger', 'Mô tả tối đa 255 ký tự.'); redirect($back); }

        $cat->save($id, $name, $parent, $desc);
        unset($_SESSION['old']);
        flash('success', $id ? 'Đã cập nhật danh mục.' : 'Đã thêm danh mục.');
        redirect('/admin/danh-muc');
    }

    public function delete(string $id): void
    {
        csrf_verify();
        if ((new Category())->delete((int)$id)) flash('success', 'Đã xóa danh mục.');
        else flash('danger', 'Không thể xóa: danh mục còn sản phẩm. Hãy chuyển sản phẩm sang danh mục khác trước.');
        redirect('/admin/danh-muc');
    }
}
