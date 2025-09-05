-- schema_sqlite.sql — SQLite schema for Auth Demo
-- (ไม่จำเป็นต้องรัน ถ้าใช้ api.php เพราะมี ensure_schema สร้างให้อัตโนมัติ)
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  username TEXT UNIQUE,
  password_hash TEXT NOT NULL,
  created_at TEXT NOT NULL
);
