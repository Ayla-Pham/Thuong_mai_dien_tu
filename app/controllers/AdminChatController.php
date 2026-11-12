<?php
/** Chat phía ADMIN: xem danh sách khách nhắn tin, trả lời, và soạn trước tin nhắn tự động. */
class AdminChatController extends AdminController
{
    /** Danh sách cuộc trò chuyện. */
    public function index(): void
    {
        $this->view('admin/chat/index', ['title' => 'Tin nhắn khách hàng', 'conversations' => (new Chat())->adminList()]);
    }

    /** Một cuộc trò chuyện: hiển thị khung chat, tin nhắn được nạp bằng JS qua messages(). */
    public function show(string $id): void
    {
        $chat = new Chat();
        $conv = $chat->find((int)$id);
        if (!$conv) { flash('danger', 'Không tìm thấy cuộc trò chuyện.'); redirect('/admin/tin-nhan'); }
        $chat->markReadByAdmin((int)$conv['id']);
        $this->view('admin/chat/show', ['title' => 'Chat với ' . $conv['full_name'], 'conv' => $conv]);
    }

    /** GET JSON: tin mới hơn ?after=ID (khung chat đang mở nên đánh dấu đã đọc). */
    public function messages(string $id): void
    {
        $chat = new Chat();
        $conv = $chat->find((int)$id) ?? $this->json(['error' => 'Không tìm thấy.'], 404);
        $chat->markReadByAdmin((int)$conv['id']);
        $this->json(['messages' => $chat->messagesAfter((int)$conv['id'], (int)($_GET['after'] ?? 0))]);
    }

    /** POST JSON: admin trả lời. */
    public function send(string $id): void
    {
        csrf_verify();
        $chat = new Chat();
        $conv = $chat->find((int)$id) ?? $this->json(['error' => 'Không tìm thấy.'], 404);
        $body = trim((string)($_POST['body'] ?? ''));
        if ($body === '' || mb_strlen($body) > Chat::MAX_LENGTH) $this->json(['error' => 'Tin nhắn phải từ 1 đến ' . Chat::MAX_LENGTH . ' ký tự.'], 422);
        $chat->adminSend((int)$conv['id'], $body);
        $this->json(['ok' => true]);
    }

    /** Trang soạn tin nhắn tự động. */
    public function autoReply(): void
    {
        $chat = new Chat();
        $this->view('admin/chat/auto', ['title' => 'Tin nhắn tự động',
            'enabled' => $chat->setting('chat_auto_reply_enabled', '1') === '1', 'text' => $chat->setting('chat_auto_reply_text')]);
    }

    public function saveAutoReply(): void
    {
        csrf_verify();
        $text = trim((string)($_POST['text'] ?? ''));
        $enabled = isset($_POST['enabled']);
        if (mb_strlen($text) > Chat::MAX_LENGTH || ($enabled && $text === '')) {
            flash('danger', 'Nội dung tin nhắn tự động phải từ 1 đến ' . Chat::MAX_LENGTH . ' ký tự (hoặc tắt tính năng này).');
            redirect('/admin/tin-nhan/tu-dong');
        }
        (new Chat())->saveAutoReply($enabled, $text);
        flash('success', 'Đã lưu tin nhắn tự động.');
        redirect('/admin/tin-nhan/tu-dong');
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        exit(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
