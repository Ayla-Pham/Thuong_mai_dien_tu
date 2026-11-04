<?php
/** Model ProductManager: thêm / sửa / xóa sản phẩm cho trang quản trị (kèm tác giả, tag, upload ảnh). */
class ProductManager extends Model
{
    public function list(string $q = ''): array
    {
        $sql = "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id";
        $args = [];
        if ($q !== '') { $sql .= " WHERE p.name LIKE ?"; $args[] = "%$q%"; }
        $st = $this->db->prepare($sql . " ORDER BY p.id DESC");
        $st->execute($args);
        return $st->fetchAll();
    }

    /** Lấy 1 sản phẩm kèm chuỗi tác giả, tag (phân cách bằng dấu phẩy) để đổ vào form. */
    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $st->execute([$id]);
        $p = $st->fetch();
        if (!$p) return null;
        $a = $this->db->prepare("SELECT a.name FROM authors a JOIN product_authors pa ON pa.author_id = a.id WHERE pa.product_id = ?");
        $a->execute([$id]);
        $t = $this->db->prepare("SELECT t.name FROM tags t JOIN product_tags pt ON pt.tag_id = t.id WHERE pt.product_id = ?");
        $t->execute([$id]);
        $p['authors_text'] = implode(', ', $a->fetchAll(PDO::FETCH_COLUMN));
        $p['tags_text']    = implode(', ', $t->fetchAll(PDO::FETCH_COLUMN));
        return $p;
    }

    public function categories(): array
    {
        return $this->db->query("SELECT c.id, IF(par.name IS NULL, c.name, CONCAT(par.name, ' › ', c.name)) AS label
                                 FROM categories c LEFT JOIN categories par ON par.id = c.parent_id ORDER BY label")->fetchAll();
    }

    public function publishers(): array
    {
        return $this->db->query("SELECT id, name FROM publishers ORDER BY name")->fetchAll();
    }

    /** Tạo slug duy nhất trong bảng (thêm -2, -3... nếu trùng). */
    private function uniqueSlug(string $table, string $base, int $ignoreId = 0): string
    {
        $slug = $base; $i = 2;
        $st = $this->db->prepare("SELECT COUNT(*) FROM $table WHERE slug = ? AND id <> ?");   // $table là hằng do code truyền, không phải input
        while (true) {
            $st->execute([$slug, $ignoreId]);
            if (!$st->fetchColumn()) return $slug;
            $slug = $base . '-' . $i++;
        }
    }

    /** Tách chuỗi "a, b, c" thành mảng giá trị không rỗng, không trùng. */
    private function splitList(string $text): array
    {
        $out = [];
        foreach (explode(',', $text) as $x) {
            $x = trim($x);
            if ($x !== '' && mb_strlen($x) <= 100) $out[mb_strtolower($x)] = $x;
        }
        return array_values($out);
    }

    /** Tìm theo tên, chưa có thì tạo mới; trả về id (dùng cho tác giả và tag). */
    private function findOrCreate(string $table, string $name, bool $hasSlug): int
    {
        $st = $this->db->prepare("SELECT id FROM $table WHERE name = ?");
        $st->execute([$name]);
        if ($id = $st->fetchColumn()) return (int)$id;
        if ($hasSlug) {
            $this->db->prepare("INSERT INTO $table (name, slug) VALUES (?, ?)")->execute([$name, $this->uniqueSlug($table, slugify($name))]);
        } else {
            $this->db->prepare("INSERT INTO $table (name) VALUES (?)")->execute([$name]);
        }
        return (int)$this->db->lastInsertId();
    }

    /**
     * Thêm mới ($id = null) hoặc cập nhật sản phẩm trong 1 transaction.
     * $d: dữ liệu đã validate. $authors/$tags: chuỗi phân cách bằng dấu phẩy.
     */
    public function save(?int $id, array $d, string $authors, string $tags): int
    {
        $this->db->beginTransaction();
        try {
            $slug = $this->uniqueSlug('products', slugify($d['slug'] !== '' ? $d['slug'] : $d['name']), $id ?? 0);
            $cols = [$d['category_id'], $d['publisher_id'], $d['type'], $d['name'], $slug, $d['description'],
                     $d['price'], $d['sale_price'], $d['stock'], $d['pages'], $d['publish_year'], $d['is_active']];
            if ($id === null) {
                $this->db->prepare("INSERT INTO products (category_id, publisher_id, type, name, slug, description, price, sale_price,
                                    stock, pages, publish_year, is_active, image) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                    ->execute([...$cols, $d['image']]);
                $id = (int)$this->db->lastInsertId();
            } else {
                $this->db->prepare("UPDATE products SET category_id=?, publisher_id=?, type=?, name=?, slug=?, description=?, price=?,
                                    sale_price=?, stock=?, pages=?, publish_year=?, is_active=? WHERE id=?")
                    ->execute([...$cols, $id]);
                if ($d['image']) $this->db->prepare("UPDATE products SET image = ? WHERE id = ?")->execute([$d['image'], $id]);
            }

            // Cập nhật quan hệ nhiều-nhiều: xóa hết rồi gắn lại
            $this->db->prepare("DELETE FROM product_authors WHERE product_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM product_tags WHERE product_id = ?")->execute([$id]);
            foreach ($this->splitList($authors) as $name) {
                $this->db->prepare("INSERT IGNORE INTO product_authors (product_id, author_id) VALUES (?, ?)")
                         ->execute([$id, $this->findOrCreate('authors', $name, true)]);
            }
            foreach ($this->splitList($tags) as $name) {
                $this->db->prepare("INSERT IGNORE INTO product_tags (product_id, tag_id) VALUES (?, ?)")
                         ->execute([$id, $this->findOrCreate('tags', $name, false)]);
            }
            $this->db->commit();
            return $id;
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    /**
     * Xóa sản phẩm. Nếu đã từng được mua (có trong order_items) thì chỉ ẩn (is_active = 0)
     * để không làm mất lịch sử đơn hàng. @return bool true nếu xóa hẳn, false nếu chỉ ẩn
     */
    public function delete(int $id): bool
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $st->execute([$id]);
        if ($st->fetchColumn() > 0) {
            $this->db->prepare("UPDATE products SET is_active = 0 WHERE id = ?")->execute([$id]);
            return false;
        }
        $this->db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        return true;
    }

    /**
     * Lưu ảnh upload vào public/assets/products. Kiểm tra: lỗi upload, dung lượng ≤ 2MB,
     * nội dung thực sự là ảnh (getimagesize, không tin phần đuôi file), tên file ngẫu nhiên.
     * @return string|null tên file đã lưu, null nếu không có file được chọn
     * @throws RuntimeException khi file không hợp lệ
     */
    public static function upload(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if ($file['error'] !== UPLOAD_ERR_OK)  throw new RuntimeException('Upload ảnh thất bại.');
        if ($file['size'] > 2 * 1024 * 1024)   throw new RuntimeException('Ảnh tối đa 2MB.');
        $info = @getimagesize($file['tmp_name']);
        $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
        if (!$ext) throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.');

        $dir = ROOT_PATH . '/public/assets/products';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) throw new RuntimeException('Không lưu được ảnh.');
        return $name;
    }
}
