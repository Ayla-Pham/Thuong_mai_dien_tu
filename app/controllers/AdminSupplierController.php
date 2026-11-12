<?php
/** Quản lý nhà cung cấp. */
class AdminSupplierController extends AdminController
{
    public function index(): void
    {
        $this->view('admin/suppliers/index', ['title' => 'Nhà cung cấp', 'suppliers' => (new Supplier())->all()]);
    }

    public function create(): void { $this->form(null); }

    public function edit(string $id): void
    {
        $s = (new Supplier())->find((int)$id);
        if (!$s) { flash('danger', 'Không tìm thấy nhà cung cấp.'); redirect('/admin/nha-cung-cap'); }
        $this->form($s);
    }

    private function form(?array $s): void
    {
        $this->view('admin/suppliers/form', ['title' => $s ? 'Sửa nhà cung cấp' : 'Thêm nhà cung cấp', 's' => $s]);
    }

    public function store(): void { $this->save(null); }
    public function update(string $id): void { $this->save((int)$id); }

    private function save(?int $id): void
    {
        csrf_verify();
        $t = fn($k) => trim($_POST[$k] ?? '') === '' ? null : trim($_POST[$k]);
        $d = ['name' => trim($_POST['name'] ?? ''), 'phone' => $t('phone'), 'email' => $t('email'),
              'address' => $t('address'), 'tax_code' => $t('tax_code'), 'is_active' => isset($_POST['is_active']) ? 1 : 0];
        $_SESSION['old'] = array_map('strval', array_filter($_POST, 'is_string'));
        $back = $id ? "/admin/nha-cung-cap/$id/sua" : '/admin/nha-cung-cap/them';

        $errors = [];
        if ($d['name'] === '' || mb_strlen($d['name']) > 150)                            $errors[] = 'Tên nhà cung cấp là bắt buộc (tối đa 150 ký tự).';
        if ($d['phone'] && !preg_match('/^[0-9 +().-]{8,20}$/', $d['phone']))            $errors[] = 'Số điện thoại không hợp lệ.';
        if ($d['email'] && !filter_var($d['email'], FILTER_VALIDATE_EMAIL))              $errors[] = 'Email không hợp lệ.';
        if ($d['tax_code'] && !preg_match('/^[0-9-]{8,20}$/', $d['tax_code']))           $errors[] = 'Mã số thuế không hợp lệ.';
        if ($errors) { foreach ($errors as $er) flash('danger', $er); redirect($back); }

        (new Supplier())->save($id, $d);
        unset($_SESSION['old']);
        flash('success', $id ? 'Đã cập nhật nhà cung cấp.' : 'Đã thêm nhà cung cấp.');
        redirect('/admin/nha-cung-cap');
    }

    public function delete(string $id): void
    {
        csrf_verify();
        flash('success', (new Supplier())->delete((int)$id) ? 'Đã xóa nhà cung cấp.' : 'Nhà cung cấp đã có hóa đơn nhập nên chỉ bị ngừng hợp tác, không xóa.');
        redirect('/admin/nha-cung-cap');
    }
}
