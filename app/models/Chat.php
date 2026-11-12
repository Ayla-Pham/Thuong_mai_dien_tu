<?php
/**
 * Model Chat: cuộc trò chuyện giữa khách hàng và shop (admin).
 * - Mỗi khách có 1 conversation; các tin nhắn nằm trong chat_messages với người gửi: customer | admin | auto.
 * - 'auto' là tin nhắn tự động, nội dung do admin soạn sẵn trong mục "Tin nhắn tự động" (bảng settings).
 * - Đếm tin chưa đọc ở 2 phía bằng customer_unread / admin_unread để hiện huy hiệu số.
 */
class Chat extends Model
{
    /** Chỉ gửi lại tin tự động nếu lần gửi trước cách đây hơn số giờ này (tránh trả lời tự động liên tục). */
    public const AUTO_REPLY_COOLDOWN_HOURS = 6;
    public const MAX_LENGTH = 1000;

    /* ------------------------- cài đặt tin nhắn tự động ------------------------- */

    public function setting(string $name, string $default = ''): string
    {
        $st = $this->db->prepare("SELECT value FROM settings WHERE name = ?");
        $st->execute([$name]);
        $v = $st->fetchColumn();
        return $v === false ? $default : (string)$v;
    }

    public function saveAutoReply(bool $enabled, string $text): void
    {
        $up = $this->db->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
        $up->execute(['chat_auto_reply_enabled', $enabled ? '1' : '0']);
        $up->execute(['chat_auto_reply_text', $text]);
    }

    /* ------------------------- phía khách hàng ------------------------- */

    /** Lấy cuộc trò chuyện của khách, chưa có thì tạo mới. */
    public function conversationFor(int $userId): array
    {
        $this->db->prepare("INSERT IGNORE INTO chat_conversations (user_id) VALUES (?)")->execute([$userId]);
        $st = $this->db->prepare("SELECT * FROM chat_conversations WHERE user_id = ?");
        $st->execute([$userId]);
        return $st->fetch();
    }

    /**
     * Khách gửi tin. Nếu bật tin tự động và đã quá thời gian chờ thì gửi kèm 1 tin tự động ngay sau đó.
     * Toàn bộ nằm trong transaction để tin nhắn và bộ đếm chưa đọc luôn nhất quán.
     */
    public function customerSend(int $userId, string $body): void
    {
        $conv = $this->conversationFor($userId);
        $this->db->beginTransaction();
        $this->insert((int)$conv['id'], 'customer', $body);
        $this->db->prepare("UPDATE chat_conversations SET admin_unread = admin_unread + 1, last_message_at = NOW() WHERE id = ?")->execute([$conv['id']]);

        $auto = $this->setting('chat_auto_reply_text');
        $due  = $conv['last_auto_reply_at'] === null
                || strtotime($conv['last_auto_reply_at']) < time() - self::AUTO_REPLY_COOLDOWN_HOURS * 3600;
        if ($this->setting('chat_auto_reply_enabled') === '1' && trim($auto) !== '' && $due) {
            $this->insert((int)$conv['id'], 'auto', $auto);
            $this->db->prepare("UPDATE chat_conversations SET customer_unread = customer_unread + 1, last_auto_reply_at = NOW() WHERE id = ?")->execute([$conv['id']]);
        }
        $this->db->commit();
    }

    /** Đã gửi quá nhiều tin trong 1 phút? (chống spam) */
    public function tooFast(int $conversationId): bool
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM chat_messages WHERE conversation_id = ? AND sender = 'customer' AND created_at > NOW() - INTERVAL 1 MINUTE");
        $st->execute([$conversationId]);
        return $st->fetchColumn() >= 15;
    }

    /** Khách mở khung chat => đánh dấu đã đọc. */
    public function markReadByCustomer(int $conversationId): void
    {
        $this->db->prepare("UPDATE chat_conversations SET customer_unread = 0 WHERE id = ?")->execute([$conversationId]);
    }

    /* ------------------------- phía admin ------------------------- */

    public function adminSend(int $conversationId, string $body): void
    {
        $this->db->beginTransaction();
        $this->insert($conversationId, 'admin', $body);
        $this->db->prepare("UPDATE chat_conversations SET customer_unread = customer_unread + 1, admin_unread = 0, last_message_at = NOW() WHERE id = ?")->execute([$conversationId]);
        $this->db->commit();
    }

    public function markReadByAdmin(int $conversationId): void
    {
        $this->db->prepare("UPDATE chat_conversations SET admin_unread = 0 WHERE id = ?")->execute([$conversationId]);
    }

    /** Danh sách cuộc trò chuyện (mới nhất trước) kèm tên khách và tin cuối. */
    public function adminList(): array
    {
        return $this->db->query(
            "SELECT c.*, u.full_name, u.email,
                    (SELECT m.body FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_body,
                    (SELECT m.sender FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_sender
             FROM chat_conversations c JOIN users u ON u.id = c.user_id
             WHERE EXISTS (SELECT 1 FROM chat_messages m WHERE m.conversation_id = c.id)
             ORDER BY c.last_message_at DESC")->fetchAll();
    }

    public function find(int $conversationId): ?array
    {
        $st = $this->db->prepare("SELECT c.*, u.full_name, u.email, u.phone FROM chat_conversations c JOIN users u ON u.id = c.user_id WHERE c.id = ?");
        $st->execute([$conversationId]);
        return $st->fetch() ?: null;
    }

    /** Tổng số tin khách chưa được admin đọc (huy hiệu ở menu admin). */
    public function adminUnreadTotal(): int
    {
        return (int)$this->db->query("SELECT COALESCE(SUM(admin_unread), 0) FROM chat_conversations")->fetchColumn();
    }

    /* ------------------------- dùng chung ------------------------- */

    /** Tin nhắn có id lớn hơn $afterId (dùng cho cơ chế tải định kỳ: chỉ lấy tin mới). */
    public function messagesAfter(int $conversationId, int $afterId): array
    {
        $st = $this->db->prepare("SELECT id, sender, body, created_at FROM chat_messages WHERE conversation_id = ? AND id > ? ORDER BY id LIMIT 200");
        $st->execute([$conversationId, $afterId]);
        return array_map(fn($m) => [
            'id' => (int)$m['id'], 'sender' => $m['sender'], 'body' => $m['body'],
            'time' => date('H:i d/m', strtotime($m['created_at'])),
        ], $st->fetchAll());
    }

    private function insert(int $conversationId, string $sender, string $body): void
    {
        $this->db->prepare("INSERT INTO chat_messages (conversation_id, sender, body) VALUES (?,?,?)")->execute([$conversationId, $sender, $body]);
    }
}
