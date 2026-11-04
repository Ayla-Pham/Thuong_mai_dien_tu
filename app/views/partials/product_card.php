<?php /** Thẻ sản phẩm dùng lại ở trang chủ, danh sách, gợi ý. Biến đầu vào: $p */
$hasSale = $p['sale_price'] !== null && $p['sale_price'] < $p['price'];
?>
<div class="card product-card h-100">
    <a href="<?= url('/san-pham/' . $p['slug']) ?>" class="ratio ratio-3x4 bg-light">
        <img src="<?= e(product_image($p)) ?>" class="card-img-top object-fit-cover" alt="<?= e($p['name']) ?>" loading="lazy">
    </a>
    <?php if ($hasSale): ?>
        <span class="badge bg-danger position-absolute top-0 start-0 m-2">-<?= round(100 - $p['sale_price'] / $p['price'] * 100) ?>%</span>
    <?php endif; ?>
    <div class="card-body d-flex flex-column">
        <a href="<?= url('/san-pham/' . $p['slug']) ?>" class="text-decoration-none text-dark fw-semibold product-name"><?= e($p['name']) ?></a>
        <small class="text-muted"><?= e($p['author_names'] ?? '') ?></small>
        <div class="mt-auto pt-2">
            <span class="text-danger fw-bold"><?= money($p['final_price']) ?></span>
            <?php if ($hasSale): ?><small class="text-muted text-decoration-line-through ms-1"><?= money($p['price']) ?></small><?php endif; ?>
        </div>
    </div>
</div>
