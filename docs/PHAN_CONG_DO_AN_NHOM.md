# ⚽ BÁO CÁO PHÂN CÔNG CÔNG VIỆC — NHÓM 8
## ĐỀ TÀI: HỆ THỐNG THƯƠNG MẠI ĐIỆN TỬ THỂ THAO STRIKER

- **Kiến trúc:** Microservices Decoupled Architecture (Laravel 11 PHP Backend + React 19 TypeScript Frontend + MySQL)
- **Tích hợp bên ngoài & Chuyên đề nâng cao:**
  - 🚚 **Giao Hàng Nhanh (GHN Sandbox Logistics):** Tính phí vận chuyển tự động theo Tỉnh/Huyện/Xã + 1-Click tạo đơn giao vận GHN.
  - 💳 **Cổng thanh toán MoMo AIO (HMAC-SHA256):** Tạo mã QR / URL thanh toán, xử lý Webhook IPN tự động, thanh toán COD.
  - 💬 **Chuyên đề Lab 7:** Tư vấn khách hàng trực tuyến LiveChat (Khách hàng & Bàn trực Admin).
  - 📊 **Chuyên đề Lab 9:** Xử lý, Báo cáo Giao dịch Thanh toán & Thống kê Tài chính Đối soát (Finance & Payment Analytics).
- **Quy mô & Phân bổ:** 5 Thành viên — Đóng góp đồng đều **20% / người** (~1.600 – 1.850 Lines of Code).

---

## 1. TỔNG QUAN HỆ THỐNG MICROSERVICES & CỔNG DỊCH VỤ

| Module / Service | Cổng (Port) | Cơ sở dữ liệu | Chức năng chính |
| :--- | :---: | :--- | :--- |
| **`crs-frontend`** | `5173` | React 19 + TypeScript + Vite | Toàn bộ giao diện Single Page App (Khách hàng Storefront & Quản trị Admin Dark-mode) |
| **`api-gateway`** | `8000` | Gateway Log | Cửa ngõ định tuyến tập trung, bảo mật, CORS và Reverse Proxy điều hướng request |
| **`auth-service`** | `8001` | `striker_auth_db` | Xác thực JWT, Quản lý tài khoản, Sổ địa chỉ GHN 3 cấp, LiveChat CSKH (Lab 7) |
| **`catalog-service`** | `8002` | `striker_catalog_db` | Quản lý Danh mục, Thương hiệu, Sản phẩm, Biến thể Màu/Size SKU, Banner động |
| **`order-service`** | `8003` | `striker_order_db` | Giỏ hàng, Đơn hàng 6 trạng thái, GHN Logistics, Mã giảm giá Vouchers, Đánh giá sao |
| **`payment-service`** | `8004` | `striker_payment_db` | Cổng MoMo (HMAC-SHA256), Webhook IPN, Quản lý Giao dịch & Báo cáo Tài chính (Lab 9) |

---

## 2. BẢNG PHÂN CHIA NHIỆM VỤ CHI TIẾT (MA TRẬN ĐÓNG GÓP 5 THÀNH VIÊN)

