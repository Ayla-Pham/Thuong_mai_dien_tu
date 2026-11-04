<?php
/** Quản lý mã giảm giá (marketing). */
class AdminCouponController extends AdminController
{
    public function index(): void
    {
        $this->view('admin/coupons/index', ['title' => 'Mã giảm giá', 'coupons' => (new Coupon())->all()]);
    }

    public function create(): void { $this->form(null); }

    public function edit(string $id): void
    {
        $c = (new Coupon())->find((int)$id);
        if (!$c) { flash('danger', 'Không tìm thấy mã giảm giá.'); redirect('/admin/ma-giam-gia'); }
        $this->form($c);
    }

    private function form(?array $c): void
    {
        $this->view('admin/coupons/form', ['title' => $c ? 'Sửa mã giảm giá' : 'Thêm mã giảm giá', 'c' => $c]);
    }

    public function store(): void { $this->save(null); }
    public function update(string $id): void { $this->save((int)$id); }

    private function save(?int $id): void
    {
        csrf_verify();
        $n = fn($k) => ($_POST[$k] ?? '') === '' ? null : (int)$_POST[$k];
        $date = fn($k) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST[$k] ?? '') ? $_POST[$k] : null;
        $d = [
            'code'           => strtoupper(trim($_POST['code'] ?? '')),
            'discount_type'  => $_POST['discount_type'] ?? '',
            'discount_value' => (int)($_POST['discount_value'] ?? 0),
            'max_discount'   => $n('max_discount'),
            'min_order'      => (int)($_POST['min_order'] ?? 0),
            'usage_limit'    => $n('usage_limit'),
            'start_date'     => $date('start_date'),
            'end_date'       => $date('end_date'),
            'is_active'      => isset($_POST['is_active']) ? 1 : 0,
        ];
        $_SESSION['old'] = array_map(fn($v) => (string)$v, $_POST);
        $back = $id ? "/admin/ma-giam-gia/$id/sua" : '/admin/ma-giam-gia/them';

        $errors = [];
        if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $d['code']))                $errors[] = 'Mã gồm 3–30 ký tự chữ, số, gạch ngang/dưới.';
        if (!in_array($d['discount_type'], ['percent', 'fixed'], true))      $errors[] = 'Loại giảm giá không hợp lệ.';
        if ($d['discount_value'] < 1)                                        $errors[] = 'Giá trị giảm phải lớn hơn 0.';
        if ($d['discount_type'] === 'percent' && $d['discount_value'] > 100) $errors[] = 'Giảm theo % tối đa 100.';
        if ($d['min_order'] < 0 || ($d['usage_limit'] !== null && $d['usage_limit'] < 1)) $errors[] = 'Đơn tối thiểu / giới hạn lượt dùng không hợp lệ.';
        if ($d['start_date'] && $d['end_date'] && $d['start_date'] > $d['end_date']) $errors[] = 'Ngày bắt đầu phải trước ngày kết thúc.';
        if ($d['discount_type'] === 'fixed') $d['max_discount'] = null;      // trần giảm chỉ áp dụng cho loại %

        if (!$errors && !(new Coupon())->save($id, $d)) $errors[] = 'Mã này đã tồn tại.';
        if ($errors) { foreach ($errors as $er) flash('danger', $er); redirect($back); }

        unset($_SESSION['old']);
        flash('success', $id ? 'Đã cập nhật mã giảm giá.' : 'Đã thêm mã giảm giá.');
        redirect('/admin/ma-giam-gia');
    }

    public function delete(string $id): void
    {
        csrf_verify();
        flash('success', (new Coupon())->delete((int)$id) ? 'Đã xóa mã giảm giá.' : 'Mã đã được dùng trong đơn hàng nên chỉ bị tắt, không xóa.');
        redirect('/admin/ma-giam-gia');
    }
}
