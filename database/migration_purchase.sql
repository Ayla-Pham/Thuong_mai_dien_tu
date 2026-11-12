-- =====================================================================
-- MODULE NHẬP HÀNG: nhà cung cấp + hóa đơn nhập (có file đính kèm)
-- Chạy trên DB đã có sẵn:  mysql -uroot bookstore < database/migration_purchase.sql
-- (Bảng cũng đã được thêm vào cuối schema.sql cho lần cài mới.)
-- =====================================================================
USE bookstore;

CREATE TABLE IF NOT EXISTS suppliers (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    phone      VARCHAR(20)  NULL,
    email      VARCHAR(150) NULL,
    address    VARCHAR(255) NULL,
    tax_code   VARCHAR(20)  NULL,                       -- mã số thuế
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_invoices (
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

CREATE TABLE IF NOT EXISTS purchase_invoice_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id  INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    quantity    INT UNSIGNED NOT NULL,
    unit_cost   DECIMAL(12,0) NOT NULL,                 -- giá nhập / đơn vị
    CONSTRAINT fk_pii_invoice FOREIGN KEY (invoice_id) REFERENCES purchase_invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_pii_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- Nhà cung cấp mẫu
INSERT INTO suppliers (name, phone, email, address, tax_code)
SELECT * FROM (SELECT 'Công ty Phát hành Sách Phương Nam' AS n, '0281234567' AS p, 'sales@phuongnam.vn' AS e, '940 Ba Tháng Hai, Q.11, TP.HCM' AS a, '0301234567' AS t
               UNION ALL SELECT 'Văn phòng phẩm Thiên Long (đại lý)', '0287654321', 'daily@thienlong.vn', '12 Tân Thới Nhất, Q.12, TP.HCM', '0309876543') s
WHERE NOT EXISTS (SELECT 1 FROM suppliers);