| Thành viên | Backend Service (100% File sở hữu) | Frontend Files & Pages (100% File sở hữu) | Lines of Code (LoC) | Tỷ trọng |
| :--- | :--- | :--- | :---: | :---: |
| **Thành viên 1**<br>*(Kiến trúc & Gateway)* | • `api-gateway/app/Http/Controllers/GatewayController.php`<br>• `api-gateway/routes/api.php`<br>• `api-gateway/config/cors.php`, `bootstrap/app.php`<br>• `start-all.ps1` (Script điều phối toàn hệ thống) | • `src/App.tsx`, `src/main.tsx`, `src/types.ts`<br>• `src/context/AppContext.tsx` (State toàn cục)<br>• `src/services/api.js`, `api.d.ts` (Axios Client & Interceptor)<br>• `src/layouts/ShopLayout.tsx`, `AdminLayout.tsx`<br>• `src/components/Header.tsx`, `Footer.tsx`<br>• `src/components/ScrollToTop.tsx`, `Skeleton.tsx` | **~1.600 lines** | **20%** |
| **Thành viên 2**<br>*(Auth & CSKH - Lab 7)* | • `auth-service/app/Http/Controllers/AuthController.php`<br>• `AddressController.php` (Sổ địa chỉ GHN)<br>• `Admin/ChatController.php` (Tin nhắn 2 chiều - Lab 7)<br>• `Models/User.php`, `Address.php`, `Message.php`<br>• `database/migrations/*` & Seeders (`auth_db`) | • `src/pages/shop/LoginPage.tsx`, `RegisterPage.tsx`<br>• `src/pages/shop/ForgotPassword.tsx`, `VerifyEmail.tsx`<br>• `src/pages/shop/Profile.tsx`<br>• `src/components/AddressBookModal.tsx`<br>• `src/components/ChatWidget.tsx`<br>• `src/components/admin/AdminChatModal.tsx`<br>• `src/pages/admin/Customers.tsx`<br>• `src/services/auth.ts`, `src/services/chat.ts` | **~1.750 lines** | **20%** |
| **Thành viên 3**<br>*(Catalog & Sản phẩm)* | • `catalog-service/app/Http/Controllers/ProductController.php`<br>• `CategoryController.php`, `BrandController.php`<br>• `BannerController.php`<br>• `Models/Product.php`, `ProductVariant.php`<br>• `Category.php`, `Brand.php`, `Banner.php`<br>• `database/migrations/*` & Seeders (`catalog_db`) | • `src/pages/shop/Home.tsx`<br>• `src/components/HeroBanner.tsx`<br>• `src/pages/shop/Shop.tsx` (Bộ lọc đa năng & Search)<br>• `src/components/ProductCard.tsx`<br>• `src/pages/shop/ProductDetail.tsx` (Biến thể Màu/Size)<br>• `src/pages/admin/Products.tsx` (Quản trị sản phẩm & SKU)<br>• `src/pages/admin/Settings.tsx` (Quản trị Banner, Danh mục)<br>• `src/services/catalog.ts`, `src/services/banners.ts` | **~1.800 lines** | **20%** |
| **Thành viên 4**<br>*(Order & GHN Logistics)* | • `order-service/app/Http/Controllers/CartController.php`<br>• `OrderController.php` (Vòng đời đơn 6 trạng thái)<br>• `ShippingController.php` & `Services/GhnService.php`<br>• `CouponController.php`, `ReviewController.php`<br>• `Models/Order.php`, `OrderItem.php`, `CartItem.php`, `Cart.php`, `Coupon.php`, `Review.php`<br>• `database/migrations/*` & Seeders (`order_db`) | • `src/components/CartDrawer.tsx`<br>• `src/components/CheckoutAddressCard.tsx`<br>• `src/components/CouponModal.tsx`<br>• `src/pages/shop/Orders.tsx` (Lịch sử đơn & Tra cứu GHN)<br>• `src/components/ReviewModal.tsx` (Đánh giá sao)<br>• `src/pages/admin/Orders.tsx` (Xử lý đơn & nút 1-click GHN)<br>• `src/pages/admin/Vouchers.tsx` (Quản trị mã giảm giá)<br>• `src/services/orders.ts`, `shipping.ts`, `coupons.ts`, `reviews.ts` | **~1.850 lines** | **20%** |
| **Thành viên 5**<br>*(Payment & Tài chính - Lab 9)* | • `payment-service/app/Http/Controllers/MoMoPaymentController.php`<br>• `DashboardController.php` (Thống kê đơn hoàn tất/Doanh thu)<br>• `PaymentLogController.php` / Finance API<br>• `Models/Payment.php`, `PaymentLog.php`, `Transaction.php`<br>• `database/migrations/*` & Seeders (`payment_db`) | • `src/pages/shop/Checkout.tsx` (Cổng COD & MoMo)<br>• `src/pages/shop/PaymentCallback.tsx`<br>• `src/pages/admin/Dashboard.tsx` (Neon Spline Chart, 4 KPI, Top bán chạy)<br>• `src/pages/admin/Finance.tsx` (**Lab 9:** Báo cáo Tài chính, Đối soát MoMo/COD, Hoàn tiền)<br>• `src/services/payment.ts` | **~1.850 lines** | **20%** |

