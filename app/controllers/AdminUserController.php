<?php
/** Quản lý khách hàng: xem danh sách, khóa / mở khóa tài khoản. */
class AdminUserController extends AdminController
{
    public function index(): void
    {
        $this->view('admin/users/index', ['title' => 'Khách hàng', 'users' => (new User())->customers()]);
    }

    public function toggle(string $id): void
    {
        csrf_verify();
        (new User())->setActive((int)$id, ($_POST['active'] ?? '') === '1');
        flash('success', 'Đã cập nhật trạng thái tài khoản.');
        redirect('/admin/khach-hang');
    }
}
