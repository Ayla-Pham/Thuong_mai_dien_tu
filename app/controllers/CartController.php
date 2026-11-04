<?php
/** Giỏ hàng: xem, thêm, sửa số lượng, xóa, áp mã giảm giá. */
class CartController extends Controller
{
    public function index(): void
    {
        $cart  = new Cart();
        $items = $cart->items();
        $this->render('cart/index', [
            'title' => 'Giỏ hàng', 'description' => 'Giỏ hàng của bạn tại BookStore.',
            'items' => $items, 'totals' => $cart->totals($items),
        ]);
    }

    /** POST /gio-hang/them */
    public function add(): void
    {
        csrf_verify();
        $err = (new Cart())->add((int)($_POST['product_id'] ?? 0), (int)($_POST['qty'] ?? 1));
        if ($err) flash('warning', $err); else flash('success', 'Đã thêm sản phẩm vào giỏ hàng.');
        redirect(!empty($_POST['buy_now']) && !$err ? '/gio-hang' : $this->backPath());
    }

    /** POST /gio-hang/cap-nhat - nhận qty[product_id] => số lượng. */
    public function update(): void
    {
        csrf_verify();
        $cart = new Cart();
        foreach ((array)($_POST['qty'] ?? []) as $id => $qty) $cart->setQty((int)$id, (int)$qty);
        $cart->items();   // cắt số lượng theo tồn kho
        flash('success', 'Đã cập nhật giỏ hàng.');
        redirect('/gio-hang');
    }

    public function remove(): void
    {
        csrf_verify();
        (new Cart())->remove((int)($_POST['product_id'] ?? 0));
        redirect('/gio-hang');
    }

    /** POST /gio-hang/ma-giam-gia - áp dụng hoặc gỡ mã. */
    public function coupon(): void
    {
        csrf_verify();
        $code = strtoupper(trim($_POST['code'] ?? ''));
        if ($code === '') { unset($_SESSION['coupon']); redirect('/gio-hang'); }

        $cart  = new Cart();
        $items = $cart->items();
        $sub   = array_sum(array_column($items, 'line_total'));
        [$discount, $err] = Coupon::evaluate((new Coupon())->findByCode($code), $sub);
        if ($err) { unset($_SESSION['coupon']); flash('danger', $err); }
        else      { $_SESSION['coupon'] = $code; flash('success', 'Áp dụng mã thành công, giảm ' . money($discount) . '.'); }
        redirect('/gio-hang');
    }

    /** Chỉ cho quay lại đường dẫn nội bộ (tránh open redirect). */
    private function backPath(): string
    {
        $back = $_POST['back'] ?? '/gio-hang';
        return (is_string($back) && preg_match('#^/[A-Za-z0-9/_\-]*$#', $back)) ? $back : '/gio-hang';
    }
}
