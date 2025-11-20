# Mini Drive App - V2

Fitur:
- Registrasi / login user.
- Admin panel + set kuota per user.
- Folder + breadcrumb.
- Upload file:
  - Form biasa: multiple file sekaligus.
  - Drag & drop (AJAX) (ujicoba)
- Preview:
  - Gambar (image/*).
  - PDF.
- Sharing:
  - Public link (token).
  - Share ke user tertentu + "Shared With Me" list.
- Kuota dihitung dari total ukuran file.

## Struktur folder (untuk server)

```
mini-drive-app/
  config/config.php
  database_schema.sql
  public/
    index.php
    register.php
    dashboard.php
    admin.php
    logout.php
    share.php
    shared.php
    view_file.php
    file_action.php
    make_admin.php
    uploads/           <-- folder penyimpanan file (wajib bisa ditulis)
  assets/
    style.css
    app.js
```

Di DewaCloud:
- Set Document Root ke: `.../mini-drive-app/public`

## Setup Database

1. Buat database, contoh: `mini_drive`.
2. Import file: `database_schema.sql`.
3. Edit `config/config.php`:

```php
$host = "localhost";      // atau host MySQL dari DewaCloud
$db   = "mini_drive";
$user = "user_mysql";
$pass = "password_mysql";
```

## Buat Admin

1. Register akun biasa lewat `register.php`.
2. Edit `public/make_admin.php`:

```php
$adminEmail = "email_yang_barusan_register@domain.com";
```

3. Akses di browser: `https://domainkamu/make_admin.php`
4. Kalau sudah sukses, HAPUS file `make_admin.php` demi keamanan.

## Hak akses folder uploads

Pastikan folder:

- `public/uploads`
- dan semua subfolder di dalamnya (`user_1`, `user_2`, dst)

bisa ditulis oleh PHP.

Biasanya cukup set permission:

- `755` atau `775`

(disesuaikan dengan environment DewaCloud).

## Cara pakai singkat

1. User registrasi → login.
2. Di dashboard:
   - Upload file via form atau drag & drop.
   - Buat folder, navigasi dengan breadcrumb.
3. Klik **Share** di file:
   - Bisa bikin **public link**.
   - Bisa share ke user tertentu.
4. User lain akan melihat file tersebut di section **Shared With Me**.
