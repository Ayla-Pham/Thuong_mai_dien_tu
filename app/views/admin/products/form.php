<?php
// Giá trị mặc định lấy từ sản phẩm đang sửa; nếu vừa validate lỗi thì ưu tiên dữ liệu đã nhập (old)
$v = fn(string $k, $def = '') => old($k, (string)($p[$k] ?? $def));
$action = $p ? url('/admin/san-pham/' . $p['id']) : url('/admin/san-pham');
$sel = fn($k, $val, $def = '') => ($_SESSION['old'][$k] ?? ($p[$k] ?? $def)) == $val ? 'selected' : '';
?>
<h1 class="h4 mb-3"><?= e($title) ?></h1>
<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="row g-3 bg-white p-3 rounded border">
    <?= csrf_field() ?>
    <div class="col-md-8"><label class="form-label">Tên sản phẩm *</label>
        <input name="name" class="form-control" required maxlength="200" value="<?= $v('name') ?>"></div>
    <div class="col-md-4"><label class="form-label">Slug (URL) <small class="text-muted">– để trống sẽ tự tạo</small></label>
        <input name="slug" class="form-control" maxlength="220" value="<?= $v('slug') ?>"></div>

    <div class="col-md-4"><label class="form-label">Danh mục *</label>
        <select name="category_id" class="form-select" required><option value="">-- Chọn --</option>
            <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $sel('category_id', $c['id']) ?>><?= e($c['label']) ?></option><?php endforeach; ?>
        </select></div>
    <div class="col-md-4"><label class="form-label">Nhà xuất bản</label>
        <select name="publisher_id" class="form-select"><option value="">-- Không có --</option>
            <?php foreach ($publishers as $pb): ?><option value="<?= (int)$pb['id'] ?>" <?= $sel('publisher_id', $pb['id']) ?>><?= e($pb['name']) ?></option><?php endforeach; ?>
        </select></div>
    <div class="col-md-4"><label class="form-label">Loại</label>
        <select name="type" class="form-select">
            <option value="book" <?= $sel('type', 'book', 'book') ?>>Sách</option>
            <option value="stationery" <?= $sel('type', 'stationery', 'book') ?>>Văn phòng phẩm</option>
        </select></div>

    <div class="col-md-3"><label class="form-label">Giá gốc (đ) *</label><input type="number" min="1" name="price" class="form-control" required value="<?= $v('price') ?>"></div>
    <div class="col-md-3"><label class="form-label">Giá khuyến mãi (đ)</label><input type="number" min="1" name="sale_price" class="form-control" value="<?= $v('sale_price') ?>"></div>
    <div class="col-md-2"><label class="form-label">Tồn kho *</label><input type="number" min="0" name="stock" class="form-control" required value="<?= $v('stock', '0') ?>"></div>
    <div class="col-md-2"><label class="form-label">Số trang</label><input type="number" min="1" name="pages" class="form-control" value="<?= $v('pages') ?>"></div>
    <div class="col-md-2"><label class="form-label">Năm XB</label><input type="number" min="1900" max="2100" name="publish_year" class="form-control" value="<?= $v('publish_year') ?>"></div>

    <div class="col-md-6"><label class="form-label">Tác giả <small class="text-muted">– cách nhau bằng dấu phẩy, tác giả mới sẽ tự được tạo</small></label>
        <input name="authors" class="form-control" value="<?= old('authors', $p['authors_text'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Tag <small class="text-muted">– dùng cho gợi ý "sản phẩm liên quan"</small></label>
        <input name="tags" class="form-control" value="<?= old('tags', $p['tags_text'] ?? '') ?>"></div>

    <div class="col-12"><label class="form-label">Mô tả</label><textarea name="description" rows="4" class="form-control"><?= $v('description') ?></textarea></div>

    <div class="col-md-6"><label class="form-label">Ảnh (JPG/PNG/WEBP, ≤ 2MB)</label>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control">
        <?php if ($p && $p['image']): ?><img src="<?= e(product_image($p)) ?>" height="80" class="mt-2 rounded border" alt=""><?php endif; ?></div>
    <div class="col-md-6 d-flex align-items-end"><div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" id="act" value="1" <?= (!$p && !isset($_SESSION['old'])) || ($_SESSION['old']['is_active'] ?? $p['is_active'] ?? 0) ? 'checked' : '' ?>>
        <label class="form-check-label" for="act">Hiển thị trên cửa hàng</label></div></div>

    <div class="col-12"><button class="btn btn-primary">Lưu</button> <a href="<?= url('/admin/san-pham') ?>" class="btn btn-link">Hủy</a></div>
</form>
