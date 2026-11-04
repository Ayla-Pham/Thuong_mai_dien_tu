<h1 class="h4 mb-3"><?= e($title) ?></h1>
<form method="post" action="<?= $c ? url('/admin/danh-muc/' . $c['id']) : url('/admin/danh-muc') ?>" class="bg-white p-3 rounded border" style="max-width:600px">
    <?= csrf_field() ?>
    <label class="form-label">Tên danh mục *</label>
    <input name="name" class="form-control mb-3" required maxlength="100" value="<?= old('name', $c['name'] ?? '') ?>">
    <label class="form-label">Danh mục cha</label>
    <select name="parent_id" class="form-select mb-3">
        <option value="">-- Danh mục gốc --</option>
        <?php foreach ($parents as $pr): ?>
            <option value="<?= (int)$pr['id'] ?>" <?= (string)($_SESSION['old']['parent_id'] ?? $c['parent_id'] ?? '') === (string)$pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <label class="form-label">Mô tả</label>
    <input name="description" class="form-control mb-3" maxlength="255" value="<?= old('description', $c['description'] ?? '') ?>">
    <button class="btn btn-primary">Lưu</button> <a href="<?= url('/admin/danh-muc') ?>" class="btn btn-link">Hủy</a>
</form>
