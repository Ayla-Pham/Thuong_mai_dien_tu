<h1 class="h4 mb-3">Đơn hàng của tôi</h1>
<?php if (!$orders): ?>
    <div class="alert alert-info">Bạn chưa có đơn hàng nào. <a href="<?= url('/san-pham') ?>">Mua sắm ngay</a></div>
<?php else: ?>
<div class="table-responsive"><table class="table align-middle">
    <thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th class="text-end">Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
        <tr>
            <td><a href="<?= url('/don-hang/' . $o['order_code']) ?>"><?= e($o['order_code']) ?></a></td>
            <td><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
            <td class="text-end"><?= money($o['total']) ?></td>
            <td><?= e(payment_label($o['payment_method'])) ?></td>
            <td><span class="badge text-bg-<?= e(status_color($o['status'])) ?>"><?= e(status_label($o['status'])) ?></span></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
