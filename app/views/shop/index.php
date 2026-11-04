<?php
/** Dựng query string giữ nguyên các bộ lọc khi chuyển trang. */
$qs = function (array $over = []) use ($filters) {
    return http_build_query(array_filter(array_merge($filters, $over), fn($v) => $v !== '' && $v !== null));
};
?>
<div class="row">
    <!-- BỘ LỌC NÂNG CAO: kết hợp thể loại + tác giả + NXB + giá -->
    <aside class="col-lg-3 mb-4">
        <form method="get" action="<?= url('/san-pham') ?>" class="card card-body">
            <h2 class="h6">Bộ lọc</h2>
            <input type="hidden" name="q" value="<?= e($filters['q']) ?>">

            <label class="form-label small mt-2">Thể loại</label>
            <select name="category" class="form-select form-select-sm">
                <option value="">Tất cả</option>
                <?php foreach ($categories as $root): ?>
                    <option value="<?= e($root['slug']) ?>" <?= $filters['category'] === $root['slug'] ? 'selected' : '' ?>><?= e($root['name']) ?></option>
                    <?php foreach ($root['children'] as $c): ?>
                        <option value="<?= e($c['slug']) ?>" <?= $filters['category'] === $c['slug'] ? 'selected' : '' ?>>&nbsp;&nbsp;– <?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </select>

            <label class="form-label small mt-2">Tác giả</label>
            <select name="author" class="form-select form-select-sm">
                <option value="">Tất cả</option>
                <?php foreach ($authors as $a): ?>
                    <option value="<?= e($a['slug']) ?>" <?= $filters['author'] === $a['slug'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label class="form-label small mt-2">Nhà xuất bản</label>
            <select name="publisher" class="form-select form-select-sm">
                <option value="">Tất cả</option>
                <?php foreach ($publishers as $pb): ?>
                    <option value="<?= e($pb['slug']) ?>" <?= $filters['publisher'] === $pb['slug'] ? 'selected' : '' ?>><?= e($pb['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label class="form-label small mt-2">Khoảng giá (đ)</label>
            <div class="input-group input-group-sm">
                <input type="number" min="0" name="min_price" class="form-control" placeholder="Từ" value="<?= e($filters['min_price']) ?>">
                <input type="number" min="0" name="max_price" class="form-control" placeholder="Đến" value="<?= e($filters['max_price']) ?>">
            </div>

            <label class="form-label small mt-2">Sắp xếp</label>
            <select name="sort" class="form-select form-select-sm">
                <?php foreach (['newest' => 'Mới nhất', 'bestseller' => 'Bán chạy', 'price_asc' => 'Giá tăng dần', 'price_desc' => 'Giá giảm dần'] as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $filters['sort'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>

            <button class="btn btn-primary btn-sm mt-3">Áp dụng</button>
            <a href="<?= url('/san-pham') ?>" class="btn btn-link btn-sm">Xóa bộ lọc</a>
        </form>
    </aside>

    <section class="col-lg-9">
        <h1 class="h5 mb-3"><?= e($title) ?> <small class="text-muted">(<?= $total ?> kết quả)</small></h1>
        <?php if (!$products): ?>
            <div class="alert alert-info">Không tìm thấy sản phẩm phù hợp.</div>
        <?php endif; ?>
        <div class="row row-cols-2 row-cols-md-3 g-3">
            <?php foreach ($products as $p): ?><div class="col"><?php include APP_PATH . '/views/partials/product_card.php'; ?></div><?php endforeach; ?>
        </div>
        <?php if ($pages > 1): ?>
        <nav class="mt-4"><ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= e($qs(['page' => $i])) ?>"><?= $i ?></a></li>
            <?php endfor; ?>
        </ul></nav>
        <?php endif; ?>
    </section>
</div>
