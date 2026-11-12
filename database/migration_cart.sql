-- =====================================================================
-- GIỎ HÀNG LƯU THEO TÀI KHOẢN: giỏ không mất khi đăng xuất / đổi thiết bị
-- Chạy trên DB đã có sẵn:  mysql -uroot bookstore < database/migration_cart.sql
-- (Bảng cũng đã được thêm vào cuối schema.sql cho lần cài mới.)
-- =====================================================================
USE bookstore;

CREATE TABLE IF NOT EXISTS cart_items (
    user_id     INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    quantity    INT UNSIGNED NOT NULL,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, product_id),                  -- mỗi người chỉ có 1 dòng cho mỗi sản phẩm
    CONSTRAINT fk_ci_user FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_ci_prod FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;
