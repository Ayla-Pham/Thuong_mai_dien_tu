<?php
/**
 * VnPay: tích hợp cổng thanh toán VNPay (sandbox) theo tài liệu API phiên bản 2.1.0.
 *
 * Nguyên lý bảo mật: hai bên (website <-> VNPay) cùng biết một khóa bí mật (HashSecret).
 * Mỗi yêu cầu/kết quả đều kèm chữ ký vnp_SecureHash = HMAC-SHA512(dữ liệu đã sắp xếp, HashSecret).
 * Bên nhận tính lại chữ ký, nếu khác nhau nghĩa là dữ liệu đã bị sửa -> từ chối.
 */
class VnPay
{
    /** VNPay chỉ bật khi đã điền TmnCode + HashSecret trong config. */
    public static function enabled(): bool
    {
        global $config;
        return !empty($config['vnpay']['tmn_code']) && !empty($config['vnpay']['hash_secret']);
    }

    /**
     * Tạo URL chuyển khách sang trang thanh toán VNPay.
     * @param array $order  đơn hàng (cần order_code, total)
     */
    public static function paymentUrl(array $order, string $ip): string
    {
        global $config;
        $c = $config['vnpay'];
        $now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));

        $data = [
            'vnp_Version'    => '2.1.0',
            'vnp_Command'    => 'pay',
            'vnp_TmnCode'    => $c['tmn_code'],
            'vnp_Amount'     => (int)$order['total'] * 100,            // VNPay yêu cầu nhân 100
            'vnp_CurrCode'   => 'VND',
            'vnp_TxnRef'     => $order['order_code'] . '_' . time(),   // mỗi lần thanh toán phải khác nhau (kể cả thanh toán lại)
            'vnp_OrderInfo'  => 'Thanh toan don hang ' . $order['order_code'],
            'vnp_OrderType'  => 'other',
            'vnp_Locale'     => 'vn',
            'vnp_ReturnUrl'  => self::absoluteUrl('/thanh-toan/vnpay-return'),
            'vnp_IpAddr'     => $ip,
            'vnp_CreateDate' => $now->format('YmdHis'),
            'vnp_ExpireDate' => (clone $now)->modify('+15 minutes')->format('YmdHis'),
        ];
        return $c['pay_url'] . '?' . self::query($data) . '&vnp_SecureHash=' . self::sign($data);
    }

    /** Kiểm tra chữ ký của dữ liệu VNPay gửi về (Return URL hoặc IPN). */
    public static function verify(array $params): bool
    {
        $given = $params['vnp_SecureHash'] ?? '';
        $data = [];
        foreach ($params as $k => $v) {
            if (str_starts_with($k, 'vnp_') && $k !== 'vnp_SecureHash' && $k !== 'vnp_SecureHashType') $data[$k] = (string)$v;
        }
        return $data !== [] && is_string($given) && hash_equals(self::sign($data), strtolower($given));
    }

    /** Tách mã đơn từ vnp_TxnRef dạng "DH2026..._1730000000". */
    public static function orderCodeFromRef(string $ref): string
    {
        return explode('_', $ref)[0];
    }

    /** Sắp xếp theo tên khóa và nối thành chuỗi key=value&key=value (đã urlencode). */
    private static function query(array $data): string
    {
        ksort($data);
        $parts = [];
        foreach ($data as $k => $v) {
            if ($v !== '' && $v !== null) $parts[] = urlencode($k) . '=' . urlencode((string)$v);
        }
        return implode('&', $parts);
    }

    private static function sign(array $data): string
    {
        global $config;
        return hash_hmac('sha512', self::query($data), $config['vnpay']['hash_secret']);
    }

    /** URL đầy đủ (VNPay cần địa chỉ tuyệt đối để quay lại website). */
    private static function absoluteUrl(string $path): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . url($path);
    }
}
