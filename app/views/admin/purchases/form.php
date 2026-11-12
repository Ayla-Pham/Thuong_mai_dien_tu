<?php
$oldItems = $_SESSION['old_items'] ?? [['product_id' => 0, 'quantity' => 1, 'unit_cost' => 0]];
unset($_SESSION['old_items']);
?>
<h1 class="h4 mb-3"><?= e($title) ?></h1>
<?php if (!$suppliers): ?>
    <div class="alert alert-warning">Chưa có nhà cung cấp nào. <a href="<?= url('/admin/nha-cung-cap/them') ?>">Thêm nhà cung cấp</a> trước.</div>
<?php else: ?>
<form method="post" action="<?= url('/admin/hoa-don-nhap') ?>" enctype="multipart/form-data" class="bg-white p-3 rounded border" style="max-width:950px">
    <?= csrf_field() ?>
    <div class="row g-3 mb-3">
        <div class="col-md-5"><label class="form-label">Nhà cung cấp *</label>
            <select name="supplier_id" class="form-select" required>
                <option value="">-- Chọn --</option>
                <?php foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>" <?= old('supplier_id') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label">Số hóa đơn *</label><input name="invoice_no" class="form-control" required maxlength="50" value="<?= old('invoice_no') ?>"></div>
        <div class="col-md-3"><label class="form-label">Ngày hóa đơn *</label><input type="date" name="invoice_date" class="form-control" required max="<?= date('Y-m-d') ?>" value="<?= old('invoice_date', date('Y-m-d')) ?>"></div>
        <div class="col-md-8"><label class="form-label">Ghi chú</label><input name="note" class="form-control" maxlength="255" value="<?= old('note') ?>"></div>
        <div class="col-md-4"><label class="form-label">File hóa đơn <small class="text-muted">(≤ 5MB)</small></label>
            <input type="file" name="invoice_file" id="invoice_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx,.csv">
            <div class="form-text">Chọn file <strong>XLSX/CSV</strong> để hệ thống tự điền nhà cung cấp, số hóa đơn, ngày và hàng nhập.
                <a href="<?= url('/admin/hoa-don-nhap/mau') ?>">Tải file mẫu</a>. PDF/ảnh/Word chỉ được lưu kèm.</div></div>
    </div>
    <div id="import-msg" class="mb-3"></div>

    <h2 class="h6">Hàng nhập</h2>
    <table class="table table-sm align-middle" id="items">
        <thead><tr><th>Sản phẩm</th><th style="width:110px">Số lượng</th><th style="width:150px">Giá nhập (đ)</th><th style="width:140px" class="text-end">Thành tiền</th><th style="width:40px"></th></tr></thead>
        <tbody>
        <?php foreach ($oldItems as $it): ?>
            <tr>
                <td><select name="product_id[]" class="form-select form-select-sm">
                    <option value="0">-- Chọn sản phẩm --</option>
                    <?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>" <?= $it['product_id'] == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?> (tồn <?= (int)$p['stock'] ?>)</option><?php endforeach; ?>
                </select></td>
                <td><input type="number" name="qty[]" min="1" class="form-control form-control-sm qty" value="<?= (int)$it['quantity'] ?>"></td>
                <td><input type="number" name="cost[]" min="0" class="form-control form-control-sm cost" value="<?= (int)$it['unit_cost'] ?>"></td>
                <td class="text-end line">0đ</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger rm">✕</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="3" class="text-end fw-bold">Tổng cộng</td><td class="text-end fw-bold text-danger" id="total">0đ</td><td></td></tr></tfoot>
    </table>
    <button type="button" id="add" class="btn btn-sm btn-outline-secondary mb-3">+ Thêm dòng</button>
    <div><button class="btn btn-primary">Lưu &amp; nhập kho</button> <a href="<?= url('/admin/hoa-don-nhap') ?>" class="btn btn-link">Hủy</a></div>
</form>

