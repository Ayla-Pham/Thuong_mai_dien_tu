<?php
/** Model User: tài khoản khách hàng / admin. Mật khẩu luôn được hash bằng bcrypt. */
class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        $st = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $st->execute([$email]);
        return $st->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** Tạo tài khoản khách hàng mới; trả về id. password_hash() tự sinh salt. */
    public function create(string $name, string $email, string $password, ?string $phone): int
    {
        $st = $this->db->prepare("INSERT INTO users (full_name, email, password, phone) VALUES (?, ?, ?, ?)");
        $st->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), $phone]);
        return (int)$this->db->lastInsertId();
    }

    /** Danh sách khách hàng kèm số đơn và tổng chi tiêu (đơn không bị hủy). */
    public function customers(): array
    {
        return $this->db->query("SELECT u.*, COUNT(o.id) AS order_count, COALESCE(SUM(o.total), 0) AS spent
                                 FROM users u LEFT JOIN orders o ON o.user_id = u.id AND o.status <> 'cancelled'
                                 WHERE u.role = 'customer' GROUP BY u.id ORDER BY u.id DESC")->fetchAll();
    }

    /** Khóa / mở khóa tài khoản khách (không áp dụng cho admin). */
    public function setActive(int $id, bool $active): void
    {
        $this->db->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'customer'")->execute([(int)$active, $id]);
    }

    public function updateProfile(int $id, string $name, ?string $phone, ?string $address): void
    {
        $st = $this->db->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
        $st->execute([$name, $phone, $address, $id]);
    }

    public function updatePassword(int $id, string $newPassword): void
    {
        $st = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $st->execute([password_hash($newPassword, PASSWORD_BCRYPT), $id]);
    }
}
