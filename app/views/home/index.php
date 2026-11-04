<div class="p-4 p-md-5 mb-4 rounded-3 text-white bg-primary">
    <h1 class="display-6 fw-bold">Sách hay - Văn phòng phẩm giá tốt</h1>
    <p class="lead mb-3">Dùng mã <strong>WELCOME10</strong> giảm 10% cho đơn đầu tiên.</p>
    <a href="<?= url('/san-pham') ?>" class="btn btn-warning btn-lg">Mua sắm ngay</a>
</div>

<div class="row g-2 mb-4">
    <?php foreach ($categories as $root): foreach ($root['children'] as $c): ?>
        <div class="col-6 col-md-3"><a class="btn btn-outline-primary w-100" href="<?= url('/san-pham?category=' . $c['slug']) ?>"><?= e($c['name']) ?></a></div>
    <?php endforeach; endforeach; ?>
</div>

<h2 class="h4 mb-3">🔥 Bán chạy nhất</h2>
<div class="row row-cols-2 row-cols-md-4 g-3 mb-5">
    <?php foreach ($bestSellers as $p): ?><div class="col"><?php include APP_PATH . '/views/partials/product_card.php'; ?></div><?php endforeach; ?>
</div>

<h2 class="h4 mb-3">🆕 Mới nhất</h2>
<div class="row row-cols-2 row-cols-md-4 g-3">
    <?php foreach ($newest as $p): ?><div class="col"><?php include APP_PATH . '/views/partials/product_card.php'; ?></div><?php endforeach; ?>
</div>
