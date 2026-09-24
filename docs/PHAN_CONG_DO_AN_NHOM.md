# ⚽ BÁO CÁO TỔNG QUAN KIẾN TRÚC & PHÂN CÔNG ĐỒ ÁN TỐT NGHIỆP
## HỆ THỐNG THƯƠNG MẠI ĐIỆN TỬ THỂ THAO CAO CẤP — STRIKER CYBER-SPORT
> **Mô hình kiến trúc:** Microservices Architecture (Laravel PHP + React TypeScript + MySQL + GHN + MoMo API)  
> **Quy mô nhóm:** 05 Thành viên (05 Microservices Backend + 01 Frontend SPA ứng dụng React)

---

## 📑 MỤC LỤC
1. [TỔNG QUAN KIẾN TRÚC HỆ THỐNG](#1-tổng-quan-kiến-trúc-hệ-thống)
2. [MA TRẬN PHÂN CÔNG NHIỆM VỤ 5 THÀNH VIÊN](#2-ma-trận-phân-công-nhiệm-vụ-5-thành-viên)
3. [CHI TIẾT NHIỆM VỤ TỪNG THÀNH VIÊN](#3-chi-tiết-nhiệm-vụ-từng-thành-viên)
   - [Thành viên 1: Trưởng nhóm & API Gateway + Core Frontend](#thành-viên-1-trưởng-nhóm--api-gateway--core-frontend-system)
   - [Thành viên 2: Auth Service & User Profile + Live Chat](#thành-viên-2-auth-service--user-profile--live-chat)
   - [Thành viên 3: Catalog Service & Cửa hàng + Sản phẩm + Banners](#thành-viên-3-catalog-service--cửa-hàng--sản-phẩm--banners)
   - [Thành viên 4: Order Service & Giỏ hàng + Đơn hàng + GHN + Voucher + Reviews](#thành-viên-4-order-service--giỏ-hàng--đơn-hàng--ghn--voucher--reviews)
   - [Thành viên 5: Payment Service & Cổng thanh toán MoMo + Báo cáo Tài chính](#thành-viên-5-payment-service--cổng-thanh-toán-momo--báo-cáo-tài-chính)
4. [SƠ ĐỒ LUỒNG DỮ LIỆU LIÊN SERVICE (CROSS-SERVICE FLOWS)](#4-sơ-đồ-luồng-dữ-liệu-liên-service)
5. [HƯỚNG DẪN CHẠY DỰ ÁN & KỊCH BẢN BẢO VỆ ĐỒ ÁN](#5-hướng-dẫn-chạy-dự-án--kịch-bản-bảo-vệ-đồ-án)

---

## 1. TỔNG QUAN KIẾN TRÚC HỆ THỐNG

Hệ thống được thiết kế theo tiêu chuẩn **Microservices hiện đại**, chia tách độc lập từng domain nghiệp vụ với cơ sở dữ liệu riêng biệt, giao tiếp thông qua **API Gateway tập trung** và **Reverse Proxy Routing**.

```
                           ┌─────────────────────────────────────────┐
                           │   crs-frontend (React 19 + TypeScript)  │
                           │              Port: 5173                 │
                           └────────────────────┬────────────────────┘
                                                │ REST API (Bearer JWT / Axios)
                                                ▼
                           ┌─────────────────────────────────────────┐
                           │       api-gateway (Laravel Core)        │
                           │              Port: 8000                 │
                           │   (Reverse Proxy, Rate Limit, Auth Guard)│
                           └───────┬─────┬─────────┬─────────┬───────┘
                                   │     │         │         │
          ┌────────────────────────┘     │         │         └────────────────────────┐
          │                              │         │                                  │
          ▼                              ▼         ▼                                  ▼
┌──────────────────┐  ┌────────────────────┐  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│   auth-service   │  │  catalog-service   │  │  order-service   │  │ payment-service  │  │  External APIs   │
│    Port: 8001    │  │     Port: 8002     │  │    Port: 8003    │  │    Port: 8004    │  │  GHN / MoMo IPN  │
├──────────────────┤  ├────────────────────┤  ├──────────────────┤  ├──────────────────┤  ├──────────────────┤
│ • Users & Admin  │  │ • Categories       │  │ • Orders & Items │  │ • MoMo Payment   │  │ • GHN Shipping   │
│ • User Addresses │  │ • Brands           │  │ • Cart & Items   │  │ • Transactions   │  │   Fee & Tracking │
│ • Live Messages  │  │ • Products & Tags  │  │ • Coupons/Voucher│  │ • Payment Logs   │  │ • MoMo Sandbox   │
│ • JWT / Password │  │ • Variants & Images│  │ • Product Reviews│  │ • IPN Webhooks   │  │   AIO QR & ATM    │
│ • DB: auth_db    │  │ • DB: catalog_db   │  │ • DB: order_db   │  │ • DB: payment_db │  │                  │
└──────────────────┘  └────────────────────┘  └──────────────────┘  └──────────────────┘  └──────────────────┘
```

### Bảng cấu hình Ports & Databases:

| Module | Cổng (Port) | Cơ sở dữ liệu | Vai trò chính |
| :--- | :---: | :--- | :--- |
| **`crs-frontend`** | `5173` | LocalStorage / Memory State | Giao diện người dùng Web App Cyber-Sport hiện đại, Responsive đa thiết bị |
| **`api-gateway`** | `8000` | Gateway Log / Cache | Cửa ngõ duy nhất định tuyến request, chặn endpoint nội bộ, xác thực Token tập trung |
| **`auth-service`** | `8001` | `striker_auth_db` | Quản lý người dùng, phân quyền Admin/User, sổ địa chỉ GHN, tin nhắn tư vấn trực tuyến |
| **`catalog-service`**| `8002` | `striker_catalog_db` | Quản lý danh mục, thương hiệu, sản phẩm, biến thể, hình ảnh, nhãn Tag, giá gốc |
| **`order-service`** | `8003` | `striker_order_db` | Quản lý giỏ hàng, đặt hàng, tính phí GHN, tạo vận đơn, voucher giảm giá, đánh giá sao |
| **`payment-service`**| `8004` | `striker_payment_db`| Xử lý thanh toán MoMo Sandbox, xác thực chữ ký số HMAC-SHA256, đối soát giao dịch |

---

## 2. MA TRẬN PHÂN CÔNG NHIỆM VỤ 5 THÀNH VIÊN

| STT | Thành viên & Vai trò | Phụ trách Microservice | Phụ trách Giao diện Frontend (`crs-frontend`) | Điểm nhấn kỹ thuật bảo vệ |
| :---: | :--- | :--- | :--- | :--- |
| **01** | **Thành viên 1**<br>*(Trưởng nhóm & Kiến trúc)* | **`api-gateway`**<br>(Port 8000) | • Khung ứng dụng Base Layout, Header, Footer<br>• Cấu hình Axios Interceptors & Router Guards<br>• Script tự động hóa khởi chạy toàn hệ thống | Gateway Pattern, Reverse Proxy, Bảo mật chặn Endpoint nội bộ SEC-01/02, Centralized CORS |
| **02** | **Thành viên 2**<br>*(Auth & CSKH)* | **`auth-service`**<br>(Port 8001) | • Đăng nhập, Đăng ký, Đổi mật khẩu, Hồ sơ cá nhân<br>• Modal Sổ địa chỉ giao hàng chuẩn GHN<br>• Quản lý Khách hàng Admin & Live Chat Widget | Xác thực phân quyền Token JWT, Quản lý tài khoản, Chat Realtime Polling, Mã hóa bcrypt |
| **03** | **Thành viên 3**<br>*(Catalog & Marketing)* | **`catalog-service`**<br>(Port 8002) | • Trang chủ Home (Banner Slider, Tab Bán chạy)<br>• Trang Cửa hàng Shop (Bộ lọc đa tiêu chí, Search)<br>• Trang Chi tiết Product Detail & Quản trị Sản phẩm | Quản lý Biến thể (Variants), Giá gốc gạch ngang `old_price`, Nhãn Tag (`HOT`, `NEW`), Soft Deletes |
| **04** | **Thành viên 4**<br>*(Order & Logistics)* | **`order-service`**<br>(Port 8003) | • Modal Giỏ hàng & Trang Đặt hàng Checkout<br>• Lịch sử đơn hàng, Tra cứu vận đơn GHN<br>• Quản trị Đơn hàng, Quản lý Voucher, Đánh giá Reviews | Tích hợp API Giao Hàng Nhanh (GHN), Công thức tính giá & Voucher, Phân tích Doanh thu thuần |
| **05** | **Thành viên 5**<br>*(Payment & Dashboard)* | **`payment-service`**<br>(Port 8004) | • Tích hợp luồng thanh toán MoMo QR/ATM/COD<br>• Trang kết quả thanh toán Payment Callback<br>• Biểu đồ Doanh thu Neon Spline Curve Dashboard | Ký số bảo mật MoMo HMAC-SHA256, Webhook IPN xử lý bất đồng bộ, Đối soát dòng tiền |

---

## 3. CHI TIẾT NHIỆM VỤ TỪNG THÀNH VIÊN

---

### 👤 THÀNH VIÊN 1: TRƯỞNG NHÓM & API GATEWAY + CORE FRONTEND SYSTEM
* **Mục tiêu**: Đóng vai trò kỹ sư trưởng, xây dựng hạ tầng cổng ngõ trung tâm (Gateway), điều phối giao tiếp giữa các microservices và kiến trúc tổng thể của Frontend.

#### 1. Backend phụ trách (`api-gateway` - Port 8000):
- [x] **Định tuyến Reverse Proxy**: Tiếp nhận toàn bộ request từ Client và phân luồng chính xác tới các service phía sau:
  - `/api/auth/*`, `/api/users/*`, `/api/chat/*` ➔ `auth-service:8001`
  - `/api/categories/*`, `/api/brands/*`, `/api/products/*`, `/api/banners/*` ➔ `catalog-service:8002`
  - `/api/cart/*`, `/api/orders/*`, `/api/coupons/*`, `/api/reviews/*`, `/api/shipping/*` ➔ `order-service:8003`
  - `/api/payments/*` ➔ `payment-service:8004`
- [x] **Bảo mật Microservices (SEC-01, SEC-02, SEC-03)**:
  - Chặn triệt để quyền truy cập public vào các endpoint nội bộ như `/api/products/deduct-stock` và `/api/products/restore-stock`.
  - Cấu hình CORS tập trung, lọc headers độc hại, chống tấn công Header Injection.
- [x] **Điều hướng MoMo Redirect Callback**: Tiếp nhận callback từ Cổng MoMo và chuyển tiếp trạng thái về đúng URL Frontend React.

#### 2. Frontend phụ trách (`crs-frontend`):
- [x] **Hạ tầng Core & State Provider**:
  - Xây dựng [AppContext.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/context/AppContext.tsx) quản lý Giỏ hàng, Tài khoản người dùng, Vouchers đã lưu và Trạng thái Theme.
  - Xây dựng HTTP Client [api.js](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/services/api.js) với Axios Interceptors: tự động gắn `Authorization: Bearer <token>`, tự động bắt mã lỗi 401 để xử lý đăng xuất.
- [x] **Hệ thống Định tuyến & Điều hướng**:
  - Cấu hình React Router 7 trong [App.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/App.tsx) với `ProtectedRoute` và phân quyền Role (`admin` vs `user`).
  - Xây dựng [Header.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/components/Header.tsx), [Footer.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/components/Footer.tsx), và [AdminLayout.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/layouts/AdminLayout.tsx).
- [x] **Script tự động hóa**: Viết file kịch bản [start-all.ps1](file:///d:/ATRANG/Herd/PROJECT/start-all.ps1) khởi động đồng thời 5 Services + 1 Frontend chỉ bằng 1 câu lệnh.

---

### 👤 THÀNH VIÊN 2: AUTH SERVICE & USER PROFILE + LIVE CHAT
* **Mục tiêu**: Xây dựng toàn bộ hệ thống xác thực, định danh người dùng, quản lý sổ địa chỉ giao hàng và cổng trò chuyện trực tiếp (Live Support Chat).

#### 1. Backend phụ trách (`auth-service` - Port 8001):
- [x] **Xác thực người dùng (Auth Controller)**:
  - Đăng ký tài khoản mới (`POST /api/auth/register`), đăng nhập bảo mật (`POST /api/auth/login`).
  - Cấp phát và thu hồi Token định danh, mã hóa mật khẩu theo thuật toán an toàn `bcrypt`.
  - Phân quyền người dùng dựa trên trường `role` (`user` / `admin`).
- [x] **Quản lý Sổ địa chỉ (Address Controller)**:
  - Quản lý danh sách địa chỉ nhận hàng của khách hàng (`GET/POST/PUT/DELETE /api/addresses`).
  - Hỗ trợ lưu trữ cấu trúc địa chỉ 3 cấp đồng bộ với GHN: Tỉnh/Thành (`province_id`), Quận/Huyện (`district_id`), Phường/Xã (`ward_code`).
  - Xử lý địa chỉ mặc định (`is_default`).
- [x] **Tin nhắn trực tuyến (Chat Controller)**:
  - Lưu trữ và truy xuất lịch sử tin nhắn tư vấn giữa Khách hàng và Quản trị viên (`/api/chat/messages`, `/api/admin/chat/users`).

#### 2. Frontend phụ trách (`crs-frontend`):
- [x] **Các trang Xác thực**:
  - [Login.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/auth/Login.tsx): Giao diện đăng nhập Cyber-Dark, ghi nhớ tài khoản.
  - [Register.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/auth/Register.tsx): Form đăng ký tài khoản mới với validation thời gian thực.
- [x] **Hồ sơ & Sổ địa chỉ Khách hàng**:
  - [Profile.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/account/Profile.tsx): Xem và cập nhật thông tin cá nhân, avatar, đổi mật khẩu.
  - [AddressModal.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/components/AddressModal.tsx): Modal chọn Tỉnh/Huyện/Xã liên thông với API GHN Shipping.
- [x] **Hệ thống Trò chuyện Tư vấn Live**:
  - [ChatWidget.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/components/ChatWidget.tsx): Nút chat nổi góc phải màn hình cho khách hàng.
  - [AdminChatModal.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/components/admin/AdminChatModal.tsx): Giao diện trực chat đa khách hàng thời gian thực cho Admin.
  - [Customers.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/admin/Customers.tsx): Quản lý danh sách khách hàng, khóa/mở khóa tài khoản.

---

### 👤 THÀNH VIÊN 3: CATALOG SERVICE & CỬA HÀNG + SẢN PHẨM + BANNERS
* **Mục tiêu**: Xây dựng kho dữ liệu sản phẩm thể thao, tối ưu hóa tìm kiếm, phân loại đa tầng, hệ thống nhãn Marketing (Tags) và hiển thị giá khuyến mãi.

#### 1. Backend phụ trách (`catalog-service` - Port 8002):
- [x] **Quản lý Danh mục & Thương hiệu**:
  - Quản lý bảng `categories` (Giày bóng đá, Áo đấu, Bóng thi đấu, Phụ kiện) và bảng `brands` (Nike, Adidas, Puma, Mizuno).
  - Khai báo quan hệ liên kết và ràng buộc toàn vẹn dữ liệu.
- [x] **Quản lý Sản phẩm & Biến thể (Product & Variant Controller)**:
  - Quản lý bảng `products` với đầy đủ các trường: `name`, `slug`, `sku`, `price`, `old_price`, `tag`, `stock`, `colors`, `sizes`, `images`.
  - Quản lý bảng `product_variants` lưu trữ SKU con theo Size và Màu sắc.
  - Quản lý nhãn Tag (`NEW`, `HOT`, `BEST SELLER`) phục vụ chiến lược Marketing.
  - Xử lý Soft Deletes (xóa mềm) tránh mất dữ liệu liên kết với đơn hàng cũ.
- [x] **Quản lý Banners Quảng cáo (Banner Controller)**:
  - Quản lý slide ảnh trình diễn trang chủ (`banners`), hỗ trợ bật/tắt hiển thị `is_active` và sắp xếp thứ tự `order`.

#### 2. Frontend phụ trách (`crs-frontend`):
- [x] **Trang chủ & Quảng bá Thương hiệu**:
  - [Home.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/shop/Home.tsx): Hero Banner Slider tự động trượt, Ticker Marquee thể thao, Tab lọc theo sản phẩm `HOT / NEW / SALE`.
  - [HeroBanner.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/components/HeroBanner.tsx): Slider ảnh động với hiệu ứng chuyển cảnh Framer Motion.
- [x] **Trang Danh mục & Tìm kiếm Cửa hàng**:
  - [Shop.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/shop/Shop.tsx): Bộ lọc đa tiêu chí (Danh mục, Hãng sản xuất, Mức giá từ dưới 1M đến trên 3M), sắp xếp theo Giá tăng/giảm, tìm kiếm từ khóa tức thì.
  - [ProductCard.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/components/ProductCard.tsx): Thẻ sản phẩm với Badge giảm giá đỏ `-%`, Badge Tag neon, giá gốc gạch ngang và số sao đánh giá thật.
- [x] **Trang Chi tiết Sản phẩm & Quản trị**:
  - [ProductDetail.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/shop/ProductDetail.tsx): Gallery ảnh nhiều góc chụp, chọn Size, chọn Màu, kiểm tra tồn kho từng biến thể, hiển thị đánh giá.
  - [Products.tsx (Admin)](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/admin/Products.tsx): Bảng quản lý sản phẩm, Modal thêm/sửa sản phẩm với tính năng upload ảnh, gán nhãn tag, cấu hình biến thể.
  - [Settings.tsx (Admin)](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/admin/Settings.tsx): Quản trị Banner slide trang chủ và cấu hình thông tin cửa hàng.

---

### 👤 THÀNH VIÊN 4: ORDER SERVICE & GIỎ HÀNG + ĐƠN HÀNG + GHN + VOUCHER + REVIEWS
* **Mục tiêu**: Xây dựng toàn bộ trái tim thương mại của hệ thống gồm Giỏ hàng, Tính toán Đơn hàng, Tích hợp Logistics GHN, Khuyến mãi Voucher và Hệ thống Đánh giá Sản phẩm.

#### 1. Backend phụ trách (`order-service` - Port 8003):
- [x] **Quản lý Giỏ hàng (Cart Controller)**:
  - Thêm, sửa số lượng, xóa sản phẩm trong giỏ hàng (`/api/cart`).
  - Hỗ trợ lưu trữ giỏ hàng theo từng người dùng hoặc phiên đăng nhập.
- [x] **Quản lý Đơn hàng (Order Controller)**:
  - Tiếp nhận đơn đặt hàng (`POST /api/orders`) với công thức tính doanh thu chuẩn:
    $$\text{Tổng thanh toán} = \text{Tiền hàng (Subtotal)} - \text{Giảm giá Voucher} + \text{Phí vận chuyển GHN}$$
  - Quản lý trạng thái đơn hàng vòng đời 6 bước: `pending` ➔ `processing` ➔ `shipping` ➔ `delivered` ➔ `paid` ➔ `cancelled`.
  - Quản lý trừ/hoàn trả tồn kho an toàn khi tạo/hủy đơn hàng.
- [x] **Tích hợp Logistics Giao Hàng Nhanh (Shipping Controller)**:
  - Kết nối trực tiếp API GHN Sandbox: Lấy danh mục Tỉnh/Thành, Quận/Huyện, Phường/Xã chuẩn quốc gia.
  - Tính phí vận chuyển tự động theo khoảng cách địa lý và khối lượng hàng hóa (`/api/shipping/fee`).
  - Tạo vận đơn GHN tự động và nhận mã bưu vận tra cứu (`ghn_tracking_code`).
- [x] **Quản lý Voucher Khuyến mãi (Coupon Controller)**:
  - Quản lý các loại Voucher: Giảm tiền cố định (`fixed`), Giảm theo phần trăm (`percent`), và Miễn phí vận chuyển (`freeship`).
  - Kiểm tra điều kiện giá trị đơn hàng tối thiểu (`min_order_value`) và giới hạn số lần sử dụng (`usage_limit`).
- [x] **Đánh giá & Xếp hạng Sao (Review Controller)**:
  - Cho phép khách hàng đã mua sản phẩm đánh giá 1-5 sao kèm nhận xét và ảnh minh chứng (`/api/reviews`).
  - Cung cấp API tổng hợp điểm sao trung bình (`GET /api/reviews/summary`) cho thẻ sản phẩm.

#### 2. Frontend phụ trách (`crs-frontend`):
- [x] **Giỏ hàng & Luồng Mua hàng**:
  - [CartModal.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/components/CartModal.tsx): Drawer giỏ hàng trượt mượt mà, chọn nhanh số lượng, tính tổng tạm tính tức thì.
  - [Checkout.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/shop/Checkout.tsx): Form thanh toán chuyên nghiệp, chọn địa chỉ nhận hàng GHN, nhập mã Voucher giảm giá có thông báo chiết khấu tức thì.
- [x] **Quản lý & Theo dõi Đơn hàng**:
  - [Orders.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/shop/Orders.tsx): Lịch sử đơn mua của người dùng, phân loại tab trạng thái đơn, tra cứu mã vận đơn GHN, nút hủy đơn khi còn xử lý.
  - [ReviewModal.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/components/ReviewModal.tsx): Modal chấm điểm sao tương tác động và viết nhận xét sau khi nhận hàng.
- [x] **Quản trị Đơn hàng & Voucher Admin**:
  - [AdminOrders.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/admin/Orders.tsx): Quản lý toàn bộ danh sách đơn hàng, xem chi tiết sản phẩm khách mua, đổi trạng thái đơn, bấm nút giao hàng sang GHN.
  - [Coupons.tsx (Admin)](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/admin/Coupons.tsx): Tạo mới mã giảm giá, cấu hình % hoặc số tiền giảm, cài đặt hạn sử dụng và số lượng phát hành.

---

### 👤 THÀNH VIÊN 5: PAYMENT SERVICE & CỔNG THANH TOÁN MOMO + BÁO CÁO TÀI CHÍNH
* **Mục tiêu**: Xây dựng phân hệ thanh toán trực tuyến an toàn, tích hợp Cổng thanh toán MoMo Sandbox, cơ chế xử lý Webhook IPN ký số và Hệ thống Báo cáo Doanh thu Dashboard.

#### 1. Backend phụ trách (`payment-service` - Port 8004):
- [x] **Tích hợp Cổng MoMo (MoMo All-In-One Gateway)**:
  - Khởi tạo giao dịch thanh toán MoMo QR Code & Thẻ ATM nội địa (`POST /api/payment/momo/initiate`).
  - Tạo chuỗi chữ ký điện tử an toàn chuẩn **HMAC-SHA256** với Secret Key / Access Key từ MoMo Sandbox.
- [x] **Xử lý Webhook IPN (Instant Payment Notification)**:
  - Tiếp nhận và xác thực chữ ký số IPN từ máy chủ MoMo gửi về ngầm khi người dùng quét mã thành công.
  - Đảm bảo tính toàn vẹn (Idempotency), tránh lặp giao dịch và cập nhật trạng thái `paid` sang `order-service`.
- [x] **Quản lý Lịch sử Giao dịch (Transaction & Payment Logs)**:
  - Lưu vết toàn bộ mã giao dịch MoMo (`trans_id`), mã yêu cầu (`request_id`), số tiền và kết quả phản hồi để phục vụ đối soát tài chính.
  - Xử lý hoàn tiền / hủy giao dịch khi đơn hàng bị từ chối.

#### 2. Frontend phụ trách (`crs-frontend`):
- [x] **Tích hợp Phương thức Thanh toán**:
  - Xây dựng phần lựa chọn phương thức thanh toán tại [Checkout.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/shop/Checkout.tsx):
    - 💵 **COD**: Thanh toán tiền mặt khi nhận hàng
    - 💳 **MoMo QR / ATM**: Tự động chuyển hướng sang cổng MoMo quét mã bảo mật
    - 🏦 **VietQR**: Chuyển khoản ngân hàng tự động tạo mã QR có nội dung đơn
- [x] **Trang Kết quả & Xác nhận Thanh toán**:
  - [PaymentCallback.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/shop/PaymentCallback.tsx): Nhận kết quả từ MoMo redirect về, hiển thị thông báo thành công hoặc thất bại sinh động và chuyển hướng vào danh sách đơn.
- [x] **Báo cáo Phân tích Doanh thu & Thống kê Quản trị (Admin Dashboard)**:
  - [Dashboard.tsx](file:///d:/ATRANG/Herd/PROJECT/crs-frontend/src/pages/admin/Dashboard.tsx):
    - Thiết kế **Biểu đồ Cột Neon & Đường cong SVG Spline Curve** phát sáng công nghệ cao hiển thị doanh thu theo mốc thời gian thực tế.
    - Hiển thị đầy đủ 4 thẻ chỉ số KPI: Tổng doanh thu thuần (đã trừ voucher & phí ship), Tổng số sản phẩm đã bán, Tổng số đơn hàng, Số khách hàng hoạt động.
    - Bảng xếp hạng **Top 5 Sản phẩm Bán Chạy Nhất** hiển thị số lượng đã bán thực tế.

---

## 4. SƠ ĐỒ LUỒNG DỮ LIỆU LIÊN SERVICE

```
[1. KHÁCH HÀNG] ──(1. Xem Sản phẩm & Thêm giỏ)──► [catalog-service:8002] & [order-service:8003]
       │
       ├──(2. Chọn Địa chỉ & Tính Phí Ship)──────► [auth-service:8001] & [GHN Logistics API]
       │
       ├──(3. Áp Mã Voucher Khuyến mãi)──────────► [order-service:8003 (Coupons)]
       │
       ├──(4. Đặt Hàng & Chọn MoMo)──────────────► [order-service:8003] ──► [payment-service:8004]
       │                                                                            │
       │                                                               (Tạo HMAC-SHA256 Signature)
       │                                                                            │
       ▼                                                                            ▼
[CỔNG MOMO SANDBOX] ◄──────────────(Khởi tạo URL Thanh toán QR Code)────────────────┘
       │
       ├──(5. Người dùng quét mã trả tiền)
       │
       ├──(6. Webhook IPN ngầm)──────────► [payment-service:8004] ──► [order-service:8003 (paid)]
       │
       └──(7. Trình duyệt Redirect)──────► [api-gateway:8000] ──────► [crs-frontend:5173/orders]
```

---

## 5. HƯỚNG DẪN CHẠY DỰ ÁN & KỊCH BẢN BẢO VỆ ĐỒ ÁN

### 🚀 1. Lệnh Khởi chạy Toàn bộ Hệ thống:
Mở PowerShell tại thư mục gốc của dự án và chạy:
```powershell
./start-all.ps1
```
*Script sẽ tự động kiểm tra database, migration và khởi chạy đồng thời cả 5 Backend Services và 1 Frontend React.*

---

### 🎯 2. Kịch bản Trả lời Hội đồng & Thao tác Demo:

| Câu hỏi của Hội đồng / Giảng viên | Thành viên trả lời chính | Thao tác Demo trên màn hình |
| :--- | :---: | :--- |
| **"Hệ thống định tuyến thế nào và vì sao cần API Gateway?"** | **Thành viên 1** | Mở Network tab trên DevTools chỉ rõ tất cả request đều đi qua `localhost:8000/api/*` chứ Client không gọi trực tiếp vào các cổng 8001, 8002, 8003. Demo việc chặn endpoint nội bộ `deduct-stock`. |
| **"Cơ chế xác thực tài khoản và phân quyền Admin hoạt động ra sao?"** | **Thành viên 2** | Đăng nhập tài khoản User và tài khoản Admin. Mở LocalStorage kiểm tra JWT Token. Bật khung Live Chat nhắn tin thử từ User sang Admin và Admin phản hồi tức thì. |
| **"Tại sao thẻ sản phẩm có tag `HOT`, `NEW` và giá gốc gạch ngang?"** | **Thành viên 3** | Vào Admin Products sửa một sản phẩm thành tag `HOT`, chỉnh giá gốc. Ra ngoài Trang chủ và Cửa hàng F5 xem badge neon và giá gạch ngang hiển thị chuẩn xác từ MySQL `catalog_db`. |
| **"Hệ thống tính tiền đơn hàng và kết nối giao hàng GHN thế nào?"** | **Thành viên 4** | Vào Đặt hàng, thay đổi Tỉnh/Thành phố xem phí vận chuyển GHN tự động nhảy theo địa chỉ. Nhập mã voucher `FREESHIP` xem tiền giảm trừ. Đặt đơn và vào xem mã vận đơn GHN. |
| **"Thanh toán MoMo bảo mật như thế nào và Dashboard tính doanh thu ra sao?"** | **Thành viên 5** | Chọn thanh toán MoMo, quét mã trên ứng dụng MoMo test. Cho xem chữ ký HMAC-SHA256 trong code `payment-service`. Sau đó vào Admin Dashboard xem biểu đồ cột Neon và doanh thu nhảy lên tức thì. |

---

> 📄 **Tài liệu được biên soạn đồng bộ với mã nguồn thực tế của dự án STRIKER Cyber-Sport.**  
> *Chúc nhóm bảo vệ Đồ án Tốt nghiệp đạt kết quả Xuất sắc!* 🎓🏆
