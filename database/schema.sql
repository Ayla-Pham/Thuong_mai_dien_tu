-- =====================================================================
-- SCHEMA CSDL: Website bán sách & văn phòng phẩm (B2C)
-- Chuẩn hóa tới 3NF, engine InnoDB (hỗ trợ khóa ngoại + transaction)
-- Import: phpMyAdmin -> Import -> chọn file này, sau đó import seed.sql
-- =====================================================================
DROP DATABASE IF EXISTS bookstore;
CREATE DATABASE bookstore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bookstore;

-- ---------------------------------------------------------------------
-- 1. USERS: khách hàng và quản trị viên (phân quyền bằng cột role)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,                  -- lưu hash bcrypt (password_hash), KHÔNG lưu mật khẩu thô
    phone       VARCHAR(20)  NULL,
    address     VARCHAR(255) NULL,
    role        ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,        -- 0 = khóa tài khoản
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. CATEGORIES: thể loại / danh mục (hỗ trợ danh mục cha - con)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id   INT UNSIGNED NULL,                      -- NULL = danh mục gốc
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(120) NOT NULL UNIQUE,           -- dùng cho URL thân thiện
    description VARCHAR(255) NULL,
    CONSTRAINT fk_cat_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. AUTHORS & PUBLISHERS: phục vụ bộ lọc nâng cao (tác giả, nhà xuất bản)
