<?php
/**
 * Khung chat nổi cho khách hàng (hiện ở mọi trang của website, trừ khi đang đăng nhập bằng tài khoản admin).
 * Khách chưa đăng nhập bấm vào sẽ thấy lời mời đăng nhập.
 */
$chatUser = auth_user();
if ($chatUser && $chatUser['role'] === 'admin') return;
?>
<link rel="stylesheet" href="<?= asset('css/chat.css') ?>">
<button id="chat-fab" type="button" aria-label="Chat với cửa hàng" title="Chat với cửa hàng">💬<span class="badge rounded-pill bg-danger d-none" id="chat-badge">0</span></button>
<div id="chat-panel" role="dialog" aria-label="Chat với cửa hàng">
    <div class="d-flex justify-content-between align-items-center bg-primary text-white px-3 py-2">
        <div><strong>Chat với BookStore</strong><div class="small opacity-75">Chúng tôi sẽ phản hồi sớm nhất</div></div>
        <button type="button" id="chat-close" class="btn-close btn-close-white" aria-label="Đóng"></button>
    </div>
<?php if ($chatUser): ?>
    <div id="chat-list" class="chat-list"></div>
    <form id="chat-form" class="d-flex gap-2 p-2 border-top">
        <?= csrf_field() ?>
        <input id="chat-input" class="form-control form-control-sm" maxlength="1000" autocomplete="off" placeholder="Nhập tin nhắn..." required>
        <button class="btn btn-primary btn-sm">Gửi</button>
    </form>
    <div id="chat-error" class="small text-danger px-2 pb-1"></div>
<?php else: ?>
    <div class="p-4 text-center flex-grow-1 d-flex flex-column justify-content-center">
        <p class="mb-3">Vui lòng đăng nhập để chat với cửa hàng.</p>
        <a class="btn btn-primary btn-sm mb-2" href="<?= url('/dang-nhap') ?>">Đăng nhập</a>
        <a class="btn btn-outline-primary btn-sm" href="<?= url('/dang-ky') ?>">Đăng ký</a>
    </div>
<?php endif; ?>
</div>
<?php if ($chatUser): ?>
<script src="<?= asset('js/chat.js') ?>"></script>
<script>
(function () {
    const panel = document.getElementById('chat-panel'), badge = document.getElementById('chat-badge');
    const input = document.getElementById('chat-input'), err = document.getElementById('chat-error');
    const box = ChatBox({
        list: document.getElementById('chat-list'),
        fetchUrl: '<?= url('/chat/tin-nhan') ?>', sendUrl: '<?= url('/chat/gui') ?>',
        csrf: document.querySelector('#chat-form input[name=_csrf]').value,
        mine: ['customer'],
        isOpen: () => panel.classList.contains('open'),
        interval: () => panel.classList.contains('open') ? 3000 : 15000,   // mở khung thì tải nhanh hơn
        onUnread: n => { badge.textContent = n; badge.classList.toggle('d-none', n < 1 || panel.classList.contains('open')); }
    });
    box.start();
    document.getElementById('chat-fab').onclick = () => {
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) { box.poll(); box.restart(); input.focus(); }
    };
    document.getElementById('chat-close').onclick = () => panel.classList.remove('open');
    document.getElementById('chat-form').onsubmit = async e => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        input.disabled = true;
        const msg = await box.send(text);
        err.textContent = msg || '';
        if (!msg) input.value = '';
        input.disabled = false; input.focus();
    };
})();
</script>
<?php else: ?>
<script>
document.getElementById('chat-fab').onclick = () => document.getElementById('chat-panel').classList.toggle('open');
document.getElementById('chat-close').onclick = () => document.getElementById('chat-panel').classList.remove('open');
</script>
<?php endif; ?>
