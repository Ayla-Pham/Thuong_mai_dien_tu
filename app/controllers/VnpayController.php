<?php
/**
 * Xử lý thanh toán VNPay:
 *  - retry(): khách bấm "Thanh toán lại" -> chuyển sang VNPay.
 *  - return(): trình duyệt khách quay về website sau khi thanh toán (hiển thị kết quả).
 *  - ipn(): VNPay gọi trực tiếp máy chủ để báo kết quả (đáng tin cậy nhất, không phụ thuộc khách có quay lại hay không).
 * Cả return() và ipn() đều cập nhật đơn qua Order::applyVnpayResult() - hàm này idempotent nên gọi nhiều lần vẫn an toàn.
 * Lưu ý khi chạy localhost: VNPay không gọi được IPN vào máy bạn, nên return() cũng cập nhật trạng thái để demo được.
 */
class VnpayController extends Controller
{
    /** POST /don-hang/{code}/thanh-toan-lai */
    public function retry(string $code): void
    {
        $sess = require_login();
        csrf_verify();
        $order = (new Order())->findForUser($code, $sess['id']);
        if (!VnPay::enabled() || !$order || $order['payment_method'] !== 'vnpay'
            || $order['payment_status'] === 'paid' || $order['status'] !== 'pending') {
            flash('danger', 'Đơn hàng này không thể thanh toán lại.');
            redirect('/don-hang');
        }
        $this->goToGateway($order);
    }

    /** GET /thanh-toan/vnpay-return */
    public function return(): void
    {
        if (!VnPay::verify($_GET)) {
            flash('danger', 'Chữ ký VNPay không hợp lệ, giao dịch bị từ chối.');
            redirect('/don-hang');
        }
        $code = VnPay::orderCodeFromRef($_GET['vnp_TxnRef'] ?? '');
        $ok   = ($_GET['vnp_ResponseCode'] ?? '') === '00' && ($_GET['vnp_TransactionStatus'] ?? '') === '00';
        $rsp  = (new Order())->applyVnpayResult($code, (int)($_GET['vnp_Amount'] ?? 0), $ok);

        if ($rsp === '04' || $rsp === '01') flash('danger', 'Thông tin giao dịch không khớp với đơn hàng.');
        elseif ($ok)                        flash('success', 'Thanh toán VNPay thành công!');
        elseif (($_GET['vnp_ResponseCode'] ?? '') === '24') flash('warning', 'Bạn đã hủy giao dịch. Có thể thanh toán lại bên dưới.');
        else                                flash('danger', 'Thanh toán không thành công. Bạn có thể thử lại.');
        redirect('/don-hang/' . $code);
    }

    /** GET /thanh-toan/vnpay-ipn - trả JSON theo đặc tả của VNPay. */
    public function ipn(): void
    {
        header('Content-Type: application/json');
        if (!VnPay::verify($_GET)) { echo json_encode(['RspCode' => '97', 'Message' => 'Invalid signature']); return; }

        $ok  = ($_GET['vnp_ResponseCode'] ?? '') === '00' && ($_GET['vnp_TransactionStatus'] ?? '') === '00';
        $rsp = (new Order())->applyVnpayResult(VnPay::orderCodeFromRef($_GET['vnp_TxnRef'] ?? ''), (int)($_GET['vnp_Amount'] ?? 0), $ok);
        $msg = ['00' => 'Confirm Success', '01' => 'Order not found', '02' => 'Order already confirmed', '04' => 'Invalid amount'][$rsp] ?? 'Unknown error';
        echo json_encode(['RspCode' => $rsp, 'Message' => $msg]);
    }

    /** Chuyển khách sang cổng VNPay (dùng chung cho đặt hàng mới và thanh toán lại). */
    public static function redirectToGateway(array $order): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if ($ip === '::1') $ip = '127.0.0.1';
        header('Location: ' . VnPay::paymentUrl($order, $ip));
        exit;
    }

    private function goToGateway(array $order): void { self::redirectToGateway($order); }
}
