<?php
/** Model Category + dữ liệu cho bộ lọc (tác giả, nhà xuất bản). */
class Category extends Model
{
    /** Danh mục gốc kèm danh mục con (dùng cho menu và sidebar). */
    public function tree(): array
    {
        $all = $this->db->query("SELECT * FROM categories ORDER BY id")->fetchAll();
        $roots = [];
        foreach ($all as $c) {
            if ($c['parent_id'] === null) { $c['children'] = []; $roots[$c['id']] = $c; }
        }
        foreach ($all as $c) {
            if ($c['parent_id'] !== null && isset($roots[$c['parent_id']])) $roots[$c['parent_id']]['children'][] = $c;
        }
        return array_values($roots);
    }

    /* ===================== DÀNH CHO ADMIN ===================== */

    /** Mọi danh mục kèm tên danh mục cha và số sản phẩm. */
    public function allWithCounts(): array
    {
        return $this->db->query("SELECT c.*, par.name AS parent_name, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
                                 FROM categories c LEFT JOIN categories par ON par.id = c.parent_id ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.id")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** Thêm/sửa danh mục; slug luôn duy nhất. Chặn chọn chính nó làm cha. */
    public function save(?int $id, string $name, ?int $parentId, ?string $desc): void
    {
        if ($id !== null && $parentId === $id) $parentId = null;
        $base = slugify($name); $slug = $base; $i = 2;
        $chk = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE slug = ? AND id <> ?");
        while (true) { $chk->execute([$slug, $id ?? 0]); if (!$chk->fetchColumn()) break; $slug = $base . '-' . $i++; }

        if ($id === null) {
            $this->db->prepare("INSERT INTO categories (parent_id, name, slug, description) VALUES (?,?,?,?)")->execute([$parentId, $name, $slug, $desc]);
        } else {
            $this->db->prepare("UPDATE categories SET parent_id=?, name=?, slug=?, description=? WHERE id=?")->execute([$parentId, $name, $slug, $desc, $id]);
        }
    }

    /** Chỉ xóa khi không còn sản phẩm; danh mục con sẽ thành danh mục gốc (ON DELETE SET NULL). */
    public function delete(int $id): bool
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $st->execute([$id]);
        if ($st->fetchColumn() > 0) return false;
        $this->db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        return true;
    }

    public function authors(): array   { return $this->db->query("SELECT * FROM authors ORDER BY name")->fetchAll(); }
    public function publishers(): array { return $this->db->query("SELECT * FROM publishers ORDER BY name")->fetchAll(); }
}
