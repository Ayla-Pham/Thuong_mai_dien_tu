<link rel="stylesheet" href="<?= asset('css/chat.css') ?>">
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h5 mb-0">Chat với <?= e($conv['full_name']) ?></h1>
        <div class="small text-muted"><?= e($conv['email']) ?><?= $conv['phone'] ? ' · ' . e($conv['phone']) : '' ?></div>
    </div>
    <a href="<?= url('/admin/tin-nhan') ?>" class="btn btn-link btn-sm">← Danh sách</a>
</div>
<div class="bg-white border rounded d-flex flex-column" style="max-width:720px;height:70vh">
    <div id="chat-list" class="chat-list flex-grow-1"></div>
    <form id="chat-form" class="d-flex gap-2 p-2 border-top">
        <?= csrf_field() ?>
        <input id="chat-input" class="form-control" maxlength="1000" autocomplete="off" placeholder="Nhập câu trả lời cho khách..." required autofocus>
        <button class="btn btn-primary">Gửi</button>
    </form>
    <div id="chat-error" class="small text-danger px-3 pb-1"></div>
</div>
<script src="<?= asset('js/chat.js') ?>"></script>
<script>
(function () {
    const input = document.getElementById('chat-input'), err = document.getElementById('chat-error');
    const box = ChatBox({
        list: document.getElementById('chat-list'),
        fetchUrl: '<?= url('/admin/tin-nhan/' . (int)$conv['id'] . '/lay') ?>',
        sendUrl: '<?= url('/admin/tin-nhan/' . (int)$conv['id'] . '/gui') ?>',
        csrf: document.querySelector('#chat-form input[name=_csrf]').value,
        mine: ['admin', 'auto'],            // phía shop (kể cả tin tự động) hiển thị bên phải
        isOpen: () => true, interval: () => 3000
    });
    box.start();
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
