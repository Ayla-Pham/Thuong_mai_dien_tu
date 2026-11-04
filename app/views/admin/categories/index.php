<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Danh mục</h1>
    <a href="<?= url('/admin/danh-muc/them') ?>" class="btn btn-primary btn-sm">+ Thêm danh mục</a>
</div>
<div class="table-responsive"><table class="table table-sm table-hover align-middle bg-white">
    <thead><tr><th>Tên</th><th>Slug</th><th>Danh mục cha</th><th class="text-end">Số sản phẩm</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($categories as $c): ?>
        <tr>
            <td><?= $c['parent_id'] ? '&nbsp;&nbsp;– ' : '<strong>' ?><?= e($c['name']) ?><?= $c['parent_id'] ? '' : '</strong>' ?></td>
            <td class="small text-muted"><?= e($c['slug']) ?></td>
            <td><?= e($c['parent_name'] ?? '—') ?></td>
            <td class="text-end"><?= (int)$c['product_count'] ?></td>
            <td class="text-end text-nowrap">
                <a href="<?= url('/admin/danh-muc/' . $c['id'] . '/sua') ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                <form method="post" action="<?= url('/admin/danh-muc/' . $c['id'] . '/xoa') ?>" class="d-inline" onsubmit="return confirm('Xóa danh mục này?')">
                    <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Xóa</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
