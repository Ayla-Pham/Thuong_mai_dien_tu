-- =====================================================================
-- DỮ LIỆU MẪU (chạy SAU schema.sql)
-- Tài khoản: admin@bookstore.vn / admin123   |   an@example.com / user123
-- =====================================================================
USE bookstore;
SET NAMES utf8mb4;

INSERT INTO users (full_name, email, password, phone, address, role) VALUES
('Quản trị viên', 'admin@bookstore.vn', '$2y$10$IOX3uNxfLMG8dA6VTv1.1uo0IPAZzc1MMF3uCCssj6O5mmNWnMIl6', '0900000000', 'TP.HCM', 'admin'),
('Nguyễn Văn An', 'an@example.com',     '$2y$10$Refp5DDfkY6basULrB.EveaI7.S/0xxLDVe.76Z9c0LGPJROsIvhG', '0911111111', '12 Lê Lợi, Q.1, TP.HCM', 'customer');

INSERT INTO categories (id, parent_id, name, slug, description) VALUES
(1, NULL, 'Sách',               'sach',               'Tất cả các loại sách'),
(2, 1,    'Văn học',            'van-hoc',            'Tiểu thuyết, truyện ngắn, thơ'),
(3, 1,    'Kinh tế - Kỹ năng',  'kinh-te-ky-nang',    'Sách kinh doanh và phát triển bản thân'),
(4, 1,    'Thiếu nhi',          'thieu-nhi',          'Truyện tranh, truyện cổ tích'),
(5, 1,    'Công nghệ thông tin','cong-nghe-thong-tin','Lập trình, cơ sở dữ liệu, mạng'),
(6, NULL, 'Văn phòng phẩm',     'van-phong-pham',     'Dụng cụ học tập và làm việc'),
(7, 6,    'Bút - Viết',         'but-viet',           'Bút bi, bút chì, bút highlight'),
(8, 6,    'Sổ - Vở',            'so-vo',              'Sổ tay, vở học sinh');

INSERT INTO authors (id, name, slug, bio) VALUES
(1, 'Nguyễn Nhật Ánh', 'nguyen-nhat-anh', 'Nhà văn nổi tiếng với các tác phẩm tuổi thơ.'),
(2, 'Nam Cao',         'nam-cao',         'Nhà văn hiện thực xuất sắc của văn học Việt Nam.'),
(3, 'Dale Carnegie',   'dale-carnegie',   'Tác giả sách kỹ năng sống nổi tiếng thế giới.'),
(4, 'Robert C. Martin','robert-c-martin', 'Tác giả Clean Code, chuyên gia phần mềm.'),
(5, 'Tô Hoài',         'to-hoai',         'Tác giả Dế Mèn phiêu lưu ký.'),
(6, 'Robert Kiyosaki', 'robert-kiyosaki', 'Tác giả Cha giàu cha nghèo.');

INSERT INTO publishers (id, name, slug) VALUES
(1, 'NXB Trẻ',          'nxb-tre'),
(2, 'NXB Kim Đồng',     'nxb-kim-dong'),
(3, 'NXB Tổng hợp TP.HCM', 'nxb-tong-hop-tphcm'),
(4, 'NXB Thế Giới',     'nxb-the-gioi');

