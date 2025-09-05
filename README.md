# 📦 PHP + SQLite Auth Demo

ระบบตัวอย่าง **สมัครสมาชิก / ล็อกอิน** พร้อม **หน้า Admin** สำหรับจัดการผู้ใช้  
สร้างด้วย **PHP + SQLite** และใช้ **Tailwind CSS** สำหรับ UI (responsive รองรับทั้ง Mobile / Desktop)

---

## 📂 โครงสร้างไฟล์
```
/project-root
├─ index.php          # หน้า Demo: Login / Register (UI ด้วย Tailwind)
├─ api.php            # API: POST /register, POST /login (ใช้ SQLite, auto-create DB)
├─ admin.php          # หน้า Admin จัดการผู้ใช้ (เพิ่ม/แก้ไข/ลบ/รีเซ็ตรหัส)
└─ auth.db            # ไฟล์ฐานข้อมูล SQLite (สร้างอัตโนมัติเมื่อใช้งานครั้งแรก)
```

---

## ⚙️ ความต้องการ
- PHP 8.0+  
- เปิดส่วนขยาย **PDO SQLite** ใน `php.ini`  
  - ตรวจสอบได้ด้วยคำสั่ง:
    ```bash
    php -m | grep sqlite
    ```
  - หรือเปิดเว็บ `phpinfo()` แล้วดูว่ามี `pdo_sqlite`  

---

## 🚀 วิธีติดตั้ง
1. ดาวน์โหลดไฟล์ทั้งหมดไปวางใน web root ของ PHP (เช่น `htdocs/` หรือ `public_html/`)  
2. เปิดเบราว์เซอร์ไปที่ `index.php` → ทดลองสมัครสมาชิก / ล็อกอินได้ทันที  
3. SQLite จะสร้างไฟล์ `auth.db` อัตโนมัติ (อยู่ข้างไฟล์ `api.php`)  

---

## 🔑 API Endpoints
### สมัครสมาชิก
```
POST /api.php?action=register
Content-Type: application/json
```
```json
{
  "email": "user@example.com",
  "username": "myuser",
  "password": "secret123"
}
```

**Response (201 Created):**
```json
{
  "message": "Registered successfully",
  "user_id": 1
}
```

---

### ล็อกอิน
```
POST /api.php?action=login
Content-Type: application/json
```
```json
{
  "email": "user@example.com",
  "password": "secret123"
}
```

**Response (200 OK):**
```json
{
  "message": "Login success",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "username": "myuser",
    "created_at": "2025-09-05T13:00:00+00:00"
  }
}
```

---

## 🛠 หน้า Admin (`admin.php`)
- ฟีเจอร์:
  - เพิ่มผู้ใช้ใหม่  
  - แก้ไข email/username  
  - รีเซ็ตรหัสผ่าน  
  - ลบผู้ใช้  
  - ค้นหาผู้ใช้  
- UI ทำด้วย Tailwind, ใช้งานได้ทั้งมือถือ/เดสก์ท็อป  

> ⚠️ **สำคัญ**: หน้านี้ **ไม่มีระบบล็อกอิน** (ตามที่ออกแบบไว้)  
> ก่อนนำไปใช้จริง ควรเพิ่ม **JWT / Session / Basic Auth** เอง เพื่อความปลอดภัย  

---

## 📌 หมายเหตุ
- ระบบนี้เป็น **ตัวอย่าง (demo/minimal)**  
- ยัง **ไม่รองรับการเก็บ session** หรือ **JWT token** (ต้องต่อเพิ่มเองถ้าต้องการระบบล็อกอินค้าง)  
- SQLite เหมาะกับ **โปรเจกต์เล็กถึงกลาง** — ถ้าเว็บใหญ่ แนะนำเปลี่ยนไปใช้ MySQL/Postgres  

---

## 🚧 แนวทางปรับแต่งต่อ
- เพิ่ม **JWT (access/refresh token)** → เพื่อให้ API ใช้งานได้ในระบบจริง  
- เพิ่ม **Role (user/admin)** ในตาราง users  
- เพิ่ม **Email verification (OTP / ลิงก์)**  
- ทำ **API เพิ่มเติม**: `/me`, `/logout`, `/refresh`  
- ทำไฟล์ **Postman collection** สำหรับเทส
