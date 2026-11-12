<?php
/** Model Coupon: kiểm tra và tính tiền giảm của mã giảm giá. */
class Coupon extends Model
{
    public function findByCode(string $code, bool $forUpdate = false): ?array
    {
        $st = $this->db->prepare("SELECT * FROM coupons WHERE code = ?" . ($forUpdate ? " FOR UPDATE" : ""));
        $st->execute([strtoupper(trim($code))]);
        return $st->fetch() ?: null;
    }

    /**
     * Danh sách voucher khách có thể xem ở giỏ hàng: chỉ gồm mã đang bật, trong thời hạn, còn lượt dùng.
     * Mỗi mã kèm: 'discount' (số tiền giảm với giỏ hiện tại, hoặc mức giảm khi đủ điều kiện),
     * 'usable' (đủ điều kiện đơn tối thiểu?), 'missing' (cần mua thêm bao nhiêu), 'best' (giảm nhiều nhất).
     * Sắp xếp: mã dùng được lên trước, giảm nhiều nhất trước.
     */
    public function available(int $subtotal): array
    {
        $rows = $this->db->query(
            "SELECT * FROM coupons
             WHERE is_active = 1
               AND (start_date IS NULL OR start_date <= CURDATE())
               AND (end_date IS NULL OR end_date >= CURDATE())
               AND (usage_limit IS NULL OR used_count < usage_limit)")->fetchAll();
        $list = [];
        foreach ($rows as $c) {
            $c['usable']   = $subtotal >= (int)$c['min_order'];
            // Nếu chưa đủ điều kiện, tính mức giảm tại ngưỡng tối thiểu để khách biết mình sẽ được gì
            $c['discount'] = self::evaluate($c, max($subtotal, (int)$c['min_order']))[0];
            $c['missing']  = $c['usable'] ? 0 : (int)$c['min_order'] - $subtotal;
            $c['best']     = false;
            $list[] = $c;
        }
        usort($list, fn($a, $b) => [$b['usable'], $b['discount']] <=> [$a['usable'], $a['discount']]);
        if ($list && $list[0]['usable'] && $list[0]['discount'] > 0) $list[0]['best'] = true;
        return $list;
    }

    /** Mô tả ngắn ưu đãi, vd "Giảm 10% (tối đa 30.000đ)". */
    public static function describe(array $c): string
    {
        if ($c['discount_type'] === 'percent') {
            return 'Giảm ' . (int)$c['discount_value'] . '%' . ($c['max_discount'] !== null ? ' (tối đa ' . money($c['max_discount']) . ')' : '');
        }
        return 'Giảm ' . money($c['discount_value']);
    }

    /* ===================== DÀNH CHO ADMIN ===================== */

    public function all(): array { return $this->db->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll(); }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM coupons WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** Thêm/sửa mã. @return bool false nếu trùng mã (UNIQUE) */
    public function save(?int $id, array $d): bool
    {
        $dup = $this->db->prepare("SELECT COUNT(*) FROM coupons WHERE code = ? AND id <> ?");
        $dup->execute([$d['code'], $id ?? 0]);
        if ($dup->fetchColumn()) return false;

        $v = [$d['code'], $d['discount_type'], $d['discount_value'], $d['max_discount'], $d['min_order'],
              $d['usage_limit'], $d['start_date'], $d['end_date'], $d['is_active']];
        if ($id === null) {
            $this->db->prepare("INSERT INTO coupons (code, discount_type, discount_value, max_discount, min_order, usage_limit, start_date, end_date, is_active)
                                VALUES (?,?,?,?,?,?,?,?,?)")->execute($v);
        } else {
            $this->db->prepare("UPDATE coupons SET code=?, discount_type=?, discount_value=?, max_discount=?, min_order=?, usage_limit=?,
                                start_date=?, end_date=?, is_active=? WHERE id=?")->execute([...$v, $id]);
        }
        return true;
    }

    /** Mã đã dùng trong đơn thì chỉ tắt (is_active=0) để giữ lịch sử; chưa dùng thì xóa hẳn. @return bool true nếu xóa hẳn */
    public function delete(int $id): bool
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE coupon_id = ?");
        $st->execute([$id]);
        if ($st->fetchColumn() > 0) {
            $this->db->prepare("UPDATE coupons SET is_active = 0 WHERE id = ?")->execute([$id]);
            return false;
        }
        $this->db->prepare("DELETE FROM coupons WHERE id = ?")->execute([$id]);
        return true;
    }

    /**
     * Đánh giá một mã với giá trị đơn hàng.
     * @return array [số tiền giảm, thông báo lỗi|null]
     */
    public static function evaluate(?array $c, int $subtotal): array
    {
        if (!$c || !$c['is_active'])                                    return [0, 'Mã giảm giá không tồn tại hoặc đã bị tắt.'];
        $today = date('Y-m-d');
        if ($c['start_date'] && $today < $c['start_date'])              return [0, 'Mã giảm giá chưa đến thời gian áp dụng.'];
        if ($c['end_date'] && $today > $c['end_date'])                  return [0, 'Mã giảm giá đã hết hạn.'];
        if ($c['usage_limit'] !== null && $c['used_count'] >= $c['usage_limit']) return [0, 'Mã giảm giá đã hết lượt sử dụng.'];
        if ($subtotal < (int)$c['min_order'])                           return [0, 'Đơn hàng tối thiểu ' . money($c['min_order']) . ' để dùng mã này.'];

        if ($c['discount_type'] === 'percent') {
            $d = (int)floor($subtotal * $c['discount_value'] / 100);
            if ($c['max_discount'] !== null) $d = min($d, (int)$c['max_discount']);
        } else {
            $d = (int)$c['discount_value'];
        }
        return [min($d, $subtotal), null];
    }
}
