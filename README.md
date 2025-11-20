
# 🧾 DewaCloud PHP Form Builder — Final Version

Versi ini adalah **paket final siap upload** ke platform **DewaCloud** tanpa modifikasi tambahan.
Sistem ini bekerja layaknya **Google Form sederhana**, dengan fitur admin, form dinamis, validasi, kuota, tema, ekspor, dan notifikasi otomatis.

---

## 🚀 Fitur Lengkap

### 👑 Untuk Admin
- Login dashboard (admin@example.com / admin123)
- Buat form baru dengan tipe input:
  - Text, Number, Textarea, Radio, Checkbox, Date, File Upload, Email
- Atur properti field:
  - Required, Regex Pattern, Min/Max (angka), Min/Max Length
- Validasi server-side otomatis
- Batasi 1 respon per email
- Tetapkan:
  - Kuota respon (form auto tutup)
  - Waktu buka/tutup (timezone-aware)
  - Tema warna (hex atau preset: emerald, indigo, amber, rose)
- Duplikasi form instan
- Lihat respon real-time
- Export ke **CSV** & **XLSX**
- Landing page publik dengan daftar form
- Notifikasi email otomatis ketika kuota tercapai

### 👤 Untuk Responden
- Akses form publik via URL (slug)
- Validasi otomatis (regex, range, captcha)
- Upload file (disimpan di `assets/uploads/`)
- Tampilan responsif dan ringan
- Status form otomatis nonaktif jika waktu habis / kuota penuh

---

## 🗃️ Struktur Database (MariaDB/MySQL)

Import file: **`init.sql`**

```sql
TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100),
  email VARCHAR(100),
  password_hash VARCHAR(255),
  role ENUM('admin','user')
);

TABLE forms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200),
  description TEXT,
  slug VARCHAR(100),
  created_by INT,
  created_at DATETIME,
  open_at DATETIME NULL,
  close_at DATETIME NULL,
  theme_color VARCHAR(20) NULL,
  max_responses INT NULL,
  theme_preset VARCHAR(20) NULL,
  timezone_str VARCHAR(64) NULL,
  notified_quota TINYINT(1) DEFAULT 0,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

TABLE form_fields (
  id INT AUTO_INCREMENT PRIMARY KEY,
  form_id INT,
  label VARCHAR(200),
  field_type ENUM('text','number','textarea','radio','checkbox','date','file','email'),
  options TEXT,
  required BOOLEAN,
  position INT,
  pattern VARCHAR(255) NULL,
  min_value DOUBLE NULL,
  max_value DOUBLE NULL,
  min_length INT NULL,
  max_length INT NULL,
  unique_per_email TINYINT(1) DEFAULT 0,
  FOREIGN KEY (form_id) REFERENCES forms(id)
);

TABLE responses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  form_id INT,
  user_ip VARCHAR(50),
  submitted_at DATETIME,
  FOREIGN KEY (form_id) REFERENCES forms(id)
);

TABLE response_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  response_id INT,
  field_id INT,
  answer TEXT,
  FOREIGN KEY (response_id) REFERENCES responses(id),
  FOREIGN KEY (field_id) REFERENCES form_fields(id)
);
```

---

## ⚙️ Cara Instalasi di DewaCloud

### 1. Buat Database
Buat database MySQL/MariaDB baru di DewaCloud dan catat:
```
DB_HOST, DB_NAME, DB_USER, DB_PASS
```

### 2. Upload File
Unggah seluruh isi folder ZIP ini ke web root (mis. `/public_html` atau `/var/www/html`).

### 3. Import Database
Gunakan **phpMyAdmin** atau terminal:
```bash
mysql -u DB_USER -p DB_NAME < init.sql
```

### 4. Konfigurasi
Edit `inc/config.php`:
```php
define('DB_HOST','127.0.0.1');
define('DB_NAME','namadb');
define('DB_USER','userdb');
define('DB_PASS','passworddb');

// Email pengirim notifikasi
define('ADMIN_NOTIFY_FROM','no-reply@yourdomain.tld');
```

### 5. Permission
Pastikan folder upload dapat ditulis PHP:
```bash
chmod -R 775 assets/uploads
```

### 6. Login Awal
- URL: `/admin/login.php`
- Email: `admin@example.com`
- Password: `admin123`

Segera ubah password!

---

## 🌐 Jalur Akses

| Halaman | URL Contoh |
|----------|-------------|
| Login Admin | `/admin/login.php` |
| Dashboard | `/admin/dashboard.php` |
| Buat Form | `/admin/form_new.php` |
| Form Publik | `/public/index.php?f=slug-form` |
| Daftar Form Publik | `/public/list.php` |

---

## ✉️ Notifikasi Email (Quota Reached)

- Menggunakan fungsi PHP `mail()`.
- Jika tidak bekerja di DewaCloud, gunakan SMTP:
  - Aktifkan PHPMailer (tambahkan library)
  - Gunakan SMTP Gmail / domain email Anda
- Format notifikasi:
  ```
  Subject: [Forms] Kuota Tercapai: <judul form>
  Body: Form Anda telah mencapai kuota <x>/<y>.
  Link: /admin/responses.php?id=<id>
  ```

---

## 🧩 Troubleshooting

| Masalah | Solusi |
|----------|--------|
| ❌ Tidak bisa login admin | Cek tabel `users`, pastikan `password_hash` sesuai (gunakan password `admin123` default). |
| ⚠️ Tidak bisa upload file | Pastikan folder `assets/uploads` writable (CHMOD 775) dan PHP `file_uploads=On`. |
| 📧 Email tidak terkirim | Pastikan mail() aktif di DewaCloud atau gunakan SMTP (PHPMailer). |
| 🕒 Zona waktu tidak sesuai | Pastikan isi kolom `timezone_str` valid IANA zone (`Asia/Jakarta`, `Asia/Makassar`, dll). |
| 🧮 XLSX tidak bisa dibuka | Gunakan Excel/LibreOffice terbaru. Ekspor dilakukan dalam format SpreadsheetML. |
| 🧱 Database error | Pastikan `init.sql` diimport sepenuhnya dan semua kolom sesuai. |
| 🔒 Form selalu nonaktif | Periksa `open_at`, `close_at`, `max_responses`, dan waktu server sesuai `timezone_str`. |

---

## 🧰 Direktori Penting

| Folder/File | Fungsi |
|--------------|--------|
| `admin/` | Semua halaman admin (buat/edit/export/duplicate form) |
| `public/` | Form publik dan daftar form |
| `inc/` | Koneksi DB & autentikasi |
| `assets/` | CSS dan file upload |
| `init.sql` | Struktur database |
| `README.md` | Panduan ini |

---

## 🛡️ Keamanan Tambahan (Opsional)
- Ganti semua password default.
- Hapus file `init.sql` setelah setup.
- Batasi akses folder `/admin` dengan .htaccess jika perlu.
- Gunakan HTTPS.
- Aktifkan reCAPTCHA (bisa integrasi nanti).

---

## 🏁 Selesai
Sekarang aplikasi sudah siap dijalankan langsung di DewaCloud 🎉

Cukup upload, ubah konfigurasi `config.php`, import database, dan login.
