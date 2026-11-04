<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Mã giảm giá</h1>
    <a href="<?= url('/admin/ma-giam-gia/them') ?>" class="btn btn-primary btn-sm">+ Thêm mã</a>
</div>
<div class="table-responsive"><table class="table table-sm table-hover align-middle bg-white">
    <thead><tr><th>Mã</th><th>Giảm</th><th class="text-end">Đơn tối thiểu</th><th>Đã dùng</th><th>Thời hạn</th><th>Trạng thái</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($coupons as $c): ?>
        <tr>
            <td><strong><?= e($c['code']) ?></strong></td>
            <td><?= $c['discount_type'] === 'percent' ? (int)$c['discount_value'] . '%' . ($c['max_discount'] ? ' (tối đa ' . money($c['max_discount']) . ')' : '') : money($c['discount_value']) ?></td>
            <td class="text-end"><?= money($c['min_order']) ?></td>
            <td><?= (int)$c['used_count'] ?><?= $c['usage_limit'] !== null ? ' / ' . (int)$c['usage_limit'] : '' ?></td>
            <td class="small"><?= e($c['start_date'] ?? '…') ?> → <?= e($c['end_date'] ?? '…') ?></td>
            <td><?= $c['is_active'] ? '<span class="badge text-bg-success">Bật</span>' : '<span class="badge text-bg-secondary">Tắt</span>' ?></td>
            <td class="text-end text-nowrap">
                <a href="<?= url('/admin/ma-giam-gia/' . $c['id'] . '/sua') ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                <form method="post" action="<?= url('/admin/ma-giam-gia/' . $c['id'] . '/xoa') ?>" class="d-inline" onsubmit="return confirm('Xóa mã này?')">
                    <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Xóa</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
