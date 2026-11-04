<h1 class="h4 mb-3">Đơn hàng</h1>
<div class="mb-3 d-flex flex-wrap gap-1">
    <a href="<?= url('/admin/don-hang') ?>" class="btn btn-sm <?= $status === '' ? 'btn-dark' : 'btn-outline-dark' ?>">Tất cả</a>
    <?php foreach (array_keys(Order::FLOW) as $st): ?>
        <a href="<?= url('/admin/don-hang?status=' . $st) ?>" class="btn btn-sm <?= $status === $st ? 'btn-dark' : 'btn-outline-dark' ?>"><?= e(status_label($st)) ?></a>
    <?php endforeach; ?>
</div>
<div class="table-responsive"><table class="table table-sm table-hover align-middle bg-white">
    <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Ngày đặt</th><th class="text-end">Tổng</th><th>Thanh toán</th><th>Trạng thái</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
        <tr>
            <td><a href="<?= url('/admin/don-hang/' . $o['id']) ?>"><?= e($o['order_code']) ?></a></td>
            <td><?= e($o['full_name']) ?><br><small class="text-muted"><?= e($o['email']) ?></small></td>
            <td class="small"><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
            <td class="text-end"><?= money($o['total']) ?></td>
            <td class="small"><?= e(payment_label($o['payment_method'])) ?> – <?= $o['payment_status'] === 'paid' ? '<span class="text-success">đã thu</span>' : 'chưa thu' ?></td>
            <td><span class="badge text-bg-<?= e(status_color($o['status'])) ?>"><?= e(status_label($o['status'])) ?></span></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$orders): ?><tr><td colspan="6" class="text-muted">Không có đơn hàng.</td></tr><?php endif; ?>
    </tbody>
</table></div>
