<h1 class="h4 mb-3">Tài khoản của tôi</h1>
<div class="row g-4">
    <div class="col-md-6">
        <form method="post" action="<?= url('/tai-khoan') ?>" class="card card-body">
            <?= csrf_field() ?>
            <h2 class="h6">Thông tin cá nhân</h2>
            <label class="form-label">Email</label>
            <input class="form-control mb-2" value="<?= e($user['email']) ?>" disabled>
            <label class="form-label">Họ và tên</label>
            <input name="full_name" class="form-control mb-2" required maxlength="100" value="<?= e($user['full_name']) ?>">
            <label class="form-label">Số điện thoại</label>
            <input name="phone" class="form-control mb-2" value="<?= e($user['phone']) ?>">
            <label class="form-label">Địa chỉ giao hàng</label>
            <input name="address" class="form-control mb-3" maxlength="255" value="<?= e($user['address']) ?>">
            <button class="btn btn-primary">Lưu thay đổi</button>
        </form>
    </div>
    <div class="col-md-6">
        <form method="post" action="<?= url('/tai-khoan/doi-mat-khau') ?>" class="card card-body">
            <?= csrf_field() ?>
            <h2 class="h6">Đổi mật khẩu</h2>
            <label class="form-label">Mật khẩu hiện tại</label>
            <input type="password" name="current_password" class="form-control mb-2" required>
            <label class="form-label">Mật khẩu mới</label>
            <input type="password" name="new_password" class="form-control mb-2" required minlength="6">
            <label class="form-label">Nhập lại mật khẩu mới</label>
            <input type="password" name="new_password_confirm" class="form-control mb-3" required minlength="6">
            <button class="btn btn-outline-primary">Đổi mật khẩu</button>
        </form>
    </div>
</div>
