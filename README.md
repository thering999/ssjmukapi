# ssjmukapi — Public Health Open Data API & Dashboard

ระบบ API และ Dashboard สำหรับเปิดเผยข้อมูลสาธารณสุขจังหวัด (Proxy ข้อมูลจาก MOPH Open Data API) พัฒนาด้วย PHP + MySQL พร้อมหน้าเว็บ Modern UI

> **สำคัญ**: ระบบได้รับการอัปเดตความปลอดภัยและการแสดงผลใหม่ กรุณาอ่านคู่มือการใช้งานด้านล่าง

---

## 🚀 สถานะโปรเจกต์

✅ **พร้อมใช้งาน** — API และ Dashboard ทำงานเต็มรูปแบบ
- **เวอร์ชัน**: v1.1.0 (UX/UI Update)
- **อัปเดตล่าสุด**: 1 ธันวาคม 2568

### Quick Access
- **Dashboard**: [http://localhost:8081/](http://localhost:8081/) (หน้าแรก)
- **Login**: [http://localhost:8081/login](http://localhost:8081/login)
- **API Base URL**: [http://localhost:8081/api/v1](http://localhost:8081/api/v1)
- **Health Check**: [http://localhost:8081/api/v1/health](http://localhost:8081/api/v1/health)
- **Database Admin**: [http://localhost:8082](http://localhost:8082) (Adminer)

---

## ✨ ฟีเจอร์ใหม่ (v1.1.0)

### 1. Modern UX/UI
- **Dashboard**: หน้าแสดงผลข้อมูลภาพรวมด้วยดีไซน์ Glassmorphism
- **Responsive**: รองรับการใช้งานบนมือถือและเดสก์ท็อป
- **Dark Mode**: ธีมสีเข้มสบายตาพร้อมกราฟิกทันสมัย

### 2. Enhanced Security
- **JWT Authentication**: สำหรับ Admin Endpoints (บังคับใช้)
- **API Key Authentication**: สำหรับ MOPH Alert/Notify Integrations
- **Separation of Concerns**: แยก Middleware ชัดเจนระหว่าง JWT และ API Key

---

## 🛠️ การติดตั้งและใช้งาน (Docker)

### 1. เริ่มต้นระบบ
```bash
# 1. Clone หรือไปที่โฟลเดอร์โปรเจกต์
cd d:/www/ssjmukapi

# 2. สร้างไฟล์ .env (ถ้ายังไม่มี)
cp .env.docker .env

# 3. รัน Docker Compose
docker-compose up -d --build
```

### 2. ตรวจสอบสถานะ
```bash
# ดูสถานะ Container
docker-compose ps

# ดู Logs
docker-compose logs -f app
```

### 3. การทดสอบ (Testing)
```bash
# รัน Unit/Feature Tests
docker-compose exec app vendor/bin/phpunit
```

---

## 📚 คู่มือการใช้งาน API

### 1. Authentication

#### Admin Endpoints (`/api/v1/admin/*`)
ต้องใช้ **JWT Token** ในการเข้าถึง
```http
Authorization: Bearer <jwt_token>
```
*หมายเหตุ: รับ Token ได้จากการ Login (กำลังพัฒนา)*

#### Integration Endpoints (`/api/v1/alerts`, `/api/v1/line`)
ต้องใช้ **API Key** ในการเข้าถึง
```http
Authorization: Bearer <api_key>
```

### 2. Open Data Endpoints (Public)

#### เรียกดูข้อมูล 10 อันดับโรคผู้ป่วยนอก
```http
GET /api/v1/opendata/opd10-sex-summary?province=49
```

#### เรียกดูข้อมูล 10 อันดับโรคผู้ป่วยใน
```http
GET /api/v1/opendata/ipd10-sex-summary?province=49
```

### 3. System Endpoints

#### Health Check
```http
GET /api/v1/health
```
Response:
```json
{
  "success": true,
  "data": {
    "status": "ok",
    "time": "2025-12-01T10:00:00+07:00"
  }
}
```

---

## 🔒 ความปลอดภัย (Security Guidelines)

1. **JWT Enforcement**: ระบบไม่อนุญาตให้ใช้ JWT กับ Endpoint ที่ออกแบบมาสำหรับ API Key และกลับกัน
2. **Database Credentials**: เปลี่ยนรหัสผ่าน Database ใน `.env` เมื่อนำขึ้น Production
3. **API Keys**: หมุนเวียน API Key อย่างสม่ำเสมอและเก็บเป็นความลับ

---

## 📂 โครงสร้างโปรเจกต์

```
ssjmukapi/
├── public/                 # Web Root
│   ├── assets/             # CSS, JS, Images
│   └── index.php           # Entry Point
├── src/
│   ├── Controllers/        # API Logic
│   ├── Middlewares/        # Auth & Security
│   ├── Services/           # Business Logic (e.g., MophOpendataService)
│   ├── Support/            # Helpers (View, Response)
│   └── Views/              # HTML Templates
├── tests/                  # PHPUnit Tests
├── docker-compose.yml      # Docker Config
└── README.md               # Documentation
```

---

**ผู้พัฒนา:** สำนักงานสาธารณสุขจังหวัดมุกดาหาร
