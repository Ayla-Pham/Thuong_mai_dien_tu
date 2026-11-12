<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Hóa đơn nhập <?= e($inv['invoice_no']) ?>
        <?= $inv['status'] === 'received' ? '<span class="badge text-bg-success fs-6">Đã nhập kho</span>' : '<span class="badge text-bg-secondary fs-6">Đã hủy</span>' ?></h1>
    <a href="<?= url('/admin/hoa-don-nhap') ?>" class="btn btn-link btn-sm">← Danh sách</a>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="bg-white p-3 rounded border mb-3">
            <dl class="row mb-0 small">
                <dt class="col-4">Nhà cung cấp</dt><dd class="col-8"><?= e($inv['supplier_name']) ?></dd>
                <dt class="col-4">Ngày hóa đơn</dt><dd class="col-8"><?= date('d/m/Y', strtotime($inv['invoice_date'])) ?></dd>
                <dt class="col-4">Người lập</dt><dd class="col-8"><?= e($inv['creator_name']) ?> · <?= date('d/m/Y H:i', strtotime($inv['created_at'])) ?></dd>
                <dt class="col-4">Ghi chú</dt><dd class="col-8"><?= e($inv['note'] ?: '—') ?></dd>
            </dl>
        </div>
        <table class="table table-sm bg-white border align-middle">
            <thead><tr><th>Sản phẩm</th><th class="text-end">SL</th><th class="text-end">Giá nhập</th><th class="text-end">Thành tiền</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr><td><?= e($it['product_name']) ?></td><td class="text-end"><?= (int)$it['quantity'] ?></td>
                    <td class="text-end"><?= money($it['unit_cost']) ?></td><td class="text-end"><?= money($it['quantity'] * $it['unit_cost']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="fw-bold"><td colspan="3" class="text-end">Tổng cộng</td><td class="text-end text-danger"><?= money($inv['total']) ?></td></tr></tfoot>
        </table>
    </div>
    <div class="col-lg-4">
        <div class="bg-white p-3 rounded border mb-3">
            <div class="fw-semibold mb-2">📎 File hóa đơn</div>
            <?php if ($inv['file_path']): ?>
                <p class="small mb-2"><?= e($inv['file_name']) ?></p>
                <a href="<?= url('/admin/hoa-don-nhap/' . $inv['id'] . '/tai-ve') ?>" class="btn btn-success btn-sm mb-3">⬇ Tải file về</a>
            <?php else: ?>
                <p class="small text-muted">Chưa có file đính kèm.</p>
            <?php endif; ?>
            <form method="post" action="<?= url('/admin/hoa-don-nhap/' . $inv['id'] . '/tep') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="file" name="invoice_file" class="form-control form-control-sm mb-2" required accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx">
                <button class="btn btn-outline-primary btn-sm"><?= $inv['file_path'] ? 'Thay file khác' : 'Tải file lên' ?></button>
                <div class="form-text">PDF, JPG, PNG, DOCX, XLSX · tối đa 5MB</div>
            </form>
        </div>
        <?php if ($inv['status'] === 'received'): ?>
        <form method="post" action="<?= url('/admin/hoa-don-nhap/' . $inv['id'] . '/huy') ?>" onsubmit="return confirm('Hủy hóa đơn và trừ lại tồn kho?')">
            <?= csrf_field() ?><button class="btn btn-outline-danger btn-sm">Hủy hóa đơn</button>
        </form>
        <?php endif; ?>
    </div>
</div>
