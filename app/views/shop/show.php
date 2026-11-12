<?php $hasSale = $p['sale_price'] !== null && $p['sale_price'] < $p['price'];
$pageSlug = $p['slug'];   // lưu lại vì biến $p bị các vòng lặp "sản phẩm liên quan" bên dưới ghi đè ?>
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

<h2 class="h5 mt-5 mb-3" id="danh-gia">Đánh giá sản phẩm</h2>

<?php if ($canReview): /* khách đã mua + nhận hàng: hiện form gửi/sửa đánh giá */ ?>
<form method="post" action="<?= url('/san-pham/' . $pageSlug . '/danh-gia') ?>" class="card card-body mb-4" style="max-width:600px">
    <?= csrf_field() ?>
    <div class="fw-semibold mb-2"><?= $myReview ? 'Đánh giá của bạn (có thể chỉnh sửa)' : 'Bạn đã mua sản phẩm này, hãy chia sẻ cảm nhận' ?></div>
    <div class="star-input mb-2" role="radiogroup" aria-label="Số sao">
        <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= (int)($myReview['rating'] ?? 0) === $i ? 'checked' : '' ?> required>
            <label for="star<?= $i ?>" title="<?= $i ?> sao">★</label>
        <?php endfor; ?>
    </div>
    <textarea name="comment" class="form-control mb-2" rows="3" maxlength="500" placeholder="Nhận xét của bạn (không bắt buộc)"><?= e($myReview['comment'] ?? '') ?></textarea>
    <div><button class="btn btn-primary btn-sm"><?= $myReview ? 'Cập nhật đánh giá' : 'Gửi đánh giá' ?></button>
    <?php if ($myReview): ?><button class="btn btn-outline-danger btn-sm" formaction="<?= url('/san-pham/' . $pageSlug . '/danh-gia/xoa') ?>" onclick="return confirm('Xóa đánh giá của bạn?')">Xóa</button><?php endif; ?></div>
</form>
<?php elseif (!auth_user()): ?>
    <p class="small text-muted"><a href="<?= url('/dang-nhap') ?>">Đăng nhập</a> để đánh giá sản phẩm bạn đã mua.</p>
<?php else: ?>
    <p class="small text-muted">Chỉ khách đã mua và nhận sản phẩm này mới có thể đánh giá.</p>
<?php endif; ?>

<?php foreach ($info['reviews'] as $r): ?>
    <div class="border-bottom py-2">
        <strong><?= e($r['full_name']) ?></strong> <span class="text-warning"><?= str_repeat('★', (int)$r['rating']) ?><span class="text-black-50"><?= str_repeat('★', 5 - (int)$r['rating']) ?></span></span>
        <small class="text-muted ms-2"><?= date('d/m/Y', strtotime($r['created_at'])) ?></small>
        <?php if ($r['comment']): ?><br><?= nl2br(e($r['comment'])) ?><?php endif; ?>
    </div>
<?php endforeach; ?>
<?php if (!$info['reviews']): ?><p class="text-muted small">Chưa có đánh giá nào.</p><?php endif; ?>
<style>
/* Chọn sao: các input ẩn, label ★ tô màu khi hover/được chọn. Đảo thứ tự (5→1) + row-reverse để tô các sao bên trái. */
.star-input { display:inline-flex; flex-direction:row-reverse; justify-content:flex-end; }
.star-input input { position:absolute; opacity:0; }
.star-input label { font-size:1.8rem; color:#ccc; cursor:pointer; padding:0 2px; }
.star-input input:checked ~ label, .star-input label:hover, .star-input label:hover ~ label { color:#ffc107; }
.star-input input:focus-visible + label { outline:2px solid #0d6efd; }
</style>