---

## 3. CHI TIẾT FILE VÀ NHÁNH GIT PHỤ TRÁCH TỪNG THÀNH VIÊN

### 👤 Thành viên 1: Kiến trúc Hệ thống, API Gateway & Core Frontend
* **Nhánh Git:** `feature/gateway-core-architecture`
* **Trách nhiệm chính:**
  - Thiết kế kiến trúc định tuyến tập trung Reverse Proxy cho API Gateway, bảo mật CORS.
  - Xây dựng khung giao diện chuẩn (Global Layouts, AppContext, Axios Interceptors tự động đính kèm Bearer Token).
  - Tối ưu hóa hiệu năng, ErrorBoundary chống sập ứng dụng và script khởi chạy 1-click `start-all.ps1`.
* **Backend (`api-gateway`):**
  - `api-gateway/app/Http/Controllers/GatewayController.php`
  - `api-gateway/routes/api.php`, `routes/web.php`
  - `api-gateway/config/cors.php`, `bootstrap/app.php`
  - `start-all.ps1`
* **Frontend (`crs-frontend`):**
  - `src/App.tsx` (Routing & Router Guard)
  - `src/main.tsx`, `src/types.ts`
  - `src/context/AppContext.tsx` (State giỏ hàng, người dùng, modal toàn cục)
  - `src/services/api.js`, `src/services/api.d.ts`
  - `src/layouts/ShopLayout.tsx`, `src/layouts/AdminLayout.tsx`
  - `src/components/Header.tsx`, `src/components/Footer.tsx`
  - `src/components/ScrollToTop.tsx`, `src/components/Skeleton.tsx`

---

### 👤 Thành viên 2: Xác thực JWT, Quản lý Khách hàng & LiveChat CSKH (Lab 7)
* **Nhánh Git:** `feature/auth-service-livechat`
* **Trách nhiệm chính:**
  - Xây dựng hệ thống Đăng nhập / Đăng ký / Quên mật khẩu / Xác thực tài khoản với mã hóa mật khẩu & JWT.
  - Xây dựng Sổ địa chỉ GHN 3 cấp (Tỉnh/Thành, Quận/Huyện, Phường/Xã) liên kết tài khoản.
  - **Lab 7:** Xây dựng Module Tư vấn trực tuyến LiveChat 2 chiều giữa Khách hàng và Bàn trực Admin.
* **Backend (`auth-service`):**
  - `auth-service/app/Http/Controllers/AuthController.php`
  - `auth-service/app/Http/Controllers/AddressController.php`
  - `auth-service/app/Http/Controllers/Admin/ChatController.php` (Lab 7)
  - `auth-service/app/Models/User.php`, `Address.php`, `Message.php`
  - `auth-service/database/migrations/*` & Seeders
* **Frontend (`crs-frontend`):**
  - `src/pages/shop/LoginPage.tsx`, `RegisterPage.tsx`, `ForgotPassword.tsx`, `VerifyEmail.tsx`
  - `src/pages/shop/Profile.tsx` (Hồ sơ người dùng & đổi mật khẩu)
  - `src/components/AddressBookModal.tsx` (Quản lý sổ địa chỉ giao hàng)
  - `src/components/ChatWidget.tsx` (Widget chat góc màn hình cho Khách hàng - Lab 7)
  - `src/components/admin/AdminChatModal.tsx` (Giao diện trực tiếp nhận & trả lời chat Admin - Lab 7)
  - `src/pages/admin/Customers.tsx` (Quản trị khách hàng, khóa/mở tài khoản, thống kê chi tiêu)
  - `src/services/auth.ts`, `src/services/chat.ts`

