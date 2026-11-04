<?php
/** Lịch sử đơn hàng, theo dõi trạng thái, hủy đơn của khách. */
class OrderController extends Controller
{
    public function index(): void
    {
        $sess = require_login();
        $this->render('orders/index', [
            'title' => 'Đơn hàng của tôi', 'description' => 'Lịch sử và trạng thái đơn hàng.',
            'orders' => (new Order())->forUser($sess['id']),
        ]);
    }

    public function show(string $code): void
    {
        $sess  = require_login();
        $order = (new Order())->findForUser($code, $sess['id']);
        if (!$order) { http_response_code(404); echo '404 - Không tìm thấy đơn hàng'; return; }
        $this->render('orders/show', ['title' => 'Đơn hàng ' . $code, 'description' => 'Chi tiết đơn hàng.', 'o' => $order]);
    }

    public function cancel(string $code): void
    {
        $sess = require_login();
        csrf_verify();
        if ((new Order())->cancel($code, $sess['id'])) flash('success', 'Đã hủy đơn hàng.');
        else flash('danger', 'Không thể hủy đơn này (chỉ hủy được đơn đang chờ xác nhận).');
        redirect('/don-hang/' . $code);
    }
}
