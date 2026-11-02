# ResepApp — PHP Native untuk Azure App Service

Aplikasi resep makanan sederhana dengan fitur **CRUD penuh** (Create, Read, Update, Delete) menggunakan **PHP native + SQLite**.

Cocok untuk modul praktikum **Deploy Web App melalui Azure** (Komputasi Awan).

## Fitur

- Daftar resep (Read)
- Tambah resep baru (Create)
- Edit resep (Update)
- Hapus resep (Delete)
- Pencarian berdasarkan nama/bahan
- Filter berdasarkan negara
- Database SQLite (tidak butuh Azure Database terpisah — hemat kuota Free F1)

## Struktur File

```
resep-azure/
├── index.php          ← entry point (wajib di root)
├── data/              ← folder database (otomatis dibuat)
│   └── recipes.db
├── README.md
└── .gitignore
```

## Cara Deploy ke Azure App Service (mengikuti modul)

### 1. Siapkan repository GitHub (Private)

1. Buat repository baru di GitHub (bisa private).
2. Upload semua file di folder ini (pastikan `index.php` ada di **root** repository, bukan di dalam subfolder).
3. Pastikan branch utama bernama `main` atau `master`.

### 2. Buat App Service Plan + Web App

Ikuti langkah di modul praktikum:

- **App Service Plan**: Linux, Region **Indonesia Central**, Pricing **Free F1**
- **Web App**:
  - Name: `web-praktikum-(namamu)`
  - Publish: **Code**
  - Runtime stack: **PHP 8.x** (PHP 8.2 atau 8.3)
  - Operating System: **Linux**
  - Region: sama dengan plan

### 3. Hubungkan ke GitHub (Deployment Center)

1. Di Web App → **Deployment → Deployment Center**
2. Source: **GitHub**
3. Pilih Organization, Repository, Branch
4. Save → tunggu status **Success**

### 4. Akses aplikasi

Buka **Overview** → klik **Default domain**  
URL-nya berbentuk: `https://web-praktikum-namamu.azurewebsites.net`

### Catatan penting untuk Free F1

- Paket F1 hanya punya **60 menit CPU/hari**. Jika website error 403 setelah dipakai lama, tunggu keesokan hari.
- SQLite file disimpan di folder `data/`. Pastikan folder ini writable (Azure biasanya mengizinkan).
- Jika ingin menggunakan Azure Database for MySQL, ubah koneksi di `index.php` (bagian `getDB()`).

## Local Testing

```bash
# Jalankan built-in PHP server
php -S localhost:8000
```

Buka http://localhost:8000

## Tugas Mandiri Checklist

- [x] Web App + Database
- [x] Fitur CRUD lengkap
- [x] PHP native (bukan framework)
- [x] `index.php` di root
- [x] Siap di-deploy via GitHub / VS Code
