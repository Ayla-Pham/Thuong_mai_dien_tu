<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Tin nhắn tự động</h1>
    <a href="<?= url('/admin/tin-nhan') ?>" class="btn btn-link btn-sm">← Tin nhắn khách hàng</a>
</div>
<form method="post" action="<?= url('/admin/tin-nhan/tu-dong') ?>" class="bg-white p-3 rounded border" style="max-width:720px">
    <?= csrf_field() ?>
    <p class="small text-muted">Khi khách gửi tin nhắn, hệ thống tự động gửi nội dung bên dưới để khách biết cửa hàng đã nhận được tin.
        Để tránh làm phiền, tin này chỉ gửi lại sau <?= (int)Chat::AUTO_REPLY_COOLDOWN_HOURS ?> giờ kể từ lần gửi trước cho cùng một khách.</p>
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" role="switch" name="enabled" id="enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
        <label class="form-check-label" for="enabled">Bật tin nhắn tự động</label>
    </div>
    <label class="form-label" for="text">Nội dung</label>
    <textarea name="text" id="text" rows="5" maxlength="1000" class="form-control mb-1" required><?= e($text) ?></textarea>
    <div class="form-text mb-3"><span id="count"><?= mb_strlen($text) ?></span>/1000 ký tự</div>

    <div class="fw-semibold small mb-1">Xem trước (khách sẽ thấy):</div>
    <div class="chat-msg auto theirs mb-3" style="max-width:100%"><link rel="stylesheet" href="<?= asset('css/chat.css') ?>">
        <div class="chat-tag">Tin nhắn tự động</div><div class="chat-body" id="preview"></div></div>

    <button class="btn btn-primary">Lưu</button>
</form>
<script>
const t = document.getElementById('text'), pv = document.getElementById('preview'), ct = document.getElementById('count');
const upd = () => { pv.textContent = t.value || '(trống)'; ct.textContent = t.value.length; };
t.addEventListener('input', upd); upd();
</script>
