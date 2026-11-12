<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Nhà cung cấp</h1>
    <a href="<?= url('/admin/nha-cung-cap/them') ?>" class="btn btn-primary btn-sm">+ Thêm nhà cung cấp</a>
</div>
<div class="table-responsive"><table class="table table-sm table-hover align-middle bg-white">
    <thead><tr><th>Tên</th><th>Liên hệ</th><th>MST</th><th class="text-center">Số hóa đơn</th><th>Trạng thái</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($suppliers as $s): ?>
        <tr>
            <td><strong><?= e($s['name']) ?></strong><div class="small text-muted"><?= e($s['address']) ?></div></td>
            <td class="small"><?= e($s['phone']) ?><br><?= e($s['email']) ?></td>
            <td><?= e($s['tax_code']) ?></td>
            <td class="text-center"><a href="<?= url('/admin/hoa-don-nhap?supplier=' . $s['id']) ?>"><?= (int)$s['invoice_count'] ?></a></td>
            <td><?= $s['is_active'] ? '<span class="badge text-bg-success">Đang hợp tác</span>' : '<span class="badge text-bg-secondary">Ngừng</span>' ?></td>
            <td class="text-end text-nowrap">
                <a href="<?= url('/admin/nha-cung-cap/' . $s['id'] . '/sua') ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                <form method="post" action="<?= url('/admin/nha-cung-cap/' . $s['id'] . '/xoa') ?>" class="d-inline" onsubmit="return confirm('Xóa nhà cung cấp này?')">
                    <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Xóa</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
