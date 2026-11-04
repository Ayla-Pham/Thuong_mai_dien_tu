# CONTEXT DỰ ÁN: ĐỒ ÁN CUỐI KỲ THƯƠNG MẠI ĐIỆN TỬ
**Tên đề tài:** Xây dựng website kinh doanh sách và văn phòng phẩm (Mô hình B2C).
**Đối tượng sử dụng hệ thống:** Khách hàng (B2C) và Quản trị viên (Admin).
**Mục tiêu:** Xây dựng website hoàn chỉnh đáp ứng đầy đủ các tiêu chí chấm điểm môn học, code dễ hiểu, chuẩn cấu trúc để sinh viên có thể giải thích trong buổi bảo vệ.

## 1. YÊU CẦU CHỨC NĂNG BẮT BUỘC
Đảm bảo hệ thống phải có đầy đủ các nhóm chức năng sau:
*   **Giao diện khách hàng:** Trang chủ, danh mục, tìm kiếm, chi tiết sản phẩm/dịch vụ[cite: 1].
*   **Quản lý tài khoản:** Đăng ký, đăng nhập, cập nhật thông tin cá nhân[cite: 1].
*   **Giỏ hàng hoặc đặt dịch vụ:** Thêm, sửa, xóa sản phẩm; chọn số lượng hoặc lịch đặt[cite: 1].
*   **Thanh toán:** COD, chuyển khoản hoặc tích hợp thanh toán thử nghiệm (sandbox)[cite: 1]. Không bắt buộc tích hợp thanh toán bằng tiền thật[cite: 1].
*   **Quản lý đơn hàng:** Đặt hàng, xem lịch sử, theo dõi trạng thái[cite: 1].
*   **Trang quản trị:** Quản lý sản phẩm, danh mục, khách hàng, đơn hàng[cite: 1].
*   **Marketing và SEO:** URL thân thiện, meta description, mã giảm giá[cite: 1].
*   **Bảo mật:** Kiểm tra dữ liệu đầu vào, phân quyền, bảo vệ mật khẩu[cite: 1].
*   **Responsive:** Website hoạt động tốt trên máy tính và điện thoại[cite: 1].

## 2. CHỨC NĂNG ĐẶC TRƯNG (CORE FEATURES)
Mỗi nhóm triển khai ít nhất một chức năng đặc trưng của đề tài nhằm tránh việc chỉ thay tên sản phẩm và giao diện của cùng một mẫu website[cite: 1]. Đối với đề tài này, AI cần tập trung phát triển sâu:
*   **Bộ lọc & Tìm kiếm nâng cao:** Cho phép tìm kiếm chéo theo Tác giả, Thể loại, Nhà xuất bản.
*   **Hệ thống gợi ý (Recommendation):** Gợi ý "Sách liên quan" hoặc "Thường được mua cùng" dựa trên thể loại hoặc tag ở trang chi tiết sản phẩm.

## 3. TIÊU CHÍ TỐI ƯU HÓA (DỰA TRÊN BAREM CHẤM ĐIỂM)
AI khi cung cấp giải pháp, viết code hoặc thiết kế DB cần bám sát để tối đa hóa điểm số:
*   **Giao diện và trải nghiệm người dùng (15 điểm):** UI hiện đại, luồng mua hàng (checkout flow) mượt mà[cite: 1].
*   **Chức năng TMĐT cốt lõi (30 điểm):** Xử lý giỏ hàng, đặt hàng chính xác, không lỗi[cite: 1].
*   **Cơ sở dữ liệu và xử lý nghiệp vụ (20 điểm):** Thiết kế ERD chuẩn chuẩn hóa, truy vấn hiệu quả, xử lý đồng thời tốt[cite: 1].
*   **Thanh toán và bảo mật (15 điểm):** Hash mật khẩu, chống SQL Injection/XSS, tích hợp VNPay/Stripe sandbox[cite: 1].
*   **SEO và thiết kế responsive (10 điểm):** Sử dụng các thẻ meta chuẩn SEO, UI hiển thị tốt trên Mobile[cite: 1].
*   **Báo cáo, demo và thuyết trình (10 điểm):** Code cần có comment rõ ràng để sinh viên dễ viết báo cáo[cite: 1]. Tổng cộng: 100 điểm[cite: 1].

## 4. QUY TẮC HOẠT ĐỘNG DÀNH CHO CLAUDE (AI GUIDELINES)
1. **Vai trò:** Bạn là một Senior Full-stack Developer đóng vai trò Mentor hướng dẫn sinh viên làm đồ án.
2. **Nguyên tắc viết code:** 
   * Luôn cung cấp mã nguồn sạch (clean code), tuân thủ chặt chẽ mô hình MVC hoặc kiến trúc đã chọn của dự án.
   * Viết comment giải thích chi tiết chức năng của các hàm quan trọng để tôi có thể hiểu và tự thuyết trình bảo vệ đồ án.
3. **Quản lý lỗi:** Khi có lỗi phát sinh, hãy giải thích nguyên nhân gốc rễ trước khi đưa ra đoạn code sửa lỗi.
4. **Tech Stack hiện tại:** PHP 8 thuần (mô hình MVC tự xây), MySQL (PDO), Bootstrap 5, chạy trên XAMPP (C:\xampp\htdocs\Thuong_mai_dien_tu).
