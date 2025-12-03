# ระบบ API สสจ. มุกดาหาร (SSJ Mukdahan API)

ระบบ API สำหรับจัดการข้อมูลสาธารณสุขจังหวัดมุกดาหาร พัฒนาด้วย PHP (Native/Router) รองรับการเชื่อมต่อข้อมูล Open Data และมีระบบจัดการหลังบ้าน

## 1. การติดตั้งและตั้งค่า (Installation & Setup)

### ความต้องการของระบบ (Requirements)
- PHP 8.0 ขึ้นไป
- Composer
- MySQL หรือ MariaDB

### ขั้นตอนการติดตั้ง
1.  **ติดตั้ง Dependencies**:
    เปิด Terminal ในโฟลเดอร์โปรเจกต์และรันคำสั่ง:
    ```bash
    composer install
    ```

2.  **ตั้งค่า Environment**:
    - คัดลอกไฟล์ `.env.example` เป็น `.env` (หรือสร้างใหม่)
    - แก้ไขค่าการเชื่อมต่อฐานข้อมูล:
      ```env
      DB_HOST=127.0.0.1
      DB_NAME=ssjmukapi
      DB_USER=root
      DB_PASS=รหัสผ่านของคุณ
      JWT_SECRET=คีย์ลับสำหรับJWT_ควรเปลี่ยนให้ยากต่อการเดา
      MOPH_API_URL=https://opendata.moph.go.th/api
      ```

3.  **สร้างฐานข้อมูล**:
    - สร้างฐานข้อมูลชื่อ `ssjmukapi` ใน MySQL
    - นำเข้าไฟล์ `database/schema.sql` เพื่อสร้างตารางต่างๆ

4.  **รันเซิร์ฟเวอร์**:
    ```bash
    php -S localhost:8000 -t public
    ```

---

## 2. โครงสร้างระบบ (System Architecture)

ระบบใช้รูปแบบ MVC (Model-View-Controller) โดยมีโครงสร้างดังนี้:

- `app/Config/`: การตั้งค่าระบบและฐานข้อมูล
- `app/Controllers/`: ตัวควบคุมการทำงานหลัก (API Logic)
- `app/Middleware/`: ระบบตรวจสอบสิทธิ์ (Auth) และความปลอดภัย
- `public/`: โฟลเดอร์สำหรับเข้าถึงผ่านเว็บ (มีไฟล์ `index.php`)
- `routes/`: กำหนดเส้นทาง URL (Routes)
- `storage/cache/`: พื้นที่เก็บไฟล์ Cache ของ Open Data

---

## 3. การใช้งานและการยืนยันตัวตน (Authentication)

ระบบมี 2 รูปแบบการเข้าถึง:

### A. API Key (สำหรับระบบภายนอก)
ใช้สำหรับดึงข้อมูลสาธารณะ เช่น ข้อมูลโรงพยาบาล ข่าวประชาสัมพันธ์
- **Header**: `X-API-KEY: <your_api_key>`
- **ตัวอย่าง Key**: `ssjmuk_api_key_12345` (ค่าเริ่มต้น)

### B. JWT Token (สำหรับผู้ดูแลระบบ)
ใช้สำหรับจัดการข้อมูลใน Dashboard
1.  **Login**: ส่ง POST ไปที่ `/admin/login` เพื่อรับ Token
2.  **ใช้งาน**: แนบ Header `Authorization: Bearer <token>` ในทุกคำขอ

---

## 4. รายการ API (API Endpoints)

### Public Routes (ต้องใช้ X-API-KEY)
- `GET /api/test` - ทดสอบการเชื่อมต่อ API Key
- `GET /api/opendata/{endpoint}` - ดึงข้อมูลจาก MOPH Open Data (ผ่าน Proxy & Cache)
  - ตัวอย่าง: `/api/opendata/covid19`

### Admin Routes (ต้องใช้ JWT)
- `POST /admin/login` - เข้าสู่ระบบ (Body: `username`, `password`)
  - *User เริ่มต้น*: `admin` / `admin123`
- `GET /admin/dashboard/stats` - ดูสถิติภาพรวม

---

## 5. ระบบ Caching (Open Data Proxy)
ระบบจะทำการ Cache ข้อมูลจาก MOPH Open Data ไว้เป็นเวลา **1 ชั่วโมง** เพื่อลดการเรียกไปยังเซิร์ฟเวอร์ต้นทางและเพิ่มความเร็วในการโหลด
- ไฟล์ Cache จะถูกเก็บใน `storage/cache/`
- หากต้องการล้าง Cache สามารถลบไฟล์ในโฟลเดอร์นี้ได้
