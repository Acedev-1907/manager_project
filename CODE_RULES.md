# CODE_RULES.md

## 1. Design Pattern

- Sử dụng Service và Repository pattern cho các logic nghiệp vụ và truy xuất dữ liệu.
- Controller chỉ xử lý request/response, không chứa logic nghiệp vụ.

## 2. Comment Code

- Tất cả comment code phải bằng tiếng việt.
- Comment rõ ràng cho các function, class, và đoạn code phức tạp.

## 3. Đặt Tên (Naming Convention)

- Tên biến, hàm: camelCase (ví dụ: `projectName`, `getUserData`).
- Tên class, interface: PascalCase (ví dụ: `UserService`, `ProjectRepository`).
- Tên file: snake_case cho PHP, kebab-case cho Vue/JS.

## 4. Thông Báo/Message

- Tất cả thông báo cho người dùng (UI/UX) phải bằng tiếng Anh.
- Các thông báo lỗi, thành công phải rõ ràng, dễ hiểu.

## 5. Quy Trình Review & Commit

- Commit message phải ngắn gọn, mô tả đúng nội dung thay đổi, bằng tiếng Anh.

## 6. Quy Ước Khác

- Không sử dụng magic number trực tiếp trong code, hãy định nghĩa constant.
- Tách biệt rõ ràng giữa logic backend (Laravel) và frontend (VueJS).
- Viết unit test cho các service quan trọng.

## 7. Quy Ước Về Định Dạng Code

- Không viết thưa code: Không để thừa dòng trống, căn lề hợp lý, code phải gọn gàng, dễ đọc.

## 8. Quy Ước Giao Tiếp FE với BE

- Tất cả các API call phải sử dụng hàm `makeHttpReq.ts` (không dùng fetch, axios trực tiếp).
- Tất cả các thông báo (thành công, lỗi, xác nhận, cảnh báo,...) phải sử dụng các hàm trong `alert.ts`.
- Tất cả các hàm tiện ích (utility) phía FE phải được đặt trong `utils.ts` và import từ đó.

## 9. Giao diện nút Add/Create và Search trên mobile

- Nút Add/Create (thêm mới) phải luôn hiển thị rõ ràng trên desktop.
- Trên mobile, nút Add/Create có thể ẩn khỏi action bar và chuyển thành nút nổi (fab) ở góc màn hình.
- Ô search trong action bar luôn phải hiển thị rõ ràng trên cả mobile và desktop, không được ẩn.
- Ưu tiên đặt ô search ở action bar, nút Add/Create chỉ hiện ở action bar trên desktop.

## Quy ước CSS cho component Vue

- Ưu tiên sử dụng class utility (Bootstrap/app.css) cho layout, flex, display, align, margin, padding, v.v.
- Chỉ viết CSS mới cho các style thực sự custom: màu sắc, shadow, border-radius, padding/margin đặc biệt, hiệu ứng riêng, v.v.
- Không lặp lại các class utility đã có sẵn trong app.css/Bootstrap.
- Nếu cần override, chỉ override khi thực sự cần thiết và phải ghi chú rõ lý do.

---
