<?php
/**
 * Model Review: đánh giá sản phẩm.
 * Quy tắc nghiệp vụ: chỉ khách ĐÃ MUA và đã nhận hàng (đơn ở trạng thái "completed") mới được đánh giá,
 * mỗi người đánh giá 1 lần cho mỗi sản phẩm (có thể sửa lại) - ràng buộc UNIQUE(product_id, user_id) trong CSDL.
 */
class Review extends Model
{
    /** Khách đã mua và nhận hàng sản phẩm này chưa? */
    public function hasPurchased(int $userId, int $productId): bool
    {
        $st = $this->db->prepare(
            "SELECT 1 FROM order_items oi JOIN orders o ON o.id = oi.order_id
             WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed' LIMIT 1");
        $st->execute([$userId, $productId]);
        return (bool)$st->fetchColumn();
    }

    /** Đánh giá hiện có của khách cho sản phẩm (để hiển thị sẵn khi sửa), hoặc null. */
    public function findMine(int $userId, int $productId): ?array
    {
        $st = $this->db->prepare("SELECT * FROM reviews WHERE user_id = ? AND product_id = ?");
        $st->execute([$userId, $productId]);
        return $st->fetch() ?: null;
    }

    /** Thêm mới hoặc cập nhật đánh giá (INSERT ... ON DUPLICATE KEY UPDATE dựa trên khóa UNIQUE). */
    public function save(int $userId, int $productId, int $rating, ?string $comment): void
    {
        $this->db->prepare(
            "INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = CURRENT_TIMESTAMP")
            ->execute([$productId, $userId, $rating, $comment]);
    }

    /** Xóa đánh giá của chính mình. */
    public function deleteMine(int $userId, int $productId): void
    {
        $this->db->prepare("DELETE FROM reviews WHERE user_id = ? AND product_id = ?")->execute([$userId, $productId]);
    }
}
