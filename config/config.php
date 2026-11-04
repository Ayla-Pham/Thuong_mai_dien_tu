<?php
/**
 * Cấu hình chung của website.
 * Đổi thông tin DB ở đây nếu MySQL của bạn khác mặc định XAMPP.
 */
return [
    'app_name' => 'BookStore - Sách & Văn phòng phẩm',
    'db' => [
        'host'    => 'localhost',
        'name'    => 'bookstore',
        'user'    => 'root',
        'pass'    => '',            // XAMPP mặc định không có mật khẩu
        'charset' => 'utf8mb4',
    ],
    /**
     * VNPay SANDBOX (môi trường thử nghiệm, không dùng tiền thật).
     * Đăng ký tài khoản merchant sandbox tại https://sandbox.vnpayment.vn/devreg/
     * rồi dán "Terminal ID (vnp_TmnCode)" và "Secret Key (vnp_HashSecret)" được gửi qua email vào đây.
     * Để trống => lựa chọn VNPay sẽ tự ẩn khỏi trang thanh toán.
     */
    'vnpay' => [
        'tmn_code'    => '',
        'hash_secret' => '',
        'pay_url'     => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
    ],
];
