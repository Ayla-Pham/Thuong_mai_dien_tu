<?php
$v = fn(string $k, $def = '') => old($k, (string)($c[$k] ?? $def));
$type = $_SESSION['old']['discount_type'] ?? $c['discount_type'] ?? 'percent';
?>
<h1 class="h4 mb-3"><?= e($title) ?></h1>
<form method="post" action="<?= $c ? url('/admin/ma-giam-gia/' . $c['id']) : url('/admin/ma-giam-gia') ?>" class="row g-3 bg-white p-3 rounded border" style="max-width:800px">
    <?= csrf_field() ?>
    <div class="col-md-4"><label class="form-label">Mã *</label><input name="code" class="form-control text-uppercase" required maxlength="30" value="<?= $v('code') ?>"></div>
    <div class="col-md-4"><label class="form-label">Loại giảm</label>
        <select name="discount_type" class="form-select">
            <option value="percent" <?= $type === 'percent' ? 'selected' : '' ?>>Theo %</option>
            <option value="fixed" <?= $type === 'fixed' ? 'selected' : '' ?>>Số tiền cố định</option>
        </select></div>
    <div class="col-md-4"><label class="form-label">Giá trị giảm * <small class="text-muted">(% hoặc đ)</small></label><input type="number" min="1" name="discount_value" class="form-control" required value="<?= $v('discount_value') ?>"></div>
    <div class="col-md-4"><label class="form-label">Giảm tối đa (đ) <small class="text-muted">chỉ loại %</small></label><input type="number" min="1" name="max_discount" class="form-control" value="<?= $v('max_discount') ?>"></div>
    <div class="col-md-4"><label class="form-label">Đơn tối thiểu (đ)</label><input type="number" min="0" name="min_order" class="form-control" value="<?= $v('min_order', '0') ?>"></div>
    <div class="col-md-4"><label class="form-label">Giới hạn lượt dùng</label><input type="number" min="1" name="usage_limit" class="form-control" placeholder="Không giới hạn" value="<?= $v('usage_limit') ?>"></div>
    <div class="col-md-4"><label class="form-label">Từ ngày</label><input type="date" name="start_date" class="form-control" value="<?= $v('start_date') ?>"></div>
    <div class="col-md-4"><label class="form-label">Đến ngày</label><input type="date" name="end_date" class="form-control" value="<?= $v('end_date') ?>"></div>
    <div class="col-md-4 d-flex align-items-end"><div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" id="act" value="1" <?= (!$c && !isset($_SESSION['old'])) || ($_SESSION['old']['is_active'] ?? $c['is_active'] ?? 0) ? 'checked' : '' ?>>
        <label class="form-check-label" for="act">Đang bật</label></div></div>
    <div class="col-12"><button class="btn btn-primary">Lưu</button> <a href="<?= url('/admin/ma-giam-gia') ?>" class="btn btn-link">Hủy</a></div>
</form>
