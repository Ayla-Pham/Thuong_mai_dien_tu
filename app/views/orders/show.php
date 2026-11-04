<?php
// Thanh tiến trình theo dõi trạng thái đơn
$steps = ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao', 'completed' => 'Hoàn thành'];
$idx = array_search($o['status'], array_keys($steps), true);
?>
<h1 class="h4 mb-1">Đơn hàng <?= e($o['order_code']) ?></h1>
<p class="text-muted small">Đặt lúc <?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></p>

<?php if ($o['status'] === 'cancelled'): ?>
    <div class="alert alert-secondary">Đơn hàng đã bị hủy.</div>
<?php else: ?>
    <div class="d-flex text-center mb-4">
        <?php $i = 0; foreach ($steps as $k => $label): ?>
            <div class="flex-fill"><span class="badge rounded-pill text-bg-<?= $i <= $idx ? 'success' : 'light border' ?> px-3 py-2"><?= e($label) ?></span></div>
        <?php $i++; endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($o['payment_method'] === 'bank_transfer' && $o['payment_status'] === 'unpaid' && $o['status'] !== 'cancelled'): ?>
    <div class="alert alert-warning">
        <strong>Thông tin chuyển khoản:</strong> Ngân hàng ABC – STK <strong>0123456789</strong> – Chủ TK: CONG TY BOOKSTORE<br>
        Số tiền: <strong><?= money($o['total']) ?></strong> – Nội dung: <strong><?= e($o['order_code']) ?></strong>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <table class="table align-middle">
            <thead><tr><th>Sản phẩm</th><th class="text-end">Đơn giá</th><th class="text-center">SL</th><th class="text-end">Thành tiền</th></tr></thead>
            <tbody>
            <?php foreach ($o['items'] as $it): ?>
                <tr>
                    <td><a href="<?= url('/san-pham/' . $it['slug']) ?>" class="text-decoration-none"><?= e($it['product_name']) ?></a></td>
                    <td class="text-end"><?= money($it['unit_price']) ?></td>
                    <td class="text-center"><?= (int)$it['quantity'] ?></td>
                    <td class="text-end"><?= money($it['unit_price'] * $it['quantity']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <dl class="row justify-content-end">
            <dt class="col-6 col-md-4 fw-normal">Tạm tính</dt><dd class="col-6 col-md-3 text-end"><?= money($o['subtotal']) ?></dd>
            <dt class="col-6 col-md-4 fw-normal">Giảm giá</dt><dd class="col-6 col-md-3 text-end">-<?= money($o['discount_amount']) ?></dd>
            <dt class="col-6 col-md-4 fw-normal">Phí vận chuyển</dt><dd class="col-6 col-md-3 text-end"><?= money($o['shipping_fee']) ?></dd>
            <dt class="col-6 col-md-4">Tổng cộng</dt><dd class="col-6 col-md-3 text-end fw-bold text-danger"><?= money($o['total']) ?></dd>
        </dl>
    </div>
    <div class="col-lg-4">
        <div class="card card-body small">
            <strong>Giao tới</strong>
            <?= e($o['receiver_name']) ?> – <?= e($o['receiver_phone']) ?><br><?= e($o['shipping_address']) ?>
            <?php if ($o['note']): ?><em class="text-muted">Ghi chú: <?= e($o['note']) ?></em><?php endif; ?>
            <hr class="my-2">
            <strong>Thanh toán</strong>
            <?= e(payment_label($o['payment_method'])) ?> – <?= ['paid' => 'Đã thanh toán', 'failed' => 'Thanh toán thất bại'][$o['payment_status']] ?? 'Chưa thanh toán' ?>
            <hr class="my-2">
            <strong>Trạng thái</strong> <span class="badge text-bg-<?= e(status_color($o['status'])) ?>"><?= e(status_label($o['status'])) ?></span>
        </div>
        <?php if ($o['payment_method'] === 'vnpay' && $o['payment_status'] !== 'paid' && $o['status'] === 'pending' && VnPay::enabled()): ?>
            <form method="post" action="<?= url('/don-hang/' . $o['order_code'] . '/thanh-toan-lai') ?>" class="mt-3">
                <?= csrf_field() ?><button class="btn btn-primary btn-sm">💳 Thanh toán lại với VNPay</button>
            </form>
        <?php endif; ?>
        <?php if ($o['status'] === 'pending'): ?>
            <form method="post" action="<?= url('/don-hang/' . $o['order_code'] . '/huy') ?>" class="mt-3" onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn này?')">
                <?= csrf_field() ?><button class="btn btn-outline-danger btn-sm">Hủy đơn hàng</button>
            </form>
        <?php endif; ?>
        <a href="<?= url('/don-hang') ?>" class="btn btn-link btn-sm mt-2">← Tất cả đơn hàng</a>
    </div>
</div>
