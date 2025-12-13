🛍️ Tổng quan về dự án
Dự án này là một website bán hàng thời trang hoàn chỉnh, có đủ các chức năng từ phía người dùng (mua sắm, giỏ hàng, thanh toán, trang cá nhân) đến phía quản trị (quản lý sản phẩm, đơn hàng, người dùng).

📂 Phân tích chi tiết các thành phần chính
1. ⚙️ Thư mục admin/ (Khu vực quản trị)
Đây là nơi chứa giao diện và logic cho bảng điều khiển (Dashboard) của quản trị viên.

Quản lý cơ bản:

quan_ly_san_pham.php: Quản lý danh sách sản phẩm.

quan_ly_danh_muc.php: Quản lý các danh mục sản phẩm (ví dụ: Áo, Quần, Váy).

quan_ly_nguoi_dung.php: Quản lý tài khoản người dùng và quản trị viên.

quan_ly_bien_the.php: Quản lý các biến thể sản phẩm (kích thước, màu sắc).

quan_ly_ton_kho.php: Quản lý số lượng tồn kho sản phẩm.

Quản lý giao dịch & Marketing:

quan_ly_don_hang.php, chi_tiet_don_hang.php: Quản lý đơn hàng và chi tiết.

quan_ly_thanh_toan.php: Theo dõi các giao dịch thanh toán.

quan_ly_ma_giam_gia.php: Quản lý các mã giảm giá, khuyến mãi.

quan_ly_danh_gia.php: Kiểm duyệt và quản lý đánh giá của khách hàng.

quan_ly_su_kien.php: Quản lý các sự kiện, chương trình khuyến mãi.

quan_ly_diem_tich_luy.php: Quản lý điểm thưởng/điểm tích lũy.

Bảo trì/Hệ thống:

indexadmin.php: Trang chủ quản trị.

nhat_ky_admin.php: Ghi lại các hoạt động của quản trị viên (log).

quan_ly_lien_he.php: Quản lý các thông tin liên hệ/phản hồi từ khách hàng.

quan_ly_tu_khoa.php: Có thể là quản lý các từ khóa SEO hoặc tìm kiếm.

2. 🖥️ Thư mục gốc (shopthoitrang/) và views/ (Giao diện người dùng)
Đây là các tệp tin chính mà người dùng cuối tương tác.

index.php: Trang chủ của website.

san_pham.php: Trang hiển thị danh sách sản phẩm.

chi_tiet_san_pham.php: Trang chi tiết của một sản phẩm.

gio_hang.php: Trang giỏ hàng.

thanh_toan.php, ket_qua_thanh_toan.php: Quy trình và kết quả thanh toán.

su_kien.php, su_kien_chi_tiet.php: Trang hiển thị sự kiện/khuyến mãi.

tim_kiem.php: Trang kết quả tìm kiếm.

lien_he.php: Trang liên hệ.

views/: Chứa các thành phần giao diện được sử dụng lại (header, footer, thẻ sản phẩm...).

3. 🛡️ Thư mục auth/ (Xác thực)
Quản lý các chức năng liên quan đến tài khoản người dùng:

dang_ky.php, dang_nhap.php, dang_xuat.php: Đăng ký, đăng nhập, đăng xuất.

quen_mat_khau.php, dat_lai_mat_khau.php: Chức năng quên và đặt lại mật khẩu.

dang_ky_otp.php: Có thể sử dụng OTP (mã dùng một lần) cho việc đăng ký hoặc xác thực.

4. 👤 Thư mục user/ (Trang cá nhân người dùng)
Các chức năng liên quan đến tài khoản cá nhân của khách hàng:

thong_tin_ca_nhan.php: Xem và chỉnh sửa thông tin cá nhân.

doi_mat_khau.php: Đổi mật khẩu.

lich_su_mua_hang.php, theo_doi_don_hang.php: Lịch sử và theo dõi đơn hàng.

danh_sach_yeu_thich.php: Sản phẩm đã thêm vào danh sách yêu thích.

form_danh_gia.php: Form để gửi đánh giá sản phẩm.

thong_bao.php: Trang hiển thị các thông báo.

5. 🛠️ Thư mục includes/ (Cấu hình & Logic nền)
Chứa các tệp logic nền, cấu hình và lớp (class) hỗ trợ hệ thống:

ket_noi_db.php, cau_hinh.php, ham_chung.php: Kết nối cơ sở dữ liệu, cấu hình hệ thống, hàm tiện ích.

class_gio_hang.php, class_thanh_toan.php: Các lớp xử lý logic giỏ hàng và thanh toán.

class_gui_mail.php, class_otp.php, class_xac_thuc.php: Các lớp hỗ trợ gửi mail, quản lý OTP, và xác thực.

momo/: Toàn bộ logic tích hợp cổng thanh toán MoMo.

6. 🌐 Thư mục api/ (API)
Cung cấp các điểm cuối (endpoints) cho việc tương tác bất đồng bộ (AJAX) hoặc logic backend:

cap_nhat_gio_hang.php, ap_dung_ma_giam_gia.php: Cập nhật giỏ hàng và áp dụng mã giảm giá.

huy_don.php, submit_review.php, them_vao_yeu_thich.php: Xử lý các thao tác của người dùng.

momo_xu_ly.php: Xử lý các phản hồi từ cổng thanh toán MoMo.

thong_bao_thoi_gian_thuc.php: Có thể là API để gửi/nhận thông báo thời gian thực (real-time notification).

7. 💬 Thư mục chat_box/ và websocket_server/
Đây là một tính năng nổi bật: hệ thống chat/thông báo thời gian thực (real-time chat/notifications) dựa trên WebSockets.

websocket_server/server.php, Chat.php: Chạy máy chủ WebSocket (có thể dùng Ratchet - được thấy trong thư mục vendor).

chat_box/chat_ui.php, chat.js, chat.css: Giao diện và logic phía client cho hộp chat.

8. 🖼️ Thư mục assets/ (Tài nguyên)
Chứa các tệp tĩnh (static files) của trang web:

css/, js/: Các tệp CSS và JavaScript (sử dụng cả Bootstrap và các tệp tùy chỉnh).

images/san_pham/: Chứa hình ảnh sản phẩm, banner, v.v.

models/: Các tệp .glb cho thấy dự án này có thể tích hợp mô hình 3D cho sản phẩm (thường dùng cho tính năng xem 3D hoặc thử ảo).
