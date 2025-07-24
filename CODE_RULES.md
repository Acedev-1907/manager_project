# CODE_RULES.md

## 1. Design Pattern

- Sử dụng Service và Repository pattern cho các logic nghiệp vụ và truy xuất dữ liệu.
- Controller chỉ xử lý request/response, không chứa logic nghiệp vụ. Tất cả logic (kể cả kiểm tra, validate, thao tác model, truy vấn,...) phải chuyển sang Service/Repository. Controller chỉ gọi các hàm service và trả về response chuẩn. Service chỉ thao tác với Repository, không thao tác trực tiếp với Model.

## 2. Comment Code

- Tất cả comment code phải bằng tiếng anh.
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

## 10. Quy Ước API Response

- Tất cả các API controller trả về response phải kế thừa từ `ApiController` (`app/Http/Controllers/Api/ApiController.php`).
- Sử dụng các hàm chuẩn trong `ApiController` để trả về dữ liệu/thông báo/lỗi, ví dụ: `respondWithData`, `respondWithError`, `respondNotFound`, `respondCreated`, v.v.
- Không trả về response "thô" (raw) hoặc tự tạo response JSON trong controller.
- Các mã code trả về (code, status_code) phải tuân theo constant đã định nghĩa trong `ApiController`.
- Thông báo trả về cho FE phải bằng tiếng Anh, rõ ràng, dễ hiểu.

## 11. Quy ước UI/UX cho Member/Invitation

- Tất cả các card (member, invitation, sent invitation) phải đồng bộ layout: avatar, tên, action (menu/nút) cùng 1 hàng, căn giữa dọc.
- Dùng chung 1 component table/grid (MemberTable), truyền card qua slot để custom nội dung.
- Action nhiều hơn 1 (VD: Chat, Remove) phải dùng menu 3 chấm, menu phải đóng khi click ra ngoài, không dùng thư viện ngoài nếu không cần thiết.
- Kích thước avatar, nút, font, hover phải đồng nhất giữa các card.
- Card phải gọn gàng, không để trống quá nhiều, spacing hợp lý, sử dụng flexbox/grid để căn chỉnh.
- Không lặp lại code layout giữa các card, ưu tiên tái sử dụng component hoặc slot.
- Tên file, class phải rõ ràng, thống nhất: MemberCard.vue, InvitationCard.vue, SentInvitationCard.vue, .member-card, .menu-wrapper, .action-btns...
- Các action (remove, accept, decline, chat) phải emit ra ngoài, không xử lý logic trực tiếp trong card.
- Comment rõ ràng cho các đoạn code xử lý UI đặc biệt (menu, click outside, v.v.).

## 12. Quy Ước Xử Lý API Response Chuẩn Cho FE

- Tất cả API trả về phải có cấu trúc:

  ```json
  {
    "code": 1000, // mã code, 1000 là thành công, các mã khác là lỗi
    "data": { ... }, // dữ liệu trả về (object, array, hoặc null)
    "message": "Thông báo rõ ràng, tiếng Anh"
  }
  ```

- FE luôn xử lý theo quy tắc sau:

  - Thành công: `code === 1000`, lấy dữ liệu từ `data`, lấy thông báo từ `message`.
  - Lỗi: `code !== 1000`, lấy thông báo lỗi từ `message`.

- Không truy cập trực tiếp vào các field ngoài `data`, `code`, `message`.

  - Không lấy dữ liệu từ ngoài `data` (ví dụ: không dùng `data.link` mà phải dùng `data.data.link`).
  - Không kiểm tra thành công bằng `res.ok` hoặc status code HTTP, chỉ dùng `data.code`.

- Luôn sử dụng các hàm alert chuẩn (`showSuccess`, `showError`, `showConfirm`) để hiển thị thông báo cho người dùng.

- Không tự ý thay đổi format response khi xử lý, chỉ đọc đúng theo format BE trả về.

- Ví dụ xử lý chuẩn trong FE:

  ```js
  const res = await fetch(url, options);
  const data = await res.json();
  if (data.code === 1000) {
    // Thành công
    // Xử lý data.data, hiển thị data.message nếu cần
    showSuccess(data.message || "Action successful!");
  } else {
    // Lỗi
    showError(data.message || "Action failed!");
  }
  ```

- Áp dụng cho tất cả API: GET, POST, PUT, DELETE, upload file, ...

## 13. Quy Ước Sử Dụng Icon Cho Tab Bar Và Nút Add Member (Member Management)

- Tab bar ở trang Member Management phải dùng icon chuẩn cho từng tab, đặc biệt trên mobile:
  - Members: sử dụng icon `bi-people` (Bootstrap Icons)
  - Sent Invitations: sử dụng icon `bi-send`
  - Received Invitations: sử dụng icon `bi-inbox`
- Nút Add Member (Floating Action Button - FAB) trên mobile phải dùng icon `bi-person-plus`.
- Không thay đổi icon này nếu không có lý do rõ ràng về UX/UI hoặc được duyệt bởi team.
- Nếu muốn đổi icon, phải cập nhật lại quy ước này và thông báo cho toàn bộ team FE.
- Không xóa icon hoặc thay bằng text thuần trên mobile, luôn giữ icon để đảm bảo nhận diện và nhất quán.
- Nếu dùng bộ icon khác (Heroicons, Material Icons...), phải ghi rõ mapping tương ứng trong tài liệu này.

## 14. Quy Ước Xử Lý Realtime (Event) Cho Member/Friend/Invitation

- Khi nhận event realtime (qua Echo/Pusher/Websocket) liên quan đến member/friend/invitation (ví dụ: accept, remove, invitation_sent, invitation_declined, invitation_cancelled, invitation_accepted, ...),
  - **Không gọi API fetch dữ liệu nhiều lần liên tiếp** nếu nhận nhiều event trong thời gian ngắn.
  - **Luôn sử dụng debounce hoặc throttle** (ví dụ lodash.debounce) để gom các lần gọi API fetch thành 1 lần duy nhất trong một khoảng thời gian ngắn (300-500ms).
  - Đảm bảo UI luôn cập nhật đúng, không bị lag, không gây quá tải server.
  - Nếu cần cập nhật nhiều tab (Members, Sent, Received), chỉ fetch tab đang active hoặc ưu tiên fetch tab quan trọng nhất.
  - Logic xử lý event nên tách ra file composable/helper riêng để tái sử dụng và dễ bảo trì.

---
