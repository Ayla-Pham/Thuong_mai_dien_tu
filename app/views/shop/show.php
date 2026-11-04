<?php $hasSale = $p['sale_price'] !== null && $p['sale_price'] < $p['price']; ?>
<nav aria-label="breadcrumb"><ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="<?= url('/') ?>">Trang chủ</a></li>
    <li class="breadcrumb-item"><a href="<?= url('/san-pham?category=' . $info['category']['slug']) ?>"><?= e($info['category']['name']) ?></a></li>
    <li class="breadcrumb-item active"><?= e($p['name']) ?></li>
</ol></nav>

<div class="row g-4">
    <div class="col-md-4"><img src="<?= e(product_image($p)) ?>" class="img-fluid rounded border" alt="<?= e($p['name']) ?>"></div>
    <div class="col-md-8">
        <h1 class="h3"><?= e($p['name']) ?></h1>
        <?php if ($info['rating']['total'] > 0): ?>
            <div class="text-warning">★ <?= e($info['rating']['avg_rating']) ?> <small class="text-muted">(<?= (int)$info['rating']['total'] ?> đánh giá)</small></div>
        <?php endif; ?>
        <ul class="list-unstyled small my-3">
            <?php if ($info['authors']): ?>
                <li>Tác giả:
                    <?php foreach ($info['authors'] as $i => $a): ?><?= $i ? ', ' : '' ?><a href="<?= url('/san-pham?author=' . $a['slug']) ?>"><?= e($a['name']) ?></a><?php endforeach; ?>
                </li>
            <?php endif; ?>
            <?php if ($info['publisher']): ?><li>Nhà xuất bản: <a href="<?= url('/san-pham?publisher=' . $info['publisher']['slug']) ?>"><?= e($info['publisher']['name']) ?></a></li><?php endif; ?>
            <?php if ($p['pages']): ?><li>Số trang: <?= (int)$p['pages'] ?></li><?php endif; ?>
            <?php if ($p['publish_year']): ?><li>Năm xuất bản: <?= (int)$p['publish_year'] ?></li><?php endif; ?>
            <li>Tình trạng: <?= $p['stock'] > 0 ? '<span class="text-success">Còn ' . (int)$p['stock'] . ' sản phẩm</span>' : '<span class="text-danger">Hết hàng</span>' ?></li>
        </ul>
        <div class="fs-3 text-danger fw-bold"><?= money($p['final_price']) ?>
            <?php if ($hasSale): ?><small class="fs-6 text-muted text-decoration-line-through"><?= money($p['price']) ?></small><?php endif; ?>
        </div>
        <p class="mt-3"><?= nl2br(e($p['description'])) ?></p>
        <?php foreach ($info['tags'] as $t): ?>
            <a class="badge text-bg-secondary text-decoration-none" href="<?= url('/san-pham?q=' . urlencode($t)) ?>">#<?= e($t) ?></a>
        <?php endforeach; ?>
        <form method="post" action="<?= url('/gio-hang/them') ?>" class="mt-4 d-flex gap-2 flex-wrap">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="back" value="/san-pham/<?= e($p['slug']) ?>">
            <input type="number" name="qty" value="1" min="1" max="<?= (int)$p['stock'] ?>" class="form-control" style="width:90px" <?= $p['stock'] > 0 ? '' : 'disabled' ?>>
            <button class="btn btn-primary" <?= $p['stock'] > 0 ? '' : 'disabled' ?>>🛒 Thêm vào giỏ</button>
            <button class="btn btn-warning" name="buy_now" value="1" <?= $p['stock'] > 0 ? '' : 'disabled' ?>>Mua ngay</button>
        </form>
    </div>
</div>

<?php if ($boughtTogether): ?>
<h2 class="h5 mt-5 mb-3">Thường được mua cùng</h2>
<div class="row row-cols-2 row-cols-md-4 g-3">
    <?php foreach ($boughtTogether as $p): ?><div class="col"><?php include APP_PATH . '/views/partials/product_card.php'; ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($related): ?>
<h2 class="h5 mt-5 mb-3">Sản phẩm liên quan</h2>
<div class="row row-cols-2 row-cols-md-4 g-3">
    <?php foreach ($related as $p): ?><div class="col"><?php include APP_PATH . '/views/partials/product_card.php'; ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($info['reviews']): ?>
<h2 class="h5 mt-5 mb-3">Đánh giá</h2>
<?php foreach ($info['reviews'] as $r): ?>
    <div class="border-bottom py-2"><strong><?= e($r['full_name']) ?></strong> <span class="text-warning"><?= str_repeat('★', (int)$r['rating']) ?></span><br><?= e($r['comment']) ?></div>
<?php endforeach; endif; ?>
