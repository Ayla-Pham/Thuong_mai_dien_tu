<?php
/**
 * Lớp cơ sở cho mọi controller quản trị.
 * Constructor gọi require_admin() => PHÂN QUYỀN: chỉ role 'admin' mới vào được, mọi trang con đều được bảo vệ.
 */
abstract class AdminController extends Controller
{
    public function __construct()
    {
        require_admin();
    }

    /** Hiển thị view trong layout admin. */
    protected function view(string $view, array $data = []): void
    {
        $this->render($view, $data, 'admin');
    }
}
