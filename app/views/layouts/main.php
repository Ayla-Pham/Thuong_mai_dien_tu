<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? $config['app_name']) ?> | BookStore</title>
    <meta name="description" content="<?= e($description ?? '') ?>">
    <meta property="og:title" content="<?= e($title ?? '') ?>">
    <meta property="og:description" content="<?= e($description ?? '') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= url('/') ?>">📚 BookStore</a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
        <div id="nav" class="collapse navbar-collapse">
            <form class="d-flex mx-lg-4 flex-grow-1 my-2 my-lg-0" action="<?= url('/san-pham') ?>" method="get">
                <input class="form-control" name="q" placeholder="Tìm sách, tác giả, nhà xuất bản..." value="<?= e($_GET['q'] ?? '') ?>">
                <button class="btn btn-warning ms-2">Tìm</button>
            </form>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?= url('/san-pham') ?>">Sản phẩm</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('/gio-hang') ?>">🛒 Giỏ hàng <span class="badge bg-warning text-dark"><?= Cart::count() ?></span></a></li>
                <?php if ($u = auth_user()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('/don-hang') ?>">Đơn hàng</a></li>
                    <?php if ($u['role'] === 'admin'): ?><li class="nav-item"><a class="nav-link" href="<?= url('/admin') ?>">Quản trị</a></li><?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('/tai-khoan') ?>">👤 <?= e($u['full_name']) ?></a></li>
                    <li class="nav-item"><form method="post" action="<?= url('/dang-xuat') ?>"><?= csrf_field() ?><button class="btn btn-link nav-link">Đăng xuất</button></form></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('/dang-nhap') ?>">Đăng nhập</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url('/dang-ky') ?>">Đăng ký</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4 flex-grow-1">
    <?php foreach (flash_pull() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show"><?= e($f['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>
<footer class="bg-dark text-light py-4 mt-4">
    <div class="container small">&copy; <?= date('Y') ?> BookStore - Sách &amp; Văn phòng phẩm. Đồ án môn Thương mại điện tử.</div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require APP_PATH . "/views/partials/chat_widget.php"; ?>
</body>
</html>
