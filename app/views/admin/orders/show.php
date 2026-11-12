<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Đơn <?= e($o['order_code']) ?> <span class="badge text-bg-<?= e(status_color($o['status'])) ?>"><?= e(status_label($o['status'])) ?></span></h1>
    <a href="<?= url('/admin/don-hang') ?>" class="btn btn-link btn-sm">← Danh sách</a>
</div>
<div class="row g-4">
    <div class="col-lg-8">
        <table class="table table-sm align-middle bg-white">
            <thead><tr><th>Sản phẩm</th><th class="text-end">Đơn giá</th><th class="text-center">SL</th><th class="text-end">Thành tiền</th></tr></thead>
            <?php foreach ($o['items'] as $it): ?>
                <tr><td><?= e($it['product_name']) ?></td><td class="text-end"><?= money($it['unit_price']) ?></td>
                    <td class="text-center"><?= (int)$it['quantity'] ?></td><td class="text-end"><?= money($it['unit_price'] * $it['quantity']) ?></td></tr>
            <?php endforeach; ?>
            <tr><td colspan="3" class="text-end">Tạm tính</td><td class="text-end"><?= money($o['subtotal']) ?></td></tr>
            <tr><td colspan="3" class="text-end">Giảm giá</td><td class="text-end">-<?= money($o['discount_amount']) ?></td></tr>
            <tr><td colspan="3" class="text-end">Phí vận chuyển</td><td class="text-end"><?= money($o['shipping_fee']) ?></td></tr>
            <tr class="fw-bold"><td colspan="3" class="text-end">Tổng cộng</td><td class="text-end text-danger"><?= money($o['total']) ?></td></tr>
        </table>
    </div>
    <div class="col-lg-4">
        <div class="card card-body small mb-3">
            <strong>Khách hàng</strong><?= e($o['full_name']) ?> (<?= e($o['email']) ?>)
            <strong class="mt-2">Giao tới</strong><?= e($o['receiver_name']) ?> – <?= e($o['receiver_phone']) ?><br><?= e($o['shipping_address']) ?>
            <?php if ($o['note']): ?><em>Ghi chú: <?= e($o['note']) ?></em><?php endif; ?>
            <strong class="mt-2">Thanh toán</strong><?= e(payment_label($o['payment_method'])) ?> – <?= $o['payment_status'] === 'paid' ? 'Đã thu tiền' : 'Chưa thu tiền' ?>
        </div>


        <?php if (Order::FLOW[$o['status']]): ?>
        <form method="post" action="<?= url('/admin/don-hang/' . $o['id'] . '/trang-thai') ?>" class="card card-body mb-3">
            <?= csrf_field() ?>
            <label class="form-label small">Chuyển trạng thái</label>
            <select name="status" class="form-select form-select-sm mb-2">
                <?php foreach (Order::FLOW[$o['status']] as $next): ?><option value="<?= e($next) ?>"><?= e(status_label($next)) ?></option><?php endforeach; ?>
            </select>
            <button class="btn btn-primary btn-sm" onclick="return confirm('Xác nhận đổi trạng thái đơn hàng?')">Cập nhật</button>
            <small class="text-muted mt-2">Hủy đơn sẽ tự hoàn lại tồn kho.</small>
        </form>
        <?php endif; ?>

        <?php if ($o['status'] !== 'cancelled'): ?>
        <form method="post" action="<?= url('/admin/don-hang/' . $o['id'] . '/thanh-toan') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="paid" value="<?= $o['payment_status'] === 'paid' ? '0' : '1' ?>">
            <button class="btn btn-sm btn-outline-success"><?= $o['payment_status'] === 'paid' ? 'Đánh dấu chưa thu tiền' : 'Đánh dấu đã thu tiền' ?></button>
        </form>
        <?php endif; ?>
    </div>
</div>
