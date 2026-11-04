<?php
/** Quản lý đơn hàng: xem, đổi trạng thái, xác nhận thanh toán. */
class AdminOrderController extends AdminController
{
    public function index(): void
    {
        $status = $_GET['status'] ?? '';
        $this->view('admin/orders/index', ['title' => 'Đơn hàng', 'status' => $status, 'orders' => (new Order())->all($status)]);
    }

    public function show(string $id): void
    {
        $o = (new Order())->findById((int)$id);
        if (!$o) { flash('danger', 'Không tìm thấy đơn hàng.'); redirect('/admin/don-hang'); }
        $this->view('admin/orders/show', ['title' => 'Đơn ' . $o['order_code'], 'o' => $o]);
    }

    public function updateStatus(string $id): void
    {
        csrf_verify();
        $err = (new Order())->adminSetStatus((int)$id, $_POST['status'] ?? '');
        if ($err) flash('danger', $err); else flash('success', 'Đã cập nhật trạng thái đơn hàng.');
        redirect("/admin/don-hang/$id");
    }

    public function updatePayment(string $id): void
    {
        csrf_verify();
        (new Order())->setPaid((int)$id, ($_POST['paid'] ?? '') === '1');
        flash('success', 'Đã cập nhật trạng thái thanh toán.');
        redirect("/admin/don-hang/$id");
    }
}
