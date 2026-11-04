<?php
/** Các hàm tiện ích dùng chung trong view. */

/** Escape dữ liệu khi in ra HTML -> chống XSS. */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Tạo URL trong site: url('/san-pham') => /Thuong_mai_dien_tu/san-pham */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Đường dẫn tới file tĩnh trong public/assets. */
function asset(string $path): string
{
    return BASE_URL . '/public/assets/' . ltrim($path, '/');
}

/** Chuyển hướng sang trang khác rồi dừng script. */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Định dạng tiền VND: 95000 => 95.000đ */
function money($n): string
{
    return number_format((float)$n, 0, ',', '.') . 'đ';
}

/** Ảnh sản phẩm (public/assets/products/<file>); chưa có file thật thì dùng ảnh placeholder. */
function product_image(array $p): string
{
    $file = 'products/' . ($p['image'] ?? '');
    if (!empty($p['image']) && is_file(ROOT_PATH . '/public/assets/' . $file)) return asset($file);
    return asset('img/placeholder.svg');
}

/* ===================== PHIÊN ĐĂNG NHẬP & PHÂN QUYỀN ===================== */

/** Thông tin người dùng đang đăng nhập (lưu trong session) hoặc null nếu là khách. */
function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Bắt buộc đăng nhập; chưa đăng nhập thì chuyển sang trang login. */
function require_login(): array
{
    if (!auth_user()) {
        flash('warning', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('/dang-nhap');
    }
    return auth_user();
}

/** Bắt buộc quyền admin (phân quyền); không đủ quyền trả 403. */
function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        exit('403 - Bạn không có quyền truy cập trang này');
    }
    return $u;
}

/** Tạo slug thân thiện URL từ tiếng Việt: "Mắt Biếc" => "mat-biec". */
function slugify(string $s): string
{
    $s = mb_strtolower($s, 'UTF-8');
    $map = ['a' => 'àáạảãâầấậẩẫăằắặẳẵ', 'e' => 'èéẹẻẽêềếệểễ', 'i' => 'ìíịỉĩ',
            'o' => 'òóọỏõôồốộổỗơờớợởỡ', 'u' => 'ùúụủũưừứựửữ', 'y' => 'ỳýỵỷỹ', 'd' => 'đ'];
    foreach ($map as $to => $chars) $s = preg_replace('/[' . $chars . ']/u', $to, $s);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $s), '-') ?: 'item';
}

/* ===================== ĐƠN HÀNG ===================== */

function status_label(string $s): string
{
    return ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao',
            'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy'][$s] ?? $s;
}

function status_color(string $s): string
{
    return ['pending' => 'warning', 'confirmed' => 'info', 'shipping' => 'primary',
            'completed' => 'success', 'cancelled' => 'secondary'][$s] ?? 'light';
}

function payment_label(string $m): string
{
    return ['cod' => 'COD', 'bank_transfer' => 'Chuyển khoản', 'vnpay' => 'VNPay'][$m] ?? $m;
}

/* ===================== CSRF ===================== */

/** Token CSRF theo phiên; nhúng vào mọi form POST để chống giả mạo request. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Kiểm tra token của request POST; sai thì dừng với mã 419. */
function csrf_verify(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);   // 419 không phải mã chuẩn, Apache sẽ đổi thành 500
        exit('403 - Phiên làm việc hết hạn hoặc yêu cầu không hợp lệ. Hãy tải lại trang.');
    }
}

/* ===================== FLASH MESSAGE & OLD INPUT ===================== */

/** Lưu thông báo hiển thị 1 lần ở lần tải trang kế tiếp. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Lấy và xóa toàn bộ thông báo flash. */
function flash_pull(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Giữ lại dữ liệu form đã nhập khi validate thất bại (đã escape). */
function old(string $key, string $default = ''): string
{
    return e($_SESSION['old'][$key] ?? $default);
}