<script>
// Thêm/xóa dòng hàng nhập và tính thành tiền, tổng tiền ngay trên trình duyệt (server vẫn tự tính lại khi lưu).
const tbody = document.querySelector('#items tbody');
const fmt = n => n.toLocaleString('vi-VN') + 'đ';
function recalc() {
    let total = 0;
    tbody.querySelectorAll('tr').forEach(tr => {
        const v = (+tr.querySelector('.qty').value || 0) * (+tr.querySelector('.cost').value || 0);
        tr.querySelector('.line').textContent = fmt(v); total += v;
    });
    document.getElementById('total').textContent = fmt(total);
}
document.getElementById('add').onclick = () => {
    const row = tbody.rows[0].cloneNode(true);
    row.querySelector('select').value = '0'; row.querySelector('.qty').value = 1; row.querySelector('.cost').value = 0;
    tbody.appendChild(row); recalc();
};
tbody.addEventListener('input', recalc);
tbody.addEventListener('click', e => {
    if (e.target.classList.contains('rm') && tbody.rows.length > 1) { e.target.closest('tr').remove(); recalc(); }
});
recalc();

// ---- Tự điền hàng nhập từ file XLSX/CSV: gửi file lên server để đọc, KHÔNG lưu gì cho tới khi bấm "Lưu & nhập kho" ----
const msg = document.getElementById('import-msg');
const esc = s => s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function showMsg(type, html) { msg.innerHTML = '<div class="alert alert-' + type + ' py-2 mb-0 small">' + html + '</div>'; }
document.getElementById('invoice_file').addEventListener('change', async function () {
    const file = this.files[0];
    if (!file || !/\.(xlsx|csv)$/i.test(file.name)) { msg.innerHTML = ''; return; }
    showMsg('info', 'Đang đọc file...');
    const fd = new FormData();
    fd.append('_csrf', document.querySelector('input[name=_csrf]').value);
    fd.append('invoice_file', file);
    try {
        const res = await (await fetch('<?= url('/admin/hoa-don-nhap/doc-file') ?>', { method: 'POST', body: fd })).json();
        if (res.fatal) { showMsg('danger', esc(res.fatal)); return; }
        // Thông tin chung của hóa đơn: nhà cung cấp, số hóa đơn, ngày (chỉ điền khi file có / hệ thống sinh được)
        const inv = res.invoice || {}, filled = [];
        const setField = (name, val) => { if (val) { document.querySelector('[name=' + name + ']').value = val; return true; } return false; };
        if (setField('supplier_id', inv.supplier_id)) filled.push('nhà cung cấp <strong>' + esc(inv.supplier_name) + '</strong>');
        if (setField('invoice_date', inv.invoice_date)) filled.push('ngày <strong>' + esc(inv.invoice_date.split('-').reverse().join('/')) + '</strong>');
        if (setField('invoice_no', inv.invoice_no)) filled.push('số hóa đơn <strong>' + esc(inv.invoice_no) + '</strong>' + (inv.generated ? ' (hệ thống tự sinh)' : ''));
        if (!res.items.length) { showMsg('warning', 'Không có dòng hàng hợp lệ nào.' + (res.errors.length ? '<br>' + res.errors.map(esc).join('<br>') : '')); return; }
        // Xóa các dòng cũ rồi tạo lại từ dòng mẫu (dòng đầu)
        const tpl = tbody.rows[0].cloneNode(true);
        tbody.innerHTML = '';
        res.items.forEach(it => {
            const row = tpl.cloneNode(true);
            row.querySelector('select').value = String(it.product_id);
            row.querySelector('.qty').value = it.quantity;
            row.querySelector('.cost').value = it.unit_cost;
            tbody.appendChild(row);
        });
        recalc();
        let html = '✅ Đã điền <strong>' + res.items.length + '</strong> sản phẩm' + (filled.length ? ', ' + filled.join(', ') : '') + ' từ file. Hãy kiểm tra lại trước khi lưu.';
        if (res.errors.length) html += '<br><span class="text-danger">Bỏ qua ' + res.errors.length + ' dòng:</span><br>' + res.errors.map(esc).join('<br>');
        showMsg(res.errors.length ? 'warning' : 'success', html);
    } catch (e) { showMsg('danger', 'Không đọc được file, vui lòng thử lại.'); }
});
</script>
<?php endif; ?>
