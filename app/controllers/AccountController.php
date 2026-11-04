<?php
/** Quản lý tài khoản: xem/cập nhật hồ sơ, đổi mật khẩu. Yêu cầu đã đăng nhập. */
class AccountController extends Controller
{
    public function profile(): void
    {
        $sess = require_login();
        $user = (new User())->findById($sess['id']);
        $this->render('account/profile', ['title' => 'Tài khoản của tôi', 'description' => 'Cập nhật thông tin cá nhân.', 'user' => $user]);
    }

    public function updateProfile(): void
    {
        $sess = require_login();
        csrf_verify();
        $name    = trim($_POST['full_name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100)               $errors[] = 'Họ tên phải từ 2 đến 100 ký tự.';
        if ($phone !== '' && !preg_match('/^(0|\+84)\d{9,10}$/', $phone))  $errors[] = 'Số điện thoại không hợp lệ.';
        if (mb_strlen($address) > 255)                                     $errors[] = 'Địa chỉ quá dài.';
        if ($errors) {
            foreach ($errors as $er) flash('danger', $er);
            redirect('/tai-khoan');
        }

        (new User())->updateProfile($sess['id'], $name, $phone ?: null, $address ?: null);
        $_SESSION['user']['full_name'] = $name;
        flash('success', 'Đã cập nhật thông tin cá nhân.');
        redirect('/tai-khoan');
    }

    public function changePassword(): void
    {
        $sess = require_login();
        csrf_verify();
        $users = new User();
        $user  = $users->findById($sess['id']);
        $cur = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';

        if (!password_verify($cur, $user['password']))               flash('danger', 'Mật khẩu hiện tại không đúng.');
        elseif (strlen($new) < 6)                                    flash('danger', 'Mật khẩu mới tối thiểu 6 ký tự.');
        elseif ($new !== ($_POST['new_password_confirm'] ?? ''))     flash('danger', 'Mật khẩu xác nhận không khớp.');
        else {
            $users->updatePassword((int)$user['id'], $new);
            flash('success', 'Đổi mật khẩu thành công.');
        }
        redirect('/tai-khoan');
    }
}
