<?php
/** Model Report: số liệu thống kê cho dashboard quản trị. */
class Report extends Model
{
    private function one(string $sql)  { return $this->db->query($sql)->fetchColumn(); }
    private function rows(string $sql) { return $this->db->query($sql)->fetchAll(); }

    public function dashboard(): array
    {
        return [
            // Doanh thu tính trên đơn đã hoàn thành
            'revenue'      => (int)$this->one("SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'completed'"),
            'orders'       => (int)$this->one("SELECT COUNT(*) FROM orders"),
            'pending'      => (int)$this->one("SELECT COUNT(*) FROM orders WHERE status = 'pending'"),
            'customers'    => (int)$this->one("SELECT COUNT(*) FROM users WHERE role = 'customer'"),
            'products'     => (int)$this->one("SELECT COUNT(*) FROM products WHERE is_active = 1"),
            'byStatus'     => $this->rows("SELECT status, COUNT(*) AS n FROM orders GROUP BY status"),
            'monthly'      => $this->rows("SELECT DATE_FORMAT(created_at, '%m/%Y') AS month, COUNT(*) AS n, SUM(total) AS revenue
                                           FROM orders WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                                           GROUP BY DATE_FORMAT(created_at, '%Y-%m'), month ORDER BY DATE_FORMAT(created_at, '%Y-%m')"),
            'lowStock'     => $this->rows("SELECT id, name, stock FROM products WHERE is_active = 1 AND stock <= 10 ORDER BY stock LIMIT 8"),
            'topProducts'  => $this->rows("SELECT id, name, sold_count FROM products ORDER BY sold_count DESC LIMIT 5"),
            'recentOrders' => $this->rows("SELECT o.id, o.order_code, o.total, o.status, u.full_name
                                           FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.id DESC LIMIT 5"),
        ];
    }
}
