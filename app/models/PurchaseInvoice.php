<?php
/**
 * Model PurchaseInvoice: hóa đơn nhập hàng từ nhà cung cấp.
 * Nghiệp vụ: lập hóa đơn => tồn kho (products.stock) TĂNG; hủy hóa đơn => tồn kho GIẢM lại.
 * Cả hai thao tác đều dùng TRANSACTION để dữ liệu luôn nhất quán (hoặc thành công hết, hoặc hoàn tác hết).
 */
class PurchaseInvoice extends Model
{
    /** Danh sách hóa đơn, lọc theo nhà cung cấp / từ khóa số hóa đơn / khoảng ngày. */
    public function search(array $f): array
    {
        $where = ['1=1']; $args = [];
        if (!empty($f['supplier_id'])) { $where[] = 'i.supplier_id = ?'; $args[] = (int)$f['supplier_id']; }
        if (!empty($f['q']))           { $where[] = 'i.invoice_no LIKE ?'; $args[] = '%' . $f['q'] . '%'; }
        if (!empty($f['from']))        { $where[] = 'i.invoice_date >= ?'; $args[] = $f['from']; }
        if (!empty($f['to']))          { $where[] = 'i.invoice_date <= ?'; $args[] = $f['to']; }
        $st = $this->db->prepare("SELECT i.*, s.name AS supplier_name FROM purchase_invoices i
                                  JOIN suppliers s ON s.id = i.supplier_id
                                  WHERE " . implode(' AND ', $where) . " ORDER BY i.invoice_date DESC, i.id DESC");
        $st->execute($args);
        return $st->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT i.*, s.name AS supplier_name, u.full_name AS creator_name
                                  FROM purchase_invoices i JOIN suppliers s ON s.id = i.supplier_id
                                  JOIN users u ON u.id = i.created_by WHERE i.id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function items(int $invoiceId): array
    {
        $st = $this->db->prepare("SELECT it.*, p.name AS product_name FROM purchase_invoice_items it
                                  JOIN products p ON p.id = it.product_id WHERE it.invoice_id = ?");
        $st->execute([$invoiceId]);
        return $st->fetchAll();
    }

    /** Danh sách sản phẩm để chọn trong form nhập hàng. */
    public function productOptions(): array
    {
        return $this->db->query("SELECT id, name, stock FROM products ORDER BY name")->fetchAll();
    }

    /**
     * Lập hóa đơn nhập + cộng tồn kho.
     * @param array $d     supplier_id, created_by, invoice_no, invoice_date, note, file_path, file_name
     * @param array $items mỗi phần tử: product_id, quantity, unit_cost
     * @return string|null thông báo lỗi, hoặc null nếu thành công
     */
    public function create(array $d, array $items): ?string
    {
        $total = array_sum(array_map(fn($i) => $i['quantity'] * $i['unit_cost'], $items));
        try {
            $this->db->beginTransaction();
            $this->db->prepare("INSERT INTO purchase_invoices (supplier_id, created_by, invoice_no, invoice_date, total, note, file_path, file_name)
                                VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$d['supplier_id'], $d['created_by'], $d['invoice_no'], $d['invoice_date'], $total, $d['note'], $d['file_path'], $d['file_name']]);
            $id = (int)$this->db->lastInsertId();

            $ins = $this->db->prepare("INSERT INTO purchase_invoice_items (invoice_id, product_id, quantity, unit_cost) VALUES (?,?,?,?)");
            $stock = $this->db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");   // nhập kho
            foreach ($items as $i) {
                $ins->execute([$id, $i['product_id'], $i['quantity'], $i['unit_cost']]);
                $stock->execute([$i['quantity'], $i['product_id']]);
            }
            $this->db->commit();
            return null;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return $e->getCode() === '23000' ? 'Nhà cung cấp này đã có hóa đơn với số ' . $d['invoice_no'] . '.' : 'Không lưu được hóa đơn, vui lòng thử lại.';
        }
    }

    /** Hủy hóa đơn: trừ lại tồn kho. Từ chối nếu hàng đã bán bớt khiến tồn kho không đủ để trừ. */
    public function cancel(int $id): ?string
    {
        $this->db->beginTransaction();
        $st = $this->db->prepare("SELECT status FROM purchase_invoices WHERE id = ? FOR UPDATE");
        $st->execute([$id]);
        if ($st->fetchColumn() !== 'received') { $this->db->rollBack(); return 'Hóa đơn không ở trạng thái có thể hủy.'; }

        $dec = $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        foreach ($this->items($id) as $it) {
            $dec->execute([$it['quantity'], $it['product_id'], $it['quantity']]);
            if ($dec->rowCount() === 0) {
                $this->db->rollBack();
                return 'Không thể hủy: tồn kho của "' . $it['product_name'] . '" không đủ để trừ lại (hàng đã bán).';
            }
        }
        $this->db->prepare("UPDATE purchase_invoices SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        $this->db->commit();
        return null;
    }

    public function setFile(int $id, string $path, string $name): void
    {
        $this->db->prepare("UPDATE purchase_invoices SET file_path = ?, file_name = ? WHERE id = ?")->execute([$path, $name, $id]);
    }
}
