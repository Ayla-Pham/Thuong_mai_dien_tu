<div class="row justify-content-center"><div class="col-md-6 col-lg-4">
    <h1 class="h4 mb-3">Đăng nhập</h1>
    <form method="post" action="<?= url('/dang-nhap') ?>" class="card card-body">
        <?= csrf_field() ?>
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control mb-2" required value="<?= old('email') ?>">
        <label class="form-label">Mật khẩu</label>
        <input type="password" name="password" class="form-control mb-3" required>
        <button class="btn btn-primary">Đăng nhập</button>
    </form>
    <p class="mt-3 small">Chưa có tài khoản? <a href="<?= url('/dang-ky') ?>">Đăng ký</a></p>
</div></div>
