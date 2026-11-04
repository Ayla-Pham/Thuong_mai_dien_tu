<?php
/**
 * Giỏ hàng lưu trong SESSION dưới dạng [product_id => số lượng].
 * Mỗi lần hiển thị, dữ liệu sản phẩm (giá, tồn kho) luôn được đọc lại từ DB
 * nên khách không thể tự sửa giá.
 */
class Cart extends Model
{
    public const FREE_SHIP_FROM = 300000;   // miễn phí vận chuyển từ mức này
    public const SHIP_FEE       = 20000;

    /** Tổng số lượng sản phẩm trong giỏ (hiển thị badge ở menu). */
    public static function count(): int
    {
        return array_sum($_SESSION['cart'] ?? []);
    }

    /** Thêm sản phẩm; số lượng bị giới hạn bởi tồn kho. Trả về thông báo lỗi hoặc null. */
    public function add(int $productId, int $qty): ?string
    {
        $st = $this->db->prepare("SELECT stock FROM products WHERE id = ? AND is_active = 1");
        $st->execute([$productId]);
        $stock = $st->fetchColumn();
        if ($stock === false)  return 'Sản phẩm không tồn tại.';
        if ($stock < 1)        return 'Sản phẩm đã hết hàng.';

        $new = ($_SESSION['cart'][$productId] ?? 0) + max(1, $qty);
        $_SESSION['cart'][$productId] = min($new, (int)$stock);
        return $new > $stock ? "Chỉ còn $stock sản phẩm trong kho, đã điều chỉnh số lượng." : null;
    }

    public function setQty(int $productId, int $qty): void
    {
        if ($qty < 1) { unset($_SESSION['cart'][$productId]); return; }
        if (isset($_SESSION['cart'][$productId])) $_SESSION['cart'][$productId] = $qty;   // items() sẽ cắt theo tồn kho
    }

    public function remove(int $productId): void { unset($_SESSION['cart'][$productId]); }

    public function clear(): void { unset($_SESSION['cart'], $_SESSION['coupon']); }

    /** Danh sách dòng giỏ hàng (đọc giá + tồn kho mới nhất từ DB). */
    public function items(): array
    {
        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) return [];
        $ids = array_map('intval', array_keys($cart));
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $st  = $this->db->prepare("SELECT p.*, COALESCE(p.sale_price, p.price) AS final_price FROM products p
                                   WHERE p.id IN ($in) AND p.is_active = 1");
        $st->execute($ids);

        $items = [];
        foreach ($st->fetchAll() as $p) {
            $qty = min((int)$cart[$p['id']], (int)$p['stock']);
            if ($qty < 1) continue;                       // hết hàng -> bỏ khỏi giỏ
            $_SESSION['cart'][$p['id']] = $qty;
            $items[] = ['p' => $p, 'qty' => $qty, 'line_total' => $qty * (int)$p['final_price']];
        }
        // Dọn các id không còn hợp lệ
        $valid = array_column(array_column($items, 'p'), 'id');
        foreach (array_keys($cart) as $id) if (!in_array($id, $valid)) unset($_SESSION['cart'][$id]);
        return $items;
    }

    /** Tổng hợp tiền: tạm tính, giảm giá (mã trong session), phí ship, tổng cộng. */
    public function totals(array $items): array
    {
        $subtotal = array_sum(array_column($items, 'line_total'));
        $discount = 0; $coupon = null; $error = null;

        if (!empty($_SESSION['coupon'])) {
            $coupon = (new Coupon())->findByCode($_SESSION['coupon']);
            [$discount, $error] = Coupon::evaluate($coupon, $subtotal);
            if ($error) { unset($_SESSION['coupon']); $coupon = null; }   // mã không còn hợp lệ -> gỡ
        }
        $shipping = ($subtotal === 0 || $subtotal >= self::FREE_SHIP_FROM) ? 0 : self::SHIP_FEE;
        return [
            'subtotal' => $subtotal, 'discount' => $discount, 'shipping' => $shipping,
            'total' => $subtotal - $discount + $shipping, 'coupon' => $coupon, 'coupon_error' => $error,
        ];
    }
}
