<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0">Sản phẩm <small class="text-muted">(<?= count($products) ?>)</small></h1>
    <form class="d-flex gap-2" method="get">
        <input name="q" class="form-control form-control-sm" placeholder="Tìm theo tên..." value="<?= e($q) ?>">
        <button class="btn btn-outline-secondary btn-sm">Tìm</button>
    </form>
    <a href="<?= url('/admin/san-pham/them') ?>" class="btn btn-primary btn-sm">+ Thêm sản phẩm</a>
</div>
<div class="table-responsive"><table class="table table-sm table-hover align-middle bg-white">
    <thead><tr><th></th><th>Tên</th><th>Danh mục</th><th class="text-end">Giá</th><th class="text-end">Tồn</th><th>Hiển thị</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
        <tr>
            <td style="width:50px"><img src="<?= e(product_image($p)) ?>" width="40" height="52" class="object-fit-cover rounded" alt=""></td>
            <td><?= e($p['name']) ?></td>
            <td class="small"><?= e($p['category_name']) ?></td>
            <td class="text-end"><?= money($p['sale_price'] ?? $p['price']) ?></td>
            <td class="text-end"><?= (int)$p['stock'] ?></td>
            <td><?= $p['is_active'] ? '<span class="badge text-bg-success">Đang bán</span>' : '<span class="badge text-bg-secondary">Ẩn</span>' ?></td>
            <td class="text-end text-nowrap">
                <a href="<?= url('/admin/san-pham/' . $p['id'] . '/sua') ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                <form method="post" action="<?= url('/admin/san-pham/' . $p['id'] . '/xoa') ?>" class="d-inline" onsubmit="return confirm('Xóa sản phẩm này?')">
                    <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Xóa</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
