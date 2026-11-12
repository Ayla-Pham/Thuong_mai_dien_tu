<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Tin nhắn khách hàng</h1>
    <a href="<?= url('/admin/tin-nhan/tu-dong') ?>" class="btn btn-outline-primary btn-sm">⚙ Tin nhắn tự động</a>
</div>
<div class="list-group bg-white">
<?php foreach ($conversations as $c): ?>
    <a href="<?= url('/admin/tin-nhan/' . $c['id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-3 <?= $c['admin_unread'] ? 'fw-bold' : '' ?>">
        <div class="text-truncate">
            <div><?= e($c['full_name']) ?> <small class="text-muted fw-normal"><?= e($c['email']) ?></small></div>
            <div class="small text-muted text-truncate fw-normal">
                <?= $c['last_sender'] === 'customer' ? '' : ($c['last_sender'] === 'auto' ? '🤖 ' : 'Bạn: ') ?><?= e(mb_substr($c['last_body'], 0, 90)) ?>
            </div>
        </div>
        <div class="text-end text-nowrap">
            <div class="small text-muted fw-normal"><?= date('H:i d/m', strtotime($c['last_message_at'])) ?></div>
            <?php if ($c['admin_unread']): ?><span class="badge rounded-pill bg-danger"><?= (int)$c['admin_unread'] ?> mới</span><?php endif; ?>
        </div>
    </a>
<?php endforeach; ?>
<?php if (!$conversations): ?><div class="list-group-item text-muted text-center py-4">Chưa có khách nào nhắn tin.</div><?php endif; ?>
</div>
<script>
// Tự tải lại danh sách mỗi 10 giây để thấy tin mới (trừ khi tab đang ẩn)
setTimeout(function tick() { if (!document.hidden) location.reload(); else setTimeout(tick, 10000); }, 10000);
</script>
