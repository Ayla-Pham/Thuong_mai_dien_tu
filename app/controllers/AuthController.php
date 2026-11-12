<?php
/** Đăng ký, đăng nhập, đăng xuất. */
class AuthController extends Controller
{
    public function showRegister(): void
    {
        if (auth_user()) redirect('/');
        $this->render('auth/register', ['title' => 'Đăng ký tài khoản', 'description' => 'Tạo tài khoản BookStore để mua sắm và theo dõi đơn hàng.']);
    }

    /** POST /dang-ky - validate dữ liệu đầu vào rồi tạo tài khoản. */
    public function register(): void
    {
        csrf_verify();
        $name  = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $pass2 = $_POST['password_confirm'] ?? '';
        $_SESSION['old'] = ['full_name' => $name, 'email' => $email, 'phone' => $phone];

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100)               $errors[] = 'Họ tên phải từ 2 đến 100 ký tự.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))                    $errors[] = 'Email không hợp lệ.';
        if ($phone !== '' && !preg_match('/^(0|\+84)\d{9,10}$/', $phone))  $errors[] = 'Số điện thoại không hợp lệ.';
        if (strlen($pass) < 6)                                             $errors[] = 'Mật khẩu tối thiểu 6 ký tự.';
        if ($pass !== $pass2)                                              $errors[] = 'Mật khẩu xác nhận không khớp.';

        $users = new User();
        if (!$errors && $users->findByEmail($email))                       $errors[] = 'Email này đã được đăng ký.';

        if ($errors) {
            foreach ($errors as $err) flash('danger', $err);
            redirect('/dang-ky');
        }

        $id = $users->create($name, $email, $pass, $phone ?: null);
        unset($_SESSION['old']);
        $this->loginAs($users->findById($id));
        flash('success', 'Đăng ký thành công! Chào mừng bạn đến BookStore.');
        redirect('/');
    }

    public function showLogin(): void
    {
        if (auth_user()) redirect('/');
        $this->render('auth/login', ['title' => 'Đăng nhập', 'description' => 'Đăng nhập tài khoản BookStore.']);
    }

    /** POST /dang-nhap - xác thực bằng password_verify (so sánh với hash trong DB). */
    public function login(): void
    {
        csrf_verify();
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        $_SESSION['old'] = ['email' => $email];

        $user = (new User())->findByEmail($email);
        // Thông báo chung chung để không lộ email nào tồn tại
        if (!$user || !password_verify($pass, $user['password'])) {
            flash('danger', 'Email hoặc mật khẩu không đúng.');
            redirect('/dang-nhap');
        }
        if (!$user['is_active']) {
            flash('danger', 'Tài khoản đã bị khóa.');
            redirect('/dang-nhap');
        }

        unset($_SESSION['old']);
        $this->loginAs($user);
        flash('success', 'Đăng nhập thành công.');
        redirect($user['role'] === 'admin' ? '/admin' : '/');
    }

    /** POST /dang-xuat */
    public function logout(): void
    {
        csrf_verify();
        $_SESSION = [];
        session_regenerate_id(true);
        flash('success', 'Bạn đã đăng xuất.');
        redirect('/');
    }

    /** Lưu thông tin tối thiểu vào session; đổi session id để chống session fixation. */
    private function loginAs(array $u): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$u['id'], 'full_name' => $u['full_name'], 'email' => $u['email'], 'role' => $u['role']];
        (new Cart())->restoreForUser((int)$u['id']);   // khôi phục giỏ hàng đã lưu + gộp với giỏ vừa thêm khi chưa đăng nhập
    }
}