---

### 👤 Thành viên 3: Quản lý Catalog, Sản phẩm & Trải nghiệm Cửa hàng
* **Nhánh Git:** `feature/catalog-products-shop`
* **Trách nhiệm chính:**
  - Xây dựng Cơ sở dữ liệu và API quản lý Danh mục (Categories), Thương hiệu (Brands), Banner quảng cáo.
  - Quản lý Sản phẩm đa biến thể SKU (Màu sắc, Size, Số lượng tồn kho, Upload nhiều ảnh).
  - Xây dựng giao diện Storefront (Trang chủ, Hero Banner, Bộ lọc Shop đa năng, Chi tiết sản phẩm).
* **Backend (`catalog-service`):**
  - `catalog-service/app/Http/Controllers/ProductController.php`
  - `catalog-service/app/Http/Controllers/CategoryController.php`
  - `catalog-service/app/Http/Controllers/BrandController.php`
  - `catalog-service/app/Http/Controllers/BannerController.php`
  - `catalog-service/app/Models/Product.php`, `ProductVariant.php`, `Category.php`, `Brand.php`, `Banner.php`
  - `catalog-service/database/migrations/*` & Seeders
* **Frontend (`crs-frontend`):**
  - `src/pages/shop/Home.tsx`
  - `src/components/HeroBanner.tsx` (Banner động từ Database)
  - `src/pages/shop/Shop.tsx` (Bộ lọc theo Danh mục, Giá, Thương hiệu & Tìm kiếm thời gian thực)
  - `src/components/ProductCard.tsx`
  - `src/pages/shop/ProductDetail.tsx` (Xem chi tiết, chọn Size/Màu, số lượng)
  - `src/pages/admin/Products.tsx` (Quản trị sản phẩm, biến thể, giá bán & ảnh)
  - `src/pages/admin/Settings.tsx` (Quản trị Banner quảng cáo & Danh mục)
  - `src/services/catalog.ts`, `src/services/banners.ts`

---

### 👤 Thành viên 4: Xử lý Đơn hàng, Logistics GHN, Khuyến mãi & Đánh giá
* **Nhánh Git:** `feature/order-service-ghn-logistics`
* **Trách nhiệm chính:**
  - Xây dựng Vòng đời Đơn hàng 6 trạng thái (Chờ xác nhận, Đang xử lý, Đang giao, Đã giao, Đã hủy, Hoàn tiền).
  - Tích hợp API Logistics Giao Hàng Nhanh (GHN): Tính cước vận chuyển tự động, 1-Click đẩy vận đơn sang GHN.
  - Quản lý Mã giảm giá (Coupons/Vouchers) và hệ thống Đánh giá / Bình luận sao sản phẩm.
* **Backend (`order-service`):**
  - `order-service/app/Http/Controllers/CartController.php`
  - `order-service/app/Http/Controllers/OrderController.php`
  - `order-service/app/Http/Controllers/ShippingController.php` & `app/Services/GhnService.php` (GHN API)
  - `order-service/app/Http/Controllers/CouponController.php`
  - `order-service/app/Http/Controllers/ReviewController.php`
  - `order-service/app/Models/Order.php`, `OrderItem.php`, `CartItem.php`, `Cart.php`, `Coupon.php`, `Review.php`
  - `order-service/database/migrations/*` & Seeders
* **Frontend (`crs-frontend`):**
  - `src/components/CartDrawer.tsx` (Giỏ hàng trượt Drawer)
  - `src/components/CheckoutAddressCard.tsx`
  - `src/components/CouponModal.tsx` (Modal chọn và áp dụng Voucher)
  - `src/pages/shop/Orders.tsx` (Lịch sử mua hàng, tra cứu hành trình vận đơn GHN)
  - `src/components/ReviewModal.tsx` (Đánh giá sao & nhận xét sau khi nhận hàng)
  - `src/pages/admin/Orders.tsx` (Quản trị đơn hàng Admin, 1-click đẩy đơn sang GHN)
  - `src/pages/admin/Vouchers.tsx` (Quản trị danh sách Voucher khuyến mãi)
  - `src/services/orders.ts`, `src/services/shipping.ts`, `src/services/coupons.ts`, `src/services/reviews.ts`

