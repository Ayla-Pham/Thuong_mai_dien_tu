-- =====================================================================
-- CHAT KHÁCH HÀNG <-> SHOP (admin trả lời) + TIN NHẮN TỰ ĐỘNG
-- Chạy trên DB đã có sẵn:  mysql -uroot --default-character-set=utf8mb4 bookstore < database/migration_chat.sql
-- (Bảng cũng đã được thêm vào cuối schema.sql cho lần cài mới.)
-- =====================================================================
USE bookstore;
SET NAMES utf8mb4;

-- Mỗi khách có đúng 1 cuộc trò chuyện với shop
CREATE TABLE IF NOT EXISTS chat_conversations (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id            INT UNSIGNED NOT NULL UNIQUE,
    customer_unread    INT UNSIGNED NOT NULL DEFAULT 0,   -- số tin shop gửi mà khách chưa đọc
    admin_unread       INT UNSIGNED NOT NULL DEFAULT 0,   -- số tin khách gửi mà admin chưa đọc
    last_auto_reply_at DATETIME NULL,                     -- lần gần nhất gửi tin tự động (tránh gửi lặp)
    last_message_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_cc_last (last_message_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chat_messages (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id  INT UNSIGNED NOT NULL,
    sender           ENUM('customer','admin','auto') NOT NULL,   -- auto = tin nhắn tự động do admin soạn sẵn
    body             VARCHAR(1000) NOT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cm_conv FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    INDEX idx_cm_conv (conversation_id, id)
) ENGINE=InnoDB;

-- Bảng cài đặt chung dạng khóa - giá trị (dùng cho nội dung tin nhắn tự động)
CREATE TABLE IF NOT EXISTS settings (
    name   VARCHAR(50) PRIMARY KEY,
    value  TEXT NOT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO settings (name, value) VALUES
('chat_auto_reply_enabled', '1'),
('chat_auto_reply_text', 'Xin chào! Cảm ơn bạn đã nhắn tin cho BookStore 📚. Chúng tôi đã nhận được tin nhắn và sẽ phản hồi trong thời gian sớm nhất (giờ làm việc 8:00 - 21:00 hằng ngày).');