-- ---------------------------------------------------------------------
CREATE TABLE authors (
    id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(100) NOT NULL,
    slug  VARCHAR(120) NOT NULL UNIQUE,
    bio   TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE publishers (
    id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(100) NOT NULL,
    slug  VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. PRODUCTS: sách và văn phòng phẩm (phân biệt bằng cột type)
-- ---------------------------------------------------------------------
CREATE TABLE products (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id   INT UNSIGNED NOT NULL,
    publisher_id  INT UNSIGNED NULL,                    -- văn phòng phẩm có thể không có NXB
    type          ENUM('book','stationery') NOT NULL DEFAULT 'book',
    name          VARCHAR(200) NOT NULL,
    slug          VARCHAR(220) NOT NULL UNIQUE,
    description   TEXT NULL,
    price         DECIMAL(12,0) NOT NULL,               -- giá gốc (VND)
    sale_price    DECIMAL(12,0) NULL,                   -- giá khuyến mãi, NULL = không giảm
    stock         INT UNSIGNED NOT NULL DEFAULT 0,      -- tồn kho
    image         VARCHAR(255) NULL,
    pages         SMALLINT UNSIGNED NULL,               -- chỉ dùng cho sách
    publish_year  YEAR NULL,
    sold_count    INT UNSIGNED NOT NULL DEFAULT 0,      -- số lượng đã bán (sắp xếp "bán chạy")
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prod_cat FOREIGN KEY (category_id)  REFERENCES categories(id),
    CONSTRAINT fk_prod_pub FOREIGN KEY (publisher_id) REFERENCES publishers(id) ON DELETE SET NULL,
    INDEX idx_prod_cat (category_id),
    INDEX idx_prod_pub (publisher_id),
    INDEX idx_prod_price (price),
    FULLTEXT INDEX ft_prod_name (name)                  -- tìm kiếm theo từ khóa
) ENGINE=InnoDB;

-- Quan hệ nhiều-nhiều: 1 sách có nhiều tác giả, 1 tác giả có nhiều sách
CREATE TABLE product_authors (
    product_id INT UNSIGNED NOT NULL,
    author_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (product_id, author_id),
    CONSTRAINT fk_pa_prod   FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_pa_author FOREIGN KEY (author_id)  REFERENCES authors(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. TAGS: nhãn gắn cho sản phẩm, dùng cho hệ thống gợi ý "Sách liên quan"
-- ---------------------------------------------------------------------
CREATE TABLE tags (
    id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE product_tags (
    product_id INT UNSIGNED NOT NULL,
    tag_id     INT UNSIGNED NOT NULL,
    PRIMARY KEY (product_id, tag_id),
    CONSTRAINT fk_pt_prod FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_pt_tag  FOREIGN KEY (tag_id)     REFERENCES tags(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. COUPONS: mã giảm giá
-- ---------------------------------------------------------------------
CREATE TABLE coupons (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(30) NOT NULL UNIQUE,
    discount_type   ENUM('percent','fixed') NOT NULL,   -- giảm theo % hoặc số tiền cố định
    discount_value  DECIMAL(12,0) NOT NULL,
    max_discount    DECIMAL(12,0) NULL,                 -- trần giảm khi giảm theo %
    min_order       DECIMAL(12,0) NOT NULL DEFAULT 0,   -- giá trị đơn tối thiểu
    usage_limit     INT UNSIGNED NULL,                  -- NULL = không giới hạn
    used_count      INT UNSIGNED NOT NULL DEFAULT 0,
    start_date      DATE NULL,
    end_date        DATE NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. ORDERS + ORDER_ITEMS: đơn hàng và chi tiết đơn
-- ---------------------------------------------------------------------
CREATE TABLE orders (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL,
    coupon_id        INT UNSIGNED NULL,
    order_code       VARCHAR(20) NOT NULL UNIQUE,       -- mã đơn hiển thị cho khách, vd DH20261104001
    receiver_name    VARCHAR(100) NOT NULL,             -- thông tin giao hàng lưu riêng, không phụ thuộc hồ sơ user
    receiver_phone   VARCHAR(20)  NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    note             VARCHAR(255) NULL,
    subtotal         DECIMAL(12,0) NOT NULL,            -- tổng tiền hàng
    discount_amount  DECIMAL(12,0) NOT NULL DEFAULT 0,
    shipping_fee     DECIMAL(12,0) NOT NULL DEFAULT 0,
    total            DECIMAL(12,0) NOT NULL,            -- = subtotal - discount_amount + shipping_fee
    payment_method   ENUM('cod','bank_transfer','vnpay') NOT NULL DEFAULT 'cod',
    payment_status   ENUM('unpaid','paid','failed') NOT NULL DEFAULT 'unpaid',
    status           ENUM('pending','confirmed','shipping','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ord_user   FOREIGN KEY (user_id)   REFERENCES users(id),
    CONSTRAINT fk_ord_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL,
    INDEX idx_ord_user (user_id),
    INDEX idx_ord_status (status)
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id     INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NOT NULL,
    product_name VARCHAR(200) NOT NULL,                 -- lưu snapshot tên + giá tại thời điểm mua
    unit_price   DECIMAL(12,0) NOT NULL,
    quantity     INT UNSIGNED NOT NULL,
    CONSTRAINT fk_oi_order FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    CONSTRAINT fk_oi_prod  FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_oi_prod (product_id)                      -- phục vụ gợi ý "thường được mua cùng"
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. REVIEWS: đánh giá sản phẩm (1 người chỉ đánh giá 1 lần / sản phẩm)
-- ---------------------------------------------------------------------
CREATE TABLE reviews (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    rating      TINYINT UNSIGNED NOT NULL,
    comment     VARCHAR(500) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_rv_prod FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_rv_user FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    UNIQUE KEY uq_review (product_id, user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. NHẬP HÀNG (suppliers, purchase_invoices, purchase_invoice_items)
-- ---------------------------------------------------------------------
CREATE TABLE suppliers (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    phone      VARCHAR(20)  NULL,
    email      VARCHAR(150) NULL,
    address    VARCHAR(255) NULL,
    tax_code   VARCHAR(20)  NULL,                       -- mã số thuế
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE purchase_invoices (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id   INT UNSIGNED NOT NULL,
    created_by    INT UNSIGNED NOT NULL,                -- admin lập phiếu
    invoice_no    VARCHAR(50)  NOT NULL,                -- số hóa đơn in trên giấy của NCC
    invoice_date  DATE         NOT NULL,
    total         DECIMAL(14,0) NOT NULL DEFAULT 0,     -- = tổng (số lượng x giá nhập), server tự tính
    note          VARCHAR(255) NULL,
    status        ENUM('received','cancelled') NOT NULL DEFAULT 'received',
    file_path     VARCHAR(100) NULL,                    -- tên file lưu trên đĩa (đã đổi tên ngẫu nhiên)
    file_name     VARCHAR(150) NULL,                    -- tên gốc để hiển thị khi tải về
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pi_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_pi_user     FOREIGN KEY (created_by)  REFERENCES users(id),
    UNIQUE KEY uq_supplier_invoice (supplier_id, invoice_no),   -- 1 NCC không có 2 hóa đơn trùng số
    INDEX idx_pi_date (invoice_date)
) ENGINE=InnoDB;

CREATE TABLE purchase_invoice_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id  INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    quantity    INT UNSIGNED NOT NULL,
    unit_cost   DECIMAL(12,0) NOT NULL,                 -- giá nhập / đơn vị
    CONSTRAINT fk_pii_invoice FOREIGN KEY (invoice_id) REFERENCES purchase_invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_pii_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;


-- ---------------------------------------------------------------------
-- 10. GIỎ HÀNG LƯU THEO TÀI KHOẢN (cart_items)
-- ---------------------------------------------------------------------
CREATE TABLE cart_items (
    user_id     INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    quantity    INT UNSIGNED NOT NULL,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, product_id),                  -- mỗi người chỉ có 1 dòng cho mỗi sản phẩm
    CONSTRAINT fk_ci_user FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_ci_prod FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11. CHAT (chat_conversations, chat_messages) + CÀI ĐẶT (settings)
-- ---------------------------------------------------------------------
-- Mỗi khách có đúng 1 cuộc trò chuyện với shop
CREATE TABLE chat_conversations (
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

CREATE TABLE chat_messages (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id  INT UNSIGNED NOT NULL,
    sender           ENUM('customer','admin','auto') NOT NULL,   -- auto = tin nhắn tự động do admin soạn sẵn
    body             VARCHAR(1000) NOT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cm_conv FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    INDEX idx_cm_conv (conversation_id, id)
) ENGINE=InnoDB;

-- Bảng cài đặt chung dạng khóa - giá trị (dùng cho nội dung tin nhắn tự động)
CREATE TABLE settings (
    name   VARCHAR(50) PRIMARY KEY,
    value  TEXT NOT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO settings (name, value) VALUES
('chat_auto_reply_enabled', '1'),
('chat_auto_reply_text', 'Xin chào! Cảm ơn bạn đã nhắn tin cho BookStore 📚. Chúng tôi đã nhận được tin nhắn và sẽ phản hồi trong thời gian sớm nhất (giờ làm việc 8:00 - 21:00 hằng ngày).');
