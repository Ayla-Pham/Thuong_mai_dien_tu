<?php $v = fn(string $k) => old($k, (string)($s[$k] ?? '')); ?>
<h1 class="h4 mb-3"><?= e($title) ?></h1>
<form method="post" action="<?= $s ? url('/admin/nha-cung-cap/' . $s['id']) : url('/admin/nha-cung-cap') ?>" class="row g-3 bg-white p-3 rounded border" style="max-width:800px">
    <?= csrf_field() ?>
    <div class="col-md-8"><label class="form-label">Tên nhà cung cấp *</label><input name="name" class="form-control" required maxlength="150" value="<?= $v('name') ?>"></div>
    <div class="col-md-4"><label class="form-label">Mã số thuế</label><input name="tax_code" class="form-control" maxlength="20" value="<?= $v('tax_code') ?>"></div>
    <div class="col-md-6"><label class="form-label">Điện thoại</label><input name="phone" class="form-control" maxlength="20" value="<?= $v('phone') ?>"></div>
    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" maxlength="150" value="<?= $v('email') ?>"></div>
    <div class="col-12"><label class="form-label">Địa chỉ</label><input name="address" class="form-control" maxlength="255" value="<?= $v('address') ?>"></div>
    <div class="col-12"><div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" id="act" value="1" <?= (!$s && !isset($_SESSION['old'])) || ($_SESSION['old']['is_active'] ?? $s['is_active'] ?? 0) ? 'checked' : '' ?>>
        <label class="form-check-label" for="act">Đang hợp tác</label></div></div>
    <div class="col-12"><button class="btn btn-primary">Lưu</button> <a href="<?= url('/admin/nha-cung-cap') ?>" class="btn btn-link">Hủy</a></div>
</form>
