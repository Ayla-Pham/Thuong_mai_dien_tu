<h1 class="h4 mb-3">Thanh toán</h1>
<form method="post" action="<?= url('/thanh-toan') ?>" class="row g-4">
    <?= csrf_field() ?>
    <div class="col-lg-7">
        <div class="card card-body mb-3">
            <h2 class="h6">Thông tin giao hàng</h2>
            <label class="form-label">Người nhận</label>
            <input name="receiver_name" class="form-control mb-2" required maxlength="100" value="<?= old('receiver_name', $user['full_name']) ?>">
            <label class="form-label">Số điện thoại</label>
            <input name="receiver_phone" class="form-control mb-2" required value="<?= old('receiver_phone', (string)$user['phone']) ?>">
            <label class="form-label">Địa chỉ</label>
            <input name="shipping_address" class="form-control mb-2" required maxlength="255" value="<?= old('shipping_address', (string)$user['address']) ?>">
            <label class="form-label">Ghi chú (không bắt buộc)</label>
            <input name="note" class="form-control" maxlength="255" value="<?= old('note') ?>">
        </div>
        <div class="card card-body">
            <h2 class="h6">Phương thức thanh toán</h2>
            <div class="form-check"><input class="form-check-input" type="radio" name="payment_method" id="pm1" value="cod" checked>
                <label class="form-check-label" for="pm1">Thanh toán khi nhận hàng (COD)</label></div>
            <div class="form-check"><input class="form-check-input" type="radio" name="payment_method" id="pm2" value="bank_transfer">
                <label class="form-check-label" for="pm2">Chuyển khoản ngân hàng</label></div>
            <?php if (VnPay::enabled()): ?>
            <div class="form-check"><input class="form-check-input" type="radio" name="payment_method" id="pm3" value="vnpay">
                <label class="form-check-label" for="pm3">Thanh toán online qua VNPay (thẻ ATM / QR / thẻ quốc tế) <span class="badge text-bg-info">Sandbox</span></label></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card card-body">
            <h2 class="h6">Đơn hàng của bạn</h2>
            <?php foreach ($items as $it): ?>
                <div class="d-flex justify-content-between small border-bottom py-1"><span><?= e($it['p']['name']) ?> × <?= $it['qty'] ?></span><span><?= money($it['line_total']) ?></span></div>
            <?php endforeach; ?>
            <dl class="row mt-3 mb-0">
                <dt class="col-6 fw-normal">Tạm tính</dt><dd class="col-6 text-end"><?= money($totals['subtotal']) ?></dd>
                <dt class="col-6 fw-normal">Giảm giá<?= $totals['coupon'] ? ' (' . e($totals['coupon']['code']) . ')' : '' ?></dt><dd class="col-6 text-end text-success">-<?= money($totals['discount']) ?></dd>
                <dt class="col-6 fw-normal">Phí vận chuyển</dt><dd class="col-6 text-end"><?= $totals['shipping'] ? money($totals['shipping']) : 'Miễn phí' ?></dd>
                <dt class="col-6">Tổng cộng</dt><dd class="col-6 text-end fs-5 fw-bold text-danger"><?= money($totals['total']) ?></dd>
            </dl>
            <button class="btn btn-primary btn-lg mt-3">Đặt hàng</button>
            <a href="<?= url('/gio-hang') ?>" class="btn btn-link btn-sm">← Quay lại giỏ hàng</a>
        </div>
    </div>
</form>
