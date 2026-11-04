<div class="row justify-content-center"><div class="col-md-6 col-lg-5">
    <h1 class="h4 mb-3">Đăng ký tài khoản</h1>
    <form method="post" action="<?= url('/dang-ky') ?>" class="card card-body">
        <?= csrf_field() ?>
        <label class="form-label">Họ và tên</label>
        <input name="full_name" class="form-control mb-2" required maxlength="100" value="<?= old('full_name') ?>">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control mb-2" required value="<?= old('email') ?>">
        <label class="form-label">Số điện thoại (không bắt buộc)</label>
        <input name="phone" class="form-control mb-2" value="<?= old('phone') ?>">
        <label class="form-label">Mật khẩu</label>
        <input type="password" name="password" class="form-control mb-2" required minlength="6">
        <label class="form-label">Nhập lại mật khẩu</label>
        <input type="password" name="password_confirm" class="form-control mb-3" required minlength="6">
        <button class="btn btn-primary">Đăng ký</button>
    </form>
    <p class="mt-3 small">Đã có tài khoản? <a href="<?= url('/dang-nhap') ?>">Đăng nhập</a></p>
</div></div>
