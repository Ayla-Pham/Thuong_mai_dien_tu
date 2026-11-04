<?php
/** Thanh toán: nhập thông tin giao hàng, chọn phương thức, đặt hàng. */
class CheckoutController extends Controller
{
    public function index(): void
    {
        $sess = require_login();
        $cart  = new Cart();
        $items = $cart->items();
        if (!$items) { flash('warning', 'Giỏ hàng đang trống.'); redirect('/gio-hang'); }

        $this->render('checkout/index', [
            'title' => 'Thanh toán', 'description' => 'Xác nhận đơn hàng và chọn phương thức thanh toán.',
            'items' => $items, 'totals' => $cart->totals($items),
            'user'  => (new User())->findById($sess['id']),
        ]);
    }

    /** POST /thanh-toan - validate rồi gọi Order::place() (transaction). */
    public function place(): void
    {
        $sess = require_login();
        csrf_verify();
        $ship = [
            'name'    => trim($_POST['receiver_name'] ?? ''),
            'phone'   => trim($_POST['receiver_phone'] ?? ''),
            'address' => trim($_POST['shipping_address'] ?? ''),
            'note'    => trim($_POST['note'] ?? ''),
        ];
        $payment = $_POST['payment_method'] ?? '';
        $_SESSION['old'] = ['receiver_name' => $ship['name'], 'receiver_phone' => $ship['phone'], 'shipping_address' => $ship['address'], 'note' => $ship['note']];

        $errors = [];
        if (mb_strlen($ship['name']) < 2 || mb_strlen($ship['name']) > 100) $errors[] = 'Vui lòng nhập tên người nhận (2–100 ký tự).';
        if (!preg_match('/^(0|\+84)\d{9,10}$/', $ship['phone']))             $errors[] = 'Số điện thoại không hợp lệ.';
        if (mb_strlen($ship['address']) < 5 || mb_strlen($ship['address']) > 255) $errors[] = 'Vui lòng nhập địa chỉ giao hàng (5–255 ký tự).';
        if (mb_strlen($ship['note']) > 255)                                  $errors[] = 'Ghi chú tối đa 255 ký tự.';
        $allowed = VnPay::enabled() ? ['cod', 'bank_transfer', 'vnpay'] : ['cod', 'bank_transfer'];
        if (!in_array($payment, $allowed, true))                             $errors[] = 'Phương thức thanh toán không hợp lệ.';

        $cart = new Cart();
        if (!$cart->items()) { flash('warning', 'Giỏ hàng đang trống.'); redirect('/gio-hang'); }
        if ($errors) { foreach ($errors as $er) flash('danger', $er); redirect('/thanh-toan'); }

        try {
            $code = (new Order())->place($sess['id'], $_SESSION['cart'], $ship, $payment, $_SESSION['coupon'] ?? null);
        } catch (RuntimeException $e) {         // hết hàng / mã không hợp lệ: báo cho khách
            flash('danger', $e->getMessage());
            redirect('/gio-hang');
        }
        $cart->clear();
        unset($_SESSION['old']);
        if ($payment === 'vnpay') {   // đơn đã tạo (chờ thanh toán) -> chuyển sang cổng VNPay
            flash('info', 'Đơn hàng đã được tạo. Vui lòng hoàn tất thanh toán.');
            VnpayController::redirectToGateway((new Order())->findForUser($code, $sess['id']));
        }
        flash('success', 'Đặt hàng thành công! Cảm ơn bạn đã mua sắm.');
        redirect('/don-hang/' . $code);
    }
}
