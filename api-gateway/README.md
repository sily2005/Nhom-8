# API Gateway Service - Strike Shop Microservices

API Gateway đóng vai trò là điểm tiếp nhận trung tâm (Reverse Proxy & Gateway Router) cho toàn bộ hệ thống microservices của đồ án Strike Shop (Nhóm 8).

## 🚀 Chức năng chính
- **Reverse Proxy Routing**: Điều hướng và chuyển tiếp toàn bộ request từ Client (Frontend) đến các microservices nội bộ tương ứng.
- **Microservices Routing Map**:
  - `http://127.0.0.1:8000/api/v1/auth/*` ➔ **Auth Service** (`:8001`)
  - `http://127.0.0.1:8000/api/v1/catalog/*` ➔ **Catalog Service** (`:8002`)
  - `http://127.0.0.1:8000/api/v1/orders/*` ➔ **Order Service** (`:8003`)
  - `http://127.0.0.1:8000/api/v1/payments/*` ➔ **Payment Service** (`:8004`)
- **Forwarding Header & Token**: Đảm bảo toàn vẹn `Authorization: Bearer <token>`, `Content-Type`, `Accept` và các headers xác thực.
- **Centralized Error Handling**: Xử lý timeout, lỗi kết nối service và trả về format chuẩn API Response.

## ⚙️ Cấu hình môi trường (.env)
```env
APP_NAME=api-gateway
APP_URL=http://localhost:8000

AUTH_SERVICE_URL=http://127.0.0.1:8001
CATALOG_SERVICE_URL=http://127.0.0.1:8002
ORDER_SERVICE_URL=http://127.0.0.1:8003
PAYMENT_SERVICE_URL=http://127.0.0.1:8004
```

## 🛠️ Chạy Service
```bash
cd api-gateway
composer install
php artisan serve --port=8000
```

