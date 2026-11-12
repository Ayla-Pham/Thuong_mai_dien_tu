<?php
/**
 * Chat phía KHÁCH HÀNG. Trả JSON cho khung chat nổi (widget) ở mọi trang.
 * Cơ chế "gần như thời gian thực": trình duyệt gọi /chat/tin-nhan định kỳ (polling) và chỉ nhận các tin mới hơn id đã có.
 * Khách chỉ truy cập được cuộc trò chuyện CỦA CHÍNH MÌNH (id lấy từ session, không nhận từ request).
 */
class ChatController extends Controller
{
    /** GET /chat/tin-nhan?after=ID&open=1 */
    public function messages(): void
    {
        $user = $this->customerOrDie();
        $chat = new Chat();
        $conv = $chat->conversationFor($user['id']);
        if (!empty($_GET['open'])) {                          // khung chat đang mở => coi như khách đã đọc
            $chat->markReadByCustomer((int)$conv['id']);
            $conv['customer_unread'] = 0;
        }
        $this->json(['messages' => $chat->messagesAfter((int)$conv['id'], (int)($_GET['after'] ?? 0)),
                     'unread'   => (int)$conv['customer_unread']]);
    }

    /** POST /chat/gui - khách gửi tin (có thể kích hoạt tin nhắn tự động). */
    public function send(): void
    {
        $user = $this->customerOrDie();
        csrf_verify();
        $body = trim((string)($_POST['body'] ?? ''));
        if ($body === '' || mb_strlen($body) > Chat::MAX_LENGTH) $this->json(['error' => 'Tin nhắn phải từ 1 đến ' . Chat::MAX_LENGTH . ' ký tự.'], 422);

        $chat = new Chat();
        if ($chat->tooFast((int)$chat->conversationFor($user['id'])['id'])) $this->json(['error' => 'Bạn gửi quá nhanh, vui lòng thử lại sau ít giây.'], 429);
        $chat->customerSend($user['id'], $body);
        $this->json(['ok' => true]);
    }

    /** Chỉ khách hàng đã đăng nhập (admin có trang chat riêng). */
    private function customerOrDie(): array
    {
        $u = auth_user();
        if (!$u || $u['role'] !== 'customer') $this->json(['error' => 'Vui lòng đăng nhập bằng tài khoản khách hàng.'], 401);
        return $u;
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        exit(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