---

### 👤 Thành viên 5: Cổng Thanh toán MoMo/COD, Dashboard & Báo cáo Tài chính (Lab 9)
* **Nhánh Git:** `feature/payment-momo-dashboard-finance`
* **Trách nhiệm chính:**
  - Tích hợp Cổng thanh toán MoMo Sandbox: Tạo mã QR / Redirect URL, Mã hóa chữ ký HMAC-SHA256, Xử lý Webhook IPN tự động cập nhật trạng thái đơn.
  - Xây dựng Bảng điều khiển Analytics Dashboard: Biểu đồ Neon Spline Chart, 4 KPI doanh thu chuẩn `delivered`/`paid`, Top 5 bán chạy.
  - **Lab 9:** Quản trị Báo cáo Tài chính & Đối soát giao dịch (`Finance.tsx`), lọc theo phương thức thanh toán, quản lý yêu cầu Hoàn tiền & Trả hàng.
* **Backend (`payment-service`):**
  - `payment-service/app/Http/Controllers/MoMoPaymentController.php` (Chữ ký số HMAC-SHA256, QR code)
  - `payment-service/app/Http/Controllers/DashboardController.php` (Tổng hợp doanh thu đơn hoàn tất)
  - `payment-service/app/Http/Controllers/PaymentLogController.php` / Finance API (Lịch sử giao dịch, đối soát COD vs MoMo)
  - `payment-service/app/Models/Payment.php`, `PaymentLog.php`, `Transaction.php`
  - `payment-service/database/migrations/*` & Seeders
* **Frontend (`crs-frontend`):**
  - `src/pages/shop/Checkout.tsx` (Cụm phương thức thanh toán COD & MoMo AIO)
  - `src/pages/shop/PaymentCallback.tsx` (Xử lý kết quả trả về từ cổng MoMo)
  - `src/pages/admin/Dashboard.tsx` (Neon Spline Chart, 4 KPI, Top 5 bán chạy)
  - `src/pages/admin/Finance.tsx` (**Chuyên đề Lab 9:** Báo cáo Tài chính, Đối soát dòng tiền MoMo / COD, Xử lý Hoàn tiền)
  - `src/services/payment.ts`

---

## 4. HƯỚNG DẪN KHỞI CHẠY & QUY TRÌNH PHỐI HỢP GIT

### 🚀 Khởi chạy toàn bộ hệ thống (1 Lệnh duy nhất)
Mở PowerShell tại thư mục gốc của dự án:
```powershell
./start-all.ps1
```

* 🌐 **Giao diện Khách hàng & Quản trị:** http://localhost:5173
* 🚪 **Cổng API Gateway:** http://localhost:8000
* 🔑 **Tài khoản Quản trị viên (Admin):** `admin@striker.vn` / `123456`

### 🌿 Quy tắc làm việc trên Git Branch
1. Mỗi thành viên làm việc độc lập trên **nhánh riêng** của mình:
   - Thành viên 1: `git checkout feature/gateway-core-architecture`
   - Thành viên 2: `git checkout feature/auth-service-livechat`
   - Thành viên 3: `git checkout feature/catalog-products-shop`
   - Thành viên 4: `git checkout feature/order-service-ghn-logistics`
   - Thành viên 5: `git checkout feature/payment-momo-dashboard-finance`
2. Trước khi bắt đầu code: Luôn `git pull origin main` để cập nhật mã nguồn mới nhất.
3. Sau khi hoàn thành tính năng: Tạo Pull Request (PR) hoặc báo Team Lead (Thành viên 1) để kiểm thử và Merge vào nhánh `main`.
