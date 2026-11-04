<?php
/** Trang tổng quan quản trị: doanh thu, đơn hàng, cảnh báo tồn kho. */
class AdminDashboardController extends AdminController
{
    public function index(): void
    {
        $this->view('admin/dashboard', ['title' => 'Tổng quan', 's' => (new Report())->dashboard()]);
    }
}
