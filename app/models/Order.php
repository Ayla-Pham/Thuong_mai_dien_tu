<?php
/** Model Order: tạo đơn (transaction), lịch sử, chi tiết, hủy đơn. */
class Order extends Model
{
    /**
     * ĐẶT HÀNG - xử lý đồng thời an toàn:
     *  1. BEGIN TRANSACTION: các bước dưới đây hoặc cùng thành công, hoặc cùng bị hủy (rollback).
     *  2. SELECT ... FOR UPDATE: khóa dòng sản phẩm, 2 khách mua cùng lúc sẽ phải xếp hàng,
     *     không thể cùng "giành" món cuối cùng (tránh bán quá tồn kho).
     *  3. Giá lấy lại từ DB tại thời điểm khóa (không tin dữ liệu từ giỏ hàng).
     *  4. Trừ kho, tăng sold_count, ghi đơn + chi tiết (lưu snapshot tên/giá), tăng lượt dùng mã.
     *
     * @param array $cart  [product_id => qty]
     * @return string mã đơn hàng
     * @throws RuntimeException khi hết hàng / mã giảm giá không hợp lệ
     */
    public function place(int $userId, array $cart, array $ship, string $payment, ?string $couponCode): string
    {
        $this->db->beginTransaction();
        try {
            ksort($cart);   // luôn khóa theo thứ tự id tăng dần để tránh deadlock
            $lines = []; $subtotal = 0;
            $lock = $this->db->prepare("SELECT id, name, COALESCE(sale_price, price) AS price, stock, is_active
                                        FROM products WHERE id = ? FOR UPDATE");
            foreach ($cart as $pid => $qty) {
                $lock->execute([$pid]);
                $p = $lock->fetch();
                if (!$p || !$p['is_active']) throw new RuntimeException('Một sản phẩm trong giỏ không còn được bán.');
                if ($p['stock'] < $qty)      throw new RuntimeException("«{$p['name']}» chỉ còn {$p['stock']} sản phẩm.");
                $lines[] = ['p' => $p, 'qty' => (int)$qty];
                $subtotal += (int)$p['price'] * (int)$qty;
            }

            // Mã giảm giá: khóa dòng và kiểm tra lại ngay trong transaction
            $discount = 0; $couponId = null;
            if ($couponCode) {
                $coupon = (new Coupon())->findByCode($couponCode, true);
                [$discount, $err] = Coupon::evaluate($coupon, $subtotal);
                if ($err) throw new RuntimeException($err);
                $couponId = (int)$coupon['id'];
                $this->db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$couponId]);
            }

            $shipping = $subtotal >= Cart::FREE_SHIP_FROM ? 0 : Cart::SHIP_FEE;
            $total = $subtotal - $discount + $shipping;
            $code  = 'DH' . date('YmdHis') . random_int(10, 99);

            $this->db->prepare("INSERT INTO orders (user_id, coupon_id, order_code, receiver_name, receiver_phone, shipping_address,
                        note, subtotal, discount_amount, shipping_fee, total, payment_method)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$userId, $couponId, $code, $ship['name'], $ship['phone'], $ship['address'], $ship['note'] ?: null,
                           $subtotal, $discount, $shipping, $total, $payment]);
            $orderId = (int)$this->db->lastInsertId();

            $item = $this->db->prepare("INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity) VALUES (?,?,?,?,?)");
            $stock = $this->db->prepare("UPDATE products SET stock = stock - ?, sold_count = sold_count + ? WHERE id = ?");
            foreach ($lines as $l) {
                $item->execute([$orderId, $l['p']['id'], $l['p']['name'], $l['p']['price'], $l['qty']]);
                $stock->execute([$l['qty'], $l['qty'], $l['p']['id']]);
            }
            $this->db->commit();
            return $code;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /* ===================== DÀNH CHO ADMIN ===================== */

    /** Luồng trạng thái hợp lệ: chỉ đi tiến, hoặc hủy trước khi hoàn thành. */
    public const FLOW = [
        'pending'   => ['confirmed', 'cancelled'],
        'confirmed' => ['shipping', 'cancelled'],
        'shipping'  => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    /** Danh sách mọi đơn (lọc theo trạng thái nếu có), kèm tên khách. */
    public function all(string $status = ''): array
    {
        $sql = "SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON u.id = o.user_id";
        $args = [];
        if (isset(self::FLOW[$status])) { $sql .= " WHERE o.status = ?"; $args[] = $status; }
        $st = $this->db->prepare($sql . " ORDER BY o.id DESC LIMIT 200");
        $st->execute($args);
        return $st->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = $this->db->prepare("SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?");
        $st->execute([$id]);
        $o = $st->fetch();
        if (!$o) return null;
        $it = $this->db->prepare("SELECT oi.*, p.slug FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
        $it->execute([$id]);
        $o['items'] = $it->fetchAll();
        return $o;
    }

    /**
     * Admin đổi trạng thái đơn. Kiểm tra chuyển trạng thái hợp lệ;
     * hủy => hoàn kho; hoàn thành đơn COD => tự đánh dấu đã thu tiền.
     * @return string|null thông báo lỗi, null nếu thành công
     */
    public function adminSetStatus(int $id, string $new): ?string
    {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("SELECT * FROM orders WHERE id = ? FOR UPDATE");
            $st->execute([$id]);
            $o = $st->fetch();
            if (!$o) { $this->db->rollBack(); return 'Không tìm thấy đơn hàng.'; }
            if (!in_array($new, self::FLOW[$o['status']] ?? [], true)) {
                $this->db->rollBack();
                return 'Không thể chuyển từ "' . status_label($o['status']) . '" sang "' . status_label($new) . '".';
            }
            $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new, $id]);
            if ($new === 'cancelled') $this->restock($o);
            if ($new === 'completed' && $o['payment_method'] === 'cod') {
                $this->db->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?")->execute([$id]);
            }
            $this->db->commit();
            return null;
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    /** Xác nhận đã nhận tiền (vd: kiểm tra chuyển khoản xong). */
    public function setPaid(int $id, bool $paid): void
    {
        $this->db->prepare("UPDATE orders SET payment_status = ? WHERE id = ? AND status <> 'cancelled'")
                 ->execute([$paid ? 'paid' : 'unpaid', $id]);
    }

    /** Hoàn tồn kho, giảm sold_count và trả lại lượt dùng mã khi đơn bị hủy. */
    private function restock(array $o): void
    {
        $this->db->prepare("UPDATE products p JOIN order_items oi ON oi.product_id = p.id
                            SET p.stock = p.stock + oi.quantity, p.sold_count = GREATEST(p.sold_count - oi.quantity, 0)
                            WHERE oi.order_id = ?")->execute([$o['id']]);
        if ($o['coupon_id']) {
            $this->db->prepare("UPDATE coupons SET used_count = GREATEST(used_count - 1, 0) WHERE id = ?")->execute([$o['coupon_id']]);
        }
    }

    /**
     * Ghi nhận kết quả thanh toán VNPay (dùng cho cả Return URL và IPN).
     * Idempotent: gọi lại nhiều lần với cùng đơn đã thanh toán sẽ không đổi gì thêm.
     * @param int $vnpAmount số tiền VNPay báo về (đã nhân 100) - phải khớp tổng đơn, chống sửa số tiền
     * @return string '00' ok | '01' không thấy đơn | '02' đã xác nhận trước đó | '04' sai số tiền
     */
    public function applyVnpayResult(string $orderCode, int $vnpAmount, bool $success): string
    {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("SELECT * FROM orders WHERE order_code = ? AND payment_method = 'vnpay' FOR UPDATE");
            $st->execute([$orderCode]);
            $o = $st->fetch();
            if (!$o)                                   { $this->db->rollBack(); return '01'; }
            if ((int)$o['total'] * 100 !== $vnpAmount) { $this->db->rollBack(); return '04'; }
            if ($o['payment_status'] === 'paid')       { $this->db->rollBack(); return '02'; }

            $this->db->prepare("UPDATE orders SET payment_status = ? WHERE id = ?")->execute([$success ? 'paid' : 'failed', $o['id']]);
            $this->db->commit();
            return '00';
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    public function forUser(int $userId): array
    {
        $st = $this->db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    /** Chi tiết đơn - ràng buộc user_id để khách không xem được đơn của người khác. */
    public function findForUser(string $code, int $userId): ?array
    {
        $st = $this->db->prepare("SELECT * FROM orders WHERE order_code = ? AND user_id = ?");
        $st->execute([$code, $userId]);
        $o = $st->fetch();
        if (!$o) return null;
        $it = $this->db->prepare("SELECT oi.*, p.slug, p.image FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
        $it->execute([$o['id']]);
        $o['items'] = $it->fetchAll();
        return $o;
    }

    /** Khách hủy đơn khi còn "chờ xác nhận": hoàn lại tồn kho + lượt dùng mã. */
    public function cancel(string $code, int $userId): bool
    {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("SELECT * FROM orders WHERE order_code = ? AND user_id = ? FOR UPDATE");
            $st->execute([$code, $userId]);
            $o = $st->fetch();
            if (!$o || $o['status'] !== 'pending') { $this->db->rollBack(); return false; }

            $this->db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$o['id']]);
            $this->db->prepare("UPDATE products p JOIN order_items oi ON oi.product_id = p.id
                                SET p.stock = p.stock + oi.quantity, p.sold_count = GREATEST(p.sold_count - oi.quantity, 0)
                                WHERE oi.order_id = ?")->execute([$o['id']]);
            if ($o['coupon_id']) {
                $this->db->prepare("UPDATE coupons SET used_count = GREATEST(used_count - 1, 0) WHERE id = ?")->execute([$o['coupon_id']]);
            }
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
