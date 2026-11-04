<h1 class="h4 mb-4">Tổng quan</h1>
<div class="row g-3 mb-4">
    <?php foreach ([['Doanh thu (đơn hoàn thành)', money($s['revenue']), 'success'], ['Tổng đơn hàng', $s['orders'], 'primary'],
                    ['Đơn chờ xác nhận', $s['pending'], 'warning'], ['Khách hàng', $s['customers'], 'info'], ['Sản phẩm đang bán', $s['products'], 'secondary']] as [$label, $val, $color]): ?>
        <div class="col-6 col-lg"><div class="card border-<?= $color ?> h-100"><div class="card-body">
            <div class="small text-muted"><?= e($label) ?></div><div class="fs-4 fw-bold text-<?= $color ?>"><?= e($val) ?></div>
        </div></div></div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-6"><div class="card"><div class="card-header">Đơn hàng gần đây</div>
        <div class="table-responsive"><table class="table table-sm mb-0 align-middle">
            <?php foreach ($s['recentOrders'] as $o): ?>
                <tr><td><a href="<?= url('/admin/don-hang/' . $o['id']) ?>"><?= e($o['order_code']) ?></a></td><td><?= e($o['full_name']) ?></td>
                    <td class="text-end"><?= money($o['total']) ?></td><td><span class="badge text-bg-<?= e(status_color($o['status'])) ?>"><?= e(status_label($o['status'])) ?></span></td></tr>
            <?php endforeach; ?>
            <?php if (!$s['recentOrders']): ?><tr><td class="text-muted">Chưa có đơn hàng.</td></tr><?php endif; ?>
        </table></div></div></div>

    <div class="col-lg-6"><div class="card"><div class="card-header">⚠️ Sắp hết hàng (tồn ≤ 10)</div>
        <div class="table-responsive"><table class="table table-sm mb-0">
            <?php foreach ($s['lowStock'] as $p): ?>
                <tr><td><a href="<?= url('/admin/san-pham/' . $p['id'] . '/sua') ?>"><?= e($p['name']) ?></a></td><td class="text-end"><span class="badge text-bg-<?= $p['stock'] == 0 ? 'danger' : 'warning' ?>"><?= (int)$p['stock'] ?></span></td></tr>
            <?php endforeach; ?>
            <?php if (!$s['lowStock']): ?><tr><td class="text-muted">Tồn kho ổn định.</td></tr><?php endif; ?>
        </table></div></div></div>

    <div class="col-lg-6"><div class="card"><div class="card-header">Doanh thu 6 tháng gần nhất</div>
        <div class="table-responsive"><table class="table table-sm mb-0">
            <thead><tr><th>Tháng</th><th class="text-end">Số đơn</th><th class="text-end">Doanh thu</th></tr></thead>
            <?php foreach ($s['monthly'] as $m): ?>
                <tr><td><?= e($m['month']) ?></td><td class="text-end"><?= (int)$m['n'] ?></td><td class="text-end"><?= money($m['revenue']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$s['monthly']): ?><tr><td colspan="3" class="text-muted">Chưa có đơn hoàn thành.</td></tr><?php endif; ?>
        </table></div></div></div>

    <div class="col-lg-6"><div class="card"><div class="card-header">Top sản phẩm bán chạy</div>
        <div class="table-responsive"><table class="table table-sm mb-0">
            <?php foreach ($s['topProducts'] as $p): ?>
                <tr><td><?= e($p['name']) ?></td><td class="text-end"><?= (int)$p['sold_count'] ?> đã bán</td></tr>
            <?php endforeach; ?>
        </table></div></div></div>
</div>