INSERT INTO products (id, category_id, publisher_id, type, name, slug, description, price, sale_price, stock, image, pages, publish_year, sold_count) VALUES
(1,  2, 1, 'book', 'Mắt Biếc', 'mat-biec', 'Câu chuyện tình yêu đầy day dứt của Ngạn dành cho Hà Lan.', 110000, 95000, 50, 'mat-biec.jpg', 300, 2019, 320),
(2,  2, 1, 'book', 'Cho Tôi Xin Một Vé Đi Tuổi Thơ', 'cho-toi-xin-mot-ve-di-tuoi-tho', 'Hành trình trở về tuổi thơ trong trẻo.', 90000, NULL, 40, 've-tuoi-tho.jpg', 220, 2018, 280),
(3,  2, 1, 'book', 'Tôi Thấy Hoa Vàng Trên Cỏ Xanh', 'toi-thay-hoa-vang-tren-co-xanh', 'Tuổi thơ miền quê nghèo đầy cảm xúc.', 125000, 99000, 35, 'hoa-vang.jpg', 378, 2020, 410),
(4,  2, 3, 'book', 'Chí Phèo', 'chi-pheo', 'Tác phẩm kinh điển của Nam Cao.', 60000, NULL, 60, 'chi-pheo.jpg', 120, 2017, 150),
(5,  2, 2, 'book', 'Dế Mèn Phiêu Lưu Ký', 'de-men-phieu-luu-ky', 'Cuộc phiêu lưu của chú Dế Mèn.', 70000, 59000, 80, 'de-men.jpg', 160, 2021, 260),
(6,  3, 3, 'book', 'Đắc Nhân Tâm', 'dac-nhan-tam', 'Nghệ thuật thu phục lòng người.', 86000, 75000, 100, 'dac-nhan-tam.jpg', 320, 2020, 520),
(7,  3, 4, 'book', 'Cha Giàu Cha Nghèo', 'cha-giau-cha-ngheo', 'Bài học tài chính cá nhân.', 98000, NULL, 70, 'cha-giau.jpg', 340, 2019, 380),
(8,  3, 3, 'book', 'Quẳng Gánh Lo Đi Và Vui Sống', 'quang-ganh-lo-di-va-vui-song', 'Cách sống tích cực, bớt lo âu.', 80000, NULL, 45, 'quang-ganh-lo.jpg', 380, 2018, 190),
(9,  5, 4, 'book', 'Clean Code', 'clean-code', 'Cẩm nang viết code sạch cho lập trình viên.', 350000, 315000, 25, 'clean-code.jpg', 464, 2022, 90),
(10, 5, 4, 'book', 'Clean Architecture', 'clean-architecture', 'Nguyên tắc thiết kế kiến trúc phần mềm.', 380000, NULL, 15, 'clean-arch.jpg', 432, 2022, 60),
(11, 4, 2, 'book', 'Truyện Cổ Tích Việt Nam', 'truyen-co-tich-viet-nam', 'Tuyển tập truyện cổ tích cho bé.', 55000, NULL, 90, 'co-tich.jpg', 100, 2021, 140),
(12, 7, NULL, 'stationery', 'Bút bi Thiên Long TL-027', 'but-bi-thien-long-tl-027', 'Bút bi mực xanh, nét 0.5mm, viết êm.', 5000, NULL, 500, 'but-bi.jpg', NULL, NULL, 800),
(13, 7, NULL, 'stationery', 'Bút highlight Stabilo (bộ 4 màu)', 'but-highlight-stabilo-bo-4-mau', 'Bộ 4 bút dạ quang nhiều màu.', 65000, 55000, 120, 'highlight.jpg', NULL, NULL, 210),
(14, 8, NULL, 'stationery', 'Sổ tay lò xo A5 kẻ ngang', 'so-tay-lo-xo-a5-ke-ngang', 'Sổ tay bìa cứng 200 trang.', 45000, NULL, 150, 'so-tay.jpg', 200, NULL, 330),
(15, 8, NULL, 'stationery', 'Vở học sinh Campus 96 trang (lốc 5)', 'vo-hoc-sinh-campus-96-trang-loc-5', 'Lốc 5 quyển vở kẻ ô ly.', 70000, 63000, 200, 'vo-campus.jpg', 96, NULL, 450);

-- Liên kết sách - tác giả (văn phòng phẩm không có tác giả)
INSERT INTO product_authors (product_id, author_id) VALUES
(1,1),(2,1),(3,1),(4,2),(5,5),(6,3),(7,6),(8,3),(9,4),(10,4);

INSERT INTO tags (id, name) VALUES
(1,'tuổi thơ'),(2,'tình yêu'),(3,'kinh điển'),(4,'kỹ năng sống'),(5,'tài chính'),(6,'lập trình'),(7,'thiếu nhi'),(8,'học tập'),(9,'viết');

INSERT INTO product_tags (product_id, tag_id) VALUES
(1,1),(1,2),(2,1),(3,1),(4,3),(5,1),(5,7),(5,3),(6,4),(7,5),(8,4),(9,6),(10,6),(11,7),
(12,8),(12,9),(13,8),(13,9),(14,8),(15,8);

INSERT INTO coupons (code, discount_type, discount_value, max_discount, min_order, usage_limit, start_date, end_date) VALUES
('WELCOME10', 'percent', 10, 30000, 100000, 100, '2026-01-01', '2026-12-31'),
('GIAM20K',   'fixed',   20000, NULL, 150000, NULL, '2026-01-01', '2026-12-31');

-- Vài đơn hàng mẫu để thử gợi ý "thường được mua cùng" và lịch sử đơn
INSERT INTO orders (id, user_id, order_code, receiver_name, receiver_phone, shipping_address, subtotal, discount_amount, shipping_fee, total, payment_method, payment_status, status) VALUES
(1, 2, 'DH20261101001', 'Nguyễn Văn An', '0911111111', '12 Lê Lợi, Q.1, TP.HCM', 170000, 0, 20000, 190000, 'cod', 'paid', 'completed'),
(2, 2, 'DH20261102001', 'Nguyễn Văn An', '0911111111', '12 Lê Lợi, Q.1, TP.HCM', 140000, 0, 20000, 160000, 'bank_transfer', 'paid', 'shipping');

INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity) VALUES
(1, 6,  'Đắc Nhân Tâm', 75000, 1),
(1, 8,  'Quẳng Gánh Lo Đi Và Vui Sống', 80000, 1),
(1, 12, 'Bút bi Thiên Long TL-027', 5000, 3),
(2, 1,  'Mắt Biếc', 95000, 1),
(2, 14, 'Sổ tay lò xo A5 kẻ ngang', 45000, 1);

INSERT INTO reviews (product_id, user_id, rating, comment) VALUES
(6, 2, 5, 'Sách rất hay, đáng đọc.'),
(1, 2, 5, 'Cảm động, giao hàng nhanh.');
