<h1 class="h4 mb-3">🛒 Giỏ hàng</h1>
<?php if (!$items): ?>
    <div class="alert alert-info">Giỏ hàng trống. <a href="<?= url('/san-pham') ?>">Tiếp tục mua sắm</a></div>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-8">
        <form method="post" action="<?= url('/gio-hang/cap-nhat') ?>">
            <?= csrf_field() ?>
            <div class="table-responsive"><table class="table align-middle">
                <thead><tr><th>Sản phẩm</th><th class="text-end">Đơn giá</th><th style="width:110px">Số lượng</th><th class="text-end">Thành tiền</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($items as $it): $p = $it['p']; ?>
                    <tr>
                        <td><a href="<?= url('/san-pham/' . $p['slug']) ?>" class="text-decoration-none"><?= e($p['name']) ?></a></td>
                        <td class="text-end"><?= money($p['final_price']) ?></td>
                        <td><input type="number" name="qty[<?= (int)$p['id'] ?>]" value="<?= $it['qty'] ?>" min="0" max="<?= (int)$p['stock'] ?>" class="form-control form-control-sm"></td>
                        <td class="text-end fw-semibold"><?= money($it['line_total']) ?></td>
                        <td><button type="submit" form="rm-<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-danger" title="Xóa">✕</button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <button class="btn btn-outline-primary btn-sm">Cập nhật số lượng</button>
            <a href="<?= url('/san-pham') ?>" class="btn btn-link btn-sm">Tiếp tục mua sắm</a>
        </form>
        <?php foreach ($items as $it): ?>
            <form id="rm-<?= (int)$it['p']['id'] ?>" method="post" action="<?= url('/gio-hang/xoa') ?>"><?= csrf_field() ?><input type="hidden" name="product_id" value="<?= (int)$it['p']['id'] ?>"></form>
        <?php endforeach; ?>
    </div>

    <div class="col-lg-4">
        <div class="card card-body">
            <form method="post" action="<?= url('/gio-hang/ma-giam-gia') ?>" class="input-group input-group-sm mb-3">
                <?= csrf_field() ?>
                <input name="code" class="form-control" placeholder="Mã giảm giá" value="<?= e($totals['coupon']['code'] ?? '') ?>">
                <button class="btn btn-outline-secondary"><?= $totals['coupon'] ? 'Gỡ / đổi' : 'Áp dụng' ?></button>
            </form>
            <?php if ($vouchers): ?>
            <div class="mb-3">
                <div class="small fw-semibold mb-2">🎟️ Voucher dành cho bạn</div>
                <?php foreach ($vouchers as $v): $applied = ($totals['coupon']['code'] ?? '') === $v['code']; ?>
                    <div class="border rounded p-2 mb-2 <?= $v['usable'] ? '' : 'bg-light text-muted' ?> <?= $applied ? 'border-success' : '' ?>">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <span class="badge bg-warning text-dark"><?= e($v['code']) ?></span>
                                <?php if ($v['best'] && !$applied): ?><span class="badge bg-success">Tốt nhất</span><?php endif; ?>
                                <?php if ($applied): ?><span class="badge bg-success">Đang dùng</span><?php endif; ?>
                                <div class="small fw-semibold mt-1"><?= e(Coupon::describe($v)) ?></div>
                                <div class="small">Đơn tối thiểu <?= money($v['min_order']) ?><?= $v['end_date'] ? ' · HSD ' . date('d/m/Y', strtotime($v['end_date'])) : '' ?></div>
                                <?php if ($v['usable']): ?>
                                    <div class="small text-success">Tiết kiệm <?= money($v['discount']) ?> cho giỏ hàng này</div>
                                <?php else: ?>
                                    <div class="small text-danger">Mua thêm <?= money($v['missing']) ?> để dùng mã này</div>
                                <?php endif; ?>
                            </div>
                            <form method="post" action="<?= url('/gio-hang/ma-giam-gia') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="code" value="<?= $applied ? '' : e($v['code']) ?>">
                                <button class="btn btn-sm <?= $applied ? 'btn-outline-secondary' : 'btn-primary' ?>" <?= (!$applied && !$v['usable']) ? 'disabled' : '' ?>><?= $applied ? 'Bỏ chọn' : 'Chọn' ?></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <dl class="row mb-0">
                <dt class="col-6 fw-normal">Tạm tính</dt><dd class="col-6 text-end"><?= money($totals['subtotal']) ?></dd>
                <dt class="col-6 fw-normal">Giảm giá</dt><dd class="col-6 text-end text-success">-<?= money($totals['discount']) ?></dd>
                <dt class="col-6 fw-normal">Phí vận chuyển</dt><dd class="col-6 text-end"><?= $totals['shipping'] ? money($totals['shipping']) : 'Miễn phí' ?></dd>
                <dt class="col-6">Tổng cộng</dt><dd class="col-6 text-end fs-5 fw-bold text-danger"><?= money($totals['total']) ?></dd>
            </dl>
            <small class="text-muted">Miễn phí ship cho đơn từ <?= money(Cart::FREE_SHIP_FROM) ?>.</small>
            <a href="<?= url('/thanh-toan') ?>" class="btn btn-primary mt-3">Tiến hành thanh toán</a>
        </div>
    </div>
</div>
<?php endif; ?>
