<?php
/**
 * Model Product: mọi truy vấn liên quan tới sản phẩm (sách + văn phòng phẩm).
 * Toàn bộ dùng prepared statements (tham số ? / :name) để chống SQL Injection.
 */
class Product extends Model
{
    /** Câu SELECT chung: final_price = giá khuyến mãi nếu có, ngược lại giá gốc; kèm tên tác giả. */
    private const BASE_SELECT = "SELECT p.*, COALESCE(p.sale_price, p.price) AS final_price,
            (SELECT GROUP_CONCAT(a.name SEPARATOR ', ') FROM product_authors pa
              JOIN authors a ON a.id = pa.author_id WHERE pa.product_id = p.id) AS author_names
        FROM products p";

    /** Sản phẩm bán chạy nhất (trang chủ). */
    public function bestSellers(int $limit = 8): array
    {
        $st = $this->db->prepare(self::BASE_SELECT . " WHERE p.is_active = 1 ORDER BY p.sold_count DESC LIMIT :n");
        $st->bindValue(':n', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** Sản phẩm mới nhất. */
    public function newest(int $limit = 4): array
    {
        $st = $this->db->prepare(self::BASE_SELECT . " WHERE p.is_active = 1 ORDER BY p.created_at DESC, p.id DESC LIMIT :n");
        $st->bindValue(':n', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /**
     * BỘ LỌC & TÌM KIẾM NÂNG CAO (chức năng đặc trưng).
     * Kết hợp đồng thời: từ khóa (tên/tác giả/NXB), danh mục, tác giả, NXB, khoảng giá, sắp xếp.
     * Điều kiện được ghép động nhưng GIÁ TRỊ luôn truyền qua tham số => an toàn.
     *
     * @return array [danh sách sản phẩm, tổng số kết quả]
     */
    public function search(array $f, int $page = 1, int $perPage = 9): array
    {
        $where = ['p.is_active = 1'];
        $args  = [];

        if (!empty($f['q'])) {
            $where[] = "(p.name LIKE :q1
                OR EXISTS (SELECT 1 FROM product_authors pa JOIN authors a ON a.id = pa.author_id
                           WHERE pa.product_id = p.id AND a.name LIKE :q2)
                OR EXISTS (SELECT 1 FROM publishers pb WHERE pb.id = p.publisher_id AND pb.name LIKE :q3)
                OR EXISTS (SELECT 1 FROM product_tags pt JOIN tags t ON t.id = pt.tag_id
                           WHERE pt.product_id = p.id AND t.name LIKE :q4))";
            $like = '%' . $f['q'] . '%';
            $args += [':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like];
        }
        if (!empty($f['category'])) {
            // Chọn danh mục cha thì lấy luôn các danh mục con
            $where[] = "p.category_id IN (SELECT c.id FROM categories c
                          LEFT JOIN categories par ON par.id = c.parent_id
                          WHERE c.slug = :cat1 OR par.slug = :cat2)";
            $args += [':cat1' => $f['category'], ':cat2' => $f['category']];
        }
        if (!empty($f['author'])) {
            $where[] = "EXISTS (SELECT 1 FROM product_authors pa JOIN authors a ON a.id = pa.author_id
                         WHERE pa.product_id = p.id AND a.slug = :author)";
            $args[':author'] = $f['author'];
        }
        if (!empty($f['publisher'])) {
            $where[] = "p.publisher_id = (SELECT id FROM publishers WHERE slug = :pub)";
            $args[':pub'] = $f['publisher'];
        }
        if (isset($f['min_price']) && $f['min_price'] !== '') {
            $where[] = "COALESCE(p.sale_price, p.price) >= :minp";
            $args[':minp'] = (int)$f['min_price'];
        }
        if (isset($f['max_price']) && $f['max_price'] !== '') {
            $where[] = "COALESCE(p.sale_price, p.price) <= :maxp";
            $args[':maxp'] = (int)$f['max_price'];
        }

        // Danh sách sắp xếp cố định (whitelist) - không đưa input người dùng vào ORDER BY
        $orders = [
            'newest'     => 'p.created_at DESC, p.id DESC',
            'price_asc'  => 'final_price ASC',
            'price_desc' => 'final_price DESC',
            'bestseller' => 'p.sold_count DESC',
        ];
        $order = $orders[$f['sort'] ?? ''] ?? $orders['newest'];
        $whereSql = implode(' AND ', $where);

        $cnt = $this->db->prepare("SELECT COUNT(*) FROM products p WHERE $whereSql");
        $cnt->execute($args);
        $total = (int)$cnt->fetchColumn();

        $st = $this->db->prepare(self::BASE_SELECT . " WHERE $whereSql ORDER BY $order LIMIT :lim OFFSET :off");
        foreach ($args as $k => $v) $st->bindValue($k, $v);
        $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $st->bindValue(':off', max(0, ($page - 1) * $perPage), PDO::PARAM_INT);
        $st->execute();
        return [$st->fetchAll(), $total];
    }

    /** Chi tiết 1 sản phẩm theo slug (URL thân thiện). */
    public function findBySlug(string $slug): ?array
    {
        $st = $this->db->prepare(self::BASE_SELECT . " WHERE p.slug = ? AND p.is_active = 1");
        $st->execute([$slug]);
        return $st->fetch() ?: null;
    }

    /** Chạy 1 truy vấn có tham số, trả về statement (hàm phụ cho details()). */
    private function run(string $sql, array $args): PDOStatement
    {
        $st = $this->db->prepare($sql);
        $st->execute($args);
        return $st;
    }

    /** Lấy tác giả / NXB / danh mục / tag / đánh giá của 1 sản phẩm cho trang chi tiết. */
    public function details(int $id, ?int $publisherId, int $categoryId): array
    {
        return [
            'authors'   => $this->run("SELECT a.* FROM authors a JOIN product_authors pa ON pa.author_id = a.id WHERE pa.product_id = ?", [$id])->fetchAll(),
            'publisher' => $publisherId ? $this->run("SELECT * FROM publishers WHERE id = ?", [$publisherId])->fetch() : null,
            'category'  => $this->run("SELECT * FROM categories WHERE id = ?", [$categoryId])->fetch(),
            'tags'      => $this->run("SELECT t.name FROM tags t JOIN product_tags pt ON pt.tag_id = t.id WHERE pt.product_id = ?", [$id])->fetchAll(PDO::FETCH_COLUMN),
            'rating'    => $this->run("SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS total FROM reviews WHERE product_id = ?", [$id])->fetch(),
            'reviews'   => $this->run("SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC", [$id])->fetchAll(),
        ];
    }

    /**
     * GỢI Ý "SẢN PHẨM LIÊN QUAN" (dựa trên nội dung - content-based).
     * Điểm tương đồng = 2 * số tag chung + 1 nếu cùng danh mục; sắp xếp điểm giảm dần.
     */
    public function related(int $id, int $categoryId, int $limit = 4): array
    {
        $sql = self::BASE_SELECT . " LEFT JOIN (
                    SELECT pt2.product_id, COUNT(*) AS common_tags
                    FROM product_tags pt1
                    JOIN product_tags pt2 ON pt1.tag_id = pt2.tag_id AND pt2.product_id <> pt1.product_id
                    WHERE pt1.product_id = :id1 GROUP BY pt2.product_id) s ON s.product_id = p.id
                WHERE p.id <> :id2 AND p.is_active = 1
                  AND (s.common_tags IS NOT NULL OR p.category_id = :cat1)
                ORDER BY (COALESCE(s.common_tags, 0) * 2 + (p.category_id = :cat2)) DESC, p.sold_count DESC
                LIMIT :n";
        $st = $this->db->prepare($sql);
        $st->bindValue(':id1', $id, PDO::PARAM_INT);
        $st->bindValue(':id2', $id, PDO::PARAM_INT);
        $st->bindValue(':cat1', $categoryId, PDO::PARAM_INT);
        $st->bindValue(':cat2', $categoryId, PDO::PARAM_INT);
        $st->bindValue(':n', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /**
     * GỢI Ý "THƯỜNG ĐƯỢC MUA CÙNG" (giỏ hàng - market basket).
     * Đếm số đơn (không bị hủy) chứa đồng thời sản phẩm này và sản phẩm khác.
     */
    public function boughtTogether(int $id, int $limit = 4): array
    {
        $sql = self::BASE_SELECT . " JOIN (
                    SELECT oi2.product_id, COUNT(DISTINCT oi2.order_id) AS times
                    FROM order_items oi1
                    JOIN order_items oi2 ON oi1.order_id = oi2.order_id AND oi2.product_id <> oi1.product_id
                    JOIN orders o ON o.id = oi1.order_id AND o.status <> 'cancelled'
                    WHERE oi1.product_id = :id GROUP BY oi2.product_id) b ON b.product_id = p.id
                WHERE p.is_active = 1 ORDER BY b.times DESC, p.sold_count DESC LIMIT :n";
        $st = $this->db->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->bindValue(':n', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
}
