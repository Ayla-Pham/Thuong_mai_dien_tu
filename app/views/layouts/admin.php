<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'Quản trị') ?> | Admin BookStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
<?php
$nav = ['/admin' => '📊 Tổng quan', '/admin/san-pham' => '📚 Sản phẩm', '/admin/danh-muc' => '🗂 Danh mục',
        '/admin/don-hang' => '🧾 Đơn hàng', '/admin/khach-hang' => '👥 Khách hàng', '/admin/ma-giam-gia' => '🏷 Mã giảm giá'];
$path = '/' . trim(preg_replace('#^' . preg_quote(BASE_URL, '#') . '#', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');
?>
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <button class="navbar-toggler d-lg-none" data-bs-toggle="collapse" data-bs-target="#side"><span class="navbar-toggler-icon"></span></button>
        <a class="navbar-brand" href="<?= url('/admin') ?>">⚙️ BookStore Admin</a>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-light btn-sm" href="<?= url('/') ?>" target="_blank">Xem website</a>
            <form method="post" action="<?= url('/dang-xuat') ?>"><?= csrf_field() ?><button class="btn btn-warning btn-sm">Đăng xuất</button></form>
        </div>
    </div>
</nav>
<div class="container-fluid"><div class="row">
    <aside id="side" class="col-lg-2 collapse d-lg-block bg-white border-end py-3 min-vh-100">
        <div class="nav flex-column">
            <?php foreach ($nav as $href => $label):
                $active = $href === '/admin' ? $path === '/admin' : str_starts_with($path, $href); ?>
                <a class="nav-link <?= $active ? 'active fw-bold bg-primary-subtle' : 'text-dark' ?>" href="<?= url($href) ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </aside>
    <main class="col-lg-10 py-4 px-lg-4">
        <?php foreach (flash_pull() as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show"><?= e($f['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endforeach; ?>
        <?= $content ?>
    </main>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
