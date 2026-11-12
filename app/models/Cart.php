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
        $this->persist();
        return $new > $stock ? "Chỉ còn $stock sản phẩm trong kho, đã điều chỉnh số lượng." : null;
    }

    public function setQty(int $productId, int $qty): void
    {
        if ($qty < 1) { unset($_SESSION['cart'][$productId]); $this->persist(); return; }
        if (isset($_SESSION['cart'][$productId])) $_SESSION['cart'][$productId] = $qty;   // items() sẽ cắt theo tồn kho
        $this->persist();
    }

    public function remove(int $productId): void { unset($_SESSION['cart'][$productId]); $this->persist(); }

    public function clear(): void { unset($_SESSION['cart'], $_SESSION['coupon']); $this->persist(); }

    /* ===================== GIỎ HÀNG LƯU THEO TÀI KHOẢN =====================
     * SESSION vẫn là nơi giỏ được đọc/ghi hằng ngày (nhanh). Khi khách đã đăng nhập, mỗi thay đổi
     * được sao chép xuống bảng cart_items; đăng xuất chỉ xóa session nên dữ liệu trong DB vẫn còn,
     * lần đăng nhập sau (kể cả ở thiết bị khác) giỏ được khôi phục từ DB. */

    /** Ghi giỏ hiện tại trong session xuống DB (thay thế toàn bộ dòng cũ của user). Khách vãng lai thì bỏ qua. */
    private function persist(): void
    {
        $user = auth_user();
        if (!$user) return;
        $this->db->beginTransaction();
        $this->db->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$user['id']]);
        $ins = $this->db->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?,?,?)");
        foreach ($_SESSION['cart'] ?? [] as $pid => $qty) {
            if ($qty > 0) $ins->execute([$user['id'], (int)$pid, (int)$qty]);
        }
        $this->db->commit();
    }

    /**
     * Gọi ngay sau khi đăng nhập/đăng ký: gộp giỏ đã lưu trong DB với giỏ khách vừa thêm lúc chưa đăng nhập
     * (cùng sản phẩm thì cộng dồn số lượng), rồi cắt theo tồn kho và lưu lại.
     */
    public function restoreForUser(int $userId): void
    {
        $st = $this->db->prepare("SELECT product_id, quantity FROM cart_items WHERE user_id = ?");
        $st->execute([$userId]);
        foreach ($st->fetchAll() as $row) {
            $_SESSION['cart'][$row['product_id']] = ($_SESSION['cart'][$row['product_id']] ?? 0) + (int)$row['quantity'];
        }
        $this->items();     // loại sản phẩm hết hàng/ngừng bán, cắt số lượng theo tồn kho
        $this->persist();
    }

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
        if (($_SESSION['cart'] ?? []) != $cart) $this->persist();   // giỏ bị điều chỉnh (hết hàng/vượt tồn) -> đồng bộ DB
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
