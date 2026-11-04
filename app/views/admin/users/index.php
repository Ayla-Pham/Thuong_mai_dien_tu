<h1 class="h4 mb-3">Khách hàng <small class="text-muted">(<?= count($users) ?>)</small></h1>
<div class="table-responsive"><table class="table table-sm table-hover align-middle bg-white">
    <thead><tr><th>Họ tên</th><th>Email</th><th>SĐT</th><th class="text-end">Số đơn</th><th class="text-end">Đã chi tiêu</th><th>Ngày đăng ký</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr class="<?= $u['is_active'] ? '' : 'table-secondary' ?>">
            <td><?= e($u['full_name']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['phone']) ?></td>
            <td class="text-end"><?= (int)$u['order_count'] ?></td><td class="text-end"><?= money($u['spent']) ?></td>
            <td class="small"><?= e(date('d/m/Y', strtotime($u['created_at']))) ?></td>
            <td class="text-end">
                <form method="post" action="<?= url('/admin/khach-hang/' . $u['id'] . '/khoa') ?>">
                    <?= csrf_field() ?><input type="hidden" name="active" value="<?= $u['is_active'] ? '0' : '1' ?>">
                    <button class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"><?= $u['is_active'] ? 'Khóa' : 'Mở khóa' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
