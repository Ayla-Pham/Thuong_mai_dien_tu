<?php
/** Model Supplier: nhà cung cấp (nơi nhập sách / văn phòng phẩm). */
class Supplier extends Model
{
    public function all(bool $onlyActive = false): array
    {
        $sql = "SELECT s.*, (SELECT COUNT(*) FROM purchase_invoices i WHERE i.supplier_id = s.id) AS invoice_count
                FROM suppliers s" . ($onlyActive ? " WHERE s.is_active = 1" : "") . " ORDER BY s.name";
        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** Thêm (id = null) hoặc sửa nhà cung cấp. */
    public function save(?int $id, array $d): void
    {
        $v = [$d['name'], $d['phone'], $d['email'], $d['address'], $d['tax_code'], $d['is_active']];
        if ($id === null) {
            $this->db->prepare("INSERT INTO suppliers (name, phone, email, address, tax_code, is_active) VALUES (?,?,?,?,?,?)")->execute($v);
        } else {
            $this->db->prepare("UPDATE suppliers SET name=?, phone=?, email=?, address=?, tax_code=?, is_active=? WHERE id=?")->execute([...$v, $id]);
        }
    }

    /** Đã có hóa đơn nhập thì chỉ ngừng hợp tác (is_active=0) để giữ lịch sử; chưa có thì xóa hẳn. @return bool true nếu xóa hẳn */
    public function delete(int $id): bool
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM purchase_invoices WHERE supplier_id = ?");
        $st->execute([$id]);
        if ($st->fetchColumn() > 0) {
            $this->db->prepare("UPDATE suppliers SET is_active = 0 WHERE id = ?")->execute([$id]);
            return false;
        }
        $this->db->prepare("DELETE FROM suppliers WHERE id = ?")->execute([$id]);
        return true;
    }
}
