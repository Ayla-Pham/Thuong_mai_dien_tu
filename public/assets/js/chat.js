/**
 * ChatBox: logic chat dùng chung cho khung chat của khách (widget) và trang chat của admin.
 * Cách hoạt động ("polling"): cứ vài giây gọi API lấy các tin có id lớn hơn tin cuối cùng đã hiển thị.
 * Nội dung tin được chèn bằng textContent (không phải innerHTML) nên không thể chèn mã HTML/JS độc hại (chống XSS).
 *
 * Tham số:
 *  list      : phần tử chứa các tin nhắn
 *  fetchUrl  : API lấy tin mới (trả {messages:[...], unread})
 *  sendUrl   : API gửi tin (POST body, _csrf)
 *  csrf      : token CSRF
 *  mine      : mảng người gửi hiển thị bên phải (tin "của mình")
 *  isOpen()  : khung chat đang mở? (đang mở thì server đánh dấu đã đọc)
 *  interval(): số ms giữa 2 lần tải
 *  onUnread(n): callback cập nhật số tin chưa đọc
 */
function ChatBox(o) {
    let lastId = 0, timer = null, busy = false, first = true;

    function bubble(m) {
        const wrap = document.createElement('div');
        wrap.className = 'chat-msg ' + (o.mine.includes(m.sender) ? 'mine' : 'theirs') + (m.sender === 'auto' ? ' auto' : '');
        if (m.sender === 'auto') {
            const tag = document.createElement('div'); tag.className = 'chat-tag'; tag.textContent = 'Tin nhắn tự động';
            wrap.appendChild(tag);
        }
        const body = document.createElement('div'); body.className = 'chat-body'; body.textContent = m.body;
        const time = document.createElement('div'); time.className = 'chat-time'; time.textContent = m.time;
        wrap.append(body, time);
        return wrap;
    }

    async function poll() {
        if (busy) return;
        busy = true;
        try {
            const url = o.fetchUrl + (o.fetchUrl.includes('?') ? '&' : '?') + 'after=' + lastId + (o.isOpen() ? '&open=1' : '');
            const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (res.ok) {
                const data = await res.json();
                // Chỉ tự cuộn xuống cuối nếu người dùng đang ở gần cuối (hoặc đây là lần tải đầu) để không giật khi đang đọc tin cũ
                const stick = first || list.scrollHeight - list.scrollTop - list.clientHeight < 80;
                data.messages.forEach(m => { if (m.id > lastId) { list.appendChild(bubble(m)); lastId = m.id; } });
                if (data.messages.length && stick) list.scrollTop = list.scrollHeight;
                first = false;
                if (o.onUnread) o.onUnread(data.unread || 0);
            }
        } catch (e) { /* mất mạng tạm thời: lần tải sau sẽ thử lại */ }
        busy = false;
    }
    const list = o.list;

    function loop() {
        clearTimeout(timer);
        timer = setTimeout(async () => { if (!document.hidden) await poll(); loop(); }, o.interval());
    }

    /** Gửi tin; trả về chuỗi lỗi hoặc null nếu thành công. */
    async function send(text) {
        const fd = new FormData();
        fd.append('_csrf', o.csrf); fd.append('body', text);
        try {
            const res = await fetch(o.sendUrl, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) return data.error || 'Không gửi được tin nhắn.';
            await poll();                 // hiện ngay tin vừa gửi (và tin tự động nếu có)
            list.scrollTop = list.scrollHeight;
            return null;
        } catch (e) { return 'Mất kết nối, vui lòng thử lại.'; }
    }

    return { poll, send, start() { poll(); loop(); }, restart: loop };
}
