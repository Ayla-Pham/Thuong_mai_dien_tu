<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Hóa đơn nhập hàng</h1>
    <a href="<?= url('/admin/hoa-don-nhap/them') ?>" class="btn btn-primary btn-sm">+ Lập hóa đơn nhập</a>
</div>
<form method="get" class="row g-2 mb-3 bg-white p-2 rounded border">
    <div class="col-md-3"><select name="supplier" class="form-select form-select-sm">
        <option value="">Tất cả nhà cung cấp</option>
        <?php foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>" <?= $f['supplier_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-3"><input name="q" class="form-control form-control-sm" placeholder="Số hóa đơn" value="<?= e($f['q']) ?>"></div>
    <div class="col-md-2"><input type="date" name="from" class="form-control form-control-sm" value="<?= e($f['from']) ?>"></div>
    <div class="col-md-2"><input type="date" name="to" class="form-control form-control-sm" value="<?= e($f['to']) ?>"></div>
    <div class="col-md-2"><button class="btn btn-sm btn-outline-primary">Lọc</button> <a href="<?= url('/admin/hoa-don-nhap') ?>" class="btn btn-sm btn-link">Xóa lọc</a></div>
</form>
<div class="table-responsive"><table class="table table-sm table-hover align-middle bg-white">
    <thead><tr><th>Số HĐ</th><th>Ngày</th><th>Nhà cung cấp</th><th class="text-end">Tổng tiền</th><th>Trạng thái</th><th>File</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($invoices as $i): ?>
        <tr class="<?= $i['status'] === 'cancelled' ? 'text-muted' : '' ?>">
            <td><strong><?= e($i['invoice_no']) ?></strong></td>
            <td><?= date('d/m/Y', strtotime($i['invoice_date'])) ?></td>
            <td><?= e($i['supplier_name']) ?></td>
            <td class="text-end"><?= money($i['total']) ?></td>
            <td><?= $i['status'] === 'received' ? '<span class="badge text-bg-success">Đã nhập kho</span>' : '<span class="badge text-bg-secondary">Đã hủy</span>' ?></td>
            <td><?php if ($i['file_path']): ?><a href="<?= url('/admin/hoa-don-nhap/' . $i['id'] . '/tai-ve') ?>" class="btn btn-sm btn-outline-success py-0">⬇ Tải</a><?php else: ?><span class="small text-muted">—</span><?php endif; ?></td>
            <td class="text-end"><a href="<?= url('/admin/hoa-don-nhap/' . $i['id']) ?>" class="btn btn-sm btn-outline-primary">Xem</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$invoices): ?><tr><td colspan="7" class="text-center text-muted py-4">Chưa có hóa đơn nào.</td></tr><?php endif; ?>
    </tbody>
    <?php if ($invoices): ?><tfoot><tr class="fw-bold"><td colspan="3" class="text-end">Tổng giá trị nhập (không tính HĐ đã hủy)</td><td class="text-end"><?= money($sum) ?></td><td colspan="3"></td></tr></tfoot><?php endif; ?>
</table></div>
