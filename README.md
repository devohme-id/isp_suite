# 🌐 ISP Suite (NetManage Billing)

ISP Suite (atau NetManage Billing) adalah sistem manajemen tagihan dan operasional terintegrasi untuk penyedia layanan internet (ISP) skala mikro, kecil, maupun menengah (RT/RW Net). Aplikasi ini memadukan pencatatan keuangan, manajemen ODP (Optical Distribution Point), manajemen pelanggan, invoicing otomatis, serta sinkronisasi dinamis dengan perangkat MikroTik.

---

## 🚀 Fitur Utama

Sistem ini dirancang untuk memudahkan administrasi harian ISP dengan modul-modul berikut:

- **Dasbor Interaktif**: Visualisasi pendapatan bulanan, jumlah pelanggan aktif/nonaktif, pengeluaran, serta grafik tren finansial.
- **Manajemen Pelanggan**: Integrasi data pelanggan lengkap dengan fitur impor massal menggunakan template Excel (`xlsx`).
- **Tagihan & Invoicing**:
  - Pembuatan invoice tagihan otomatis setiap bulan secara periodik.
  - Cetak invoice ramah printer thermal/kertas standar untuk penagih lapangan.
  - Pencatatan pembayaran lunas maupun tunggakan.
- **Manajemen ODP (Optical Distribution Point)**: Pencatatan letak ODP, kapasitas port fiber optik, dan pemetaan port ODP ke masing-masing pelanggan.
- **Manajemen Paket Layanan**: Pembuatan paket internet kustom dengan masa aktif dan harga yang fleksibel.
- **Integrasi MikroTik**:
  - Autorisasi API MikroTik untuk mengaktifkan/menonaktifkan layanan pelanggan secara real-time berdasarkan status pembayaran mereka.
  - Sinkronisasi terjadwal status pelanggan lokal dengan sistem router MikroTik.
- **Pencatatan Pengeluaran & Laporan Keuangan**: Modul pencatatan kas keluar, rekap laporan rugi laba, serta ekspor data laporan keuangan bulanan.
- **Manajemen Pengguna & Hak Akses**: Sistem otentikasi login dengan pemisahan peran (Owner, Admin, Teknisi, Kolektor Lapangan).

---

## 🛠️ Spesifikasi Teknologi

Sistem dibangun menggunakan stack teknologi berikut:
- **Bahasa Pemrograman**: PHP (Native, berbasis Object-Oriented/PDO)
- **Database**: MySQL / MariaDB
- **Desain & UI**: HTML5, Vanilla CSS, Tailwind CSS (via CDN), Flowbite/Preline components
- **Library Tambahan**:
  - `SimpleXLSX` & `SimpleXLSXGen` (Membaca & menghasilkan file Excel tanpa dependensi berat)
  - `MikrotikApi` (Koneksi router via API PHP)
- **Web Server**: Apache (`.htaccess` disertakan) atau NGINX (`nginx-security.conf` & vhost example disertakan)

---

## 💻 Panduan Instalasi & Konfigurasi

Ikuti langkah-langkah berikut untuk menjalankan aplikasi di lingkungan lokal atau server produksi:

### 1. Prasyarat Sistem
- PHP versi 8.0 atau lebih baru (dengan ekstensi `pdo_mysql`, `openssl`, `gd`, `session`, `snmp` opsional).
- Database MySQL/MariaDB server.
- Web server Apache atau NGINX.

### 2. Kloning Repositori
Kloning repositori ke folder webroot server Anda (misal `htdocs` atau `/var/www/html`):
```bash
git clone https://github.com/devohme-id/isp_suite.git
```

### 3. Konfigurasi Database
1. Buat database baru di MySQL server Anda (misal `isp_billing`).
2. Impor berkas skema database awal dari folder `legacy_db/`:
   ```bash
   mysql -u [username] -p [nama_database] < legacy_db/database.sql
   ```
   *(Atau impor file `isp_billing.sql` jika ingin menggunakan data default awal).*

### 4. Konfigurasi Environment (`.env`)
1. Salin draf berkas `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
2. Buka berkas `.env` dan lengkapi konfigurasi sesuai server Anda:
   ```ini
   APP_NAME="NetManage Billing"
   APP_ENV=production   # Ubah ke 'development' untuk mode pengembangan
   APP_DEBUG=false      # Ubah ke 'true' di lokal untuk melihat error/logging
   APP_URL=http://localhost

   # Konfigurasi Database
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_USER=root
   DB_PASS=PasswordAnda
   DB_NAME=isp_billing
   DB_UNIX_SOCKET_ENABLED=false
   DB_UNIX_SOCKET=/var/run/mysqld/mysqld.sock

   # Keamanan (Ganti dengan 32 karakter acak yang unik dan kuat)
   ENCRYPTION_KEY=x7j1vql4Q62oFzJ17q5ROUsvsKAVOQ6B
   ```

### 5. Konfigurasi Web Server

#### Apache Web Server (XAMPP / WAMP / Laragon)
Aplikasi ini sudah dilengkapi dengan berkas [.htaccess](file:///.htaccess) yang mengamankan folder sensitif dari akses HTTP langsung. Pastikan modul `mod_rewrite` Apache telah aktif.

#### NGINX Web Server
Jika menggunakan NGINX, Anda dapat mengacu pada contoh konfigurasi host di berkas [nginx-vhost.conf.example](file:///nginx-vhost.conf.example). Pastikan berkas [nginx-security.conf](file:///nginx-security.conf) disertakan di dalam server block NGINX Anda untuk mencegah akses langsung ke file konfigurasi atau folder rahasia:
```nginx
include /path/to/project/nginx-security.conf;
```

---

## ⏰ Pengaturan Tugas Otomatis (Cron Jobs)

Untuk memastikan pembuatan tagihan dan sinkronisasi MikroTik berjalan secara otomatis, Anda perlu mengonfigurasi cron jobs.

### Daftar Script Cron

| No | File Script | Kegunaan | Rekomendasi Waktu |
|---|---|---|---|
| 1 | `cron/cron_generate_invoices.php` | Pembuatan tagihan bulanan pelanggan | Harian (pukul 00:01) |
| 2 | `cron/cron_mikrotik_sync.php` | Sinkronisasi status & limitasi MikroTik | Setiap 15 menit |

### Panduan Setup
- **Windows Server / Windows OS**: Silakan ikuti instruksi lengkap di dokumen panduan [SETUP_WINDOWS_SCHEDULER.md](file:///docs/SETUP_WINDOWS_SCHEDULER.md).
- **Linux Server (crontab)**:
  Jalankan `crontab -e` pada server Anda dan tambahkan baris berikut:
  ```bash
  # Generate Invoice Harian pukul 00:01
  01 00 * * * php /var/www/html/isp_suite/cron/cron_generate_invoices.php > /dev/null 2>&1

  # Sinkronisasi MikroTik setiap 15 menit
  */15 * * * * php /var/www/html/isp_suite/cron/cron_mikrotik_sync.php > /dev/null 2>&1
  ```

---

## 📁 Struktur Folder Utama

```text
isp_suite/
├── actions/             # File pemrosesan logika form (POST/GET)
├── cron/                # Script periodik otomatis (generate invoice, sync MikroTik)
├── docs/                # Dokumentasi pendukung aplikasi
├── includes/            # File template global (header, footer, sidebar) & kelas API
├── legacy_db/           # Skema & dump berkas database SQL
├── pages/               # Tampilan UI utama aplikasi (dashboard, list pelanggan, dsb)
├── templates/           # Template file unggah/impor data (Excel)
├── uploads/             # Folder penyimpanan file dinamis (bukti pembayaran/logo)
├── .env                 # Berkas konfigurasi utama (DIABAIKAN oleh Git)
├── .env.example         # Template konfigurasi default
├── .gitignore           # Daftar berkas & folder yang diabaikan Git
├── .htaccess            # Pengaturan keamanan & redirect server Apache
├── config.php           # Pengaturan booting awal & inisialisasi koneksi database PDO
└── index.php            # Gerbang masuk utama aplikasi (landing/redirect)
```

---

## 🔒 Praktik Keamanan Produksi

1. **Jaga Kerahasiaan `.env`**: Jangan pernah mencatat atau membagikan berkas `.env` ke dalam tracking Git.
2. **Generasi Kunci Enkripsi**: Selalu ganti nilai `ENCRYPTION_KEY` di berkas `.env` server produksi dengan kombinasi karakter acak yang kuat (32 karakter).
3. **Batas Akses Folder**: Folder `includes/`, `templates/`, `legacy_db/`, dan berkas `.env` serta `config.php` telah dilindungi dari akses web luar baik via NGINX maupun Apache.
4. **Koneksi MikroTik Dinamis**: Kredensial dan alamat koneksi API MikroTik (Host, Port, User, Password) disimpan sepenuhnya di tabel `settings` database secara dinamis. Kode sistem tidak menyimpan host default atau kredensial default bawaan (*no hardcoded fallbacks*) demi mencegah kebocoran informasi keamanan di lingkungan repositori publik. Pastikan pengaturan diisi lengkap pada halaman Pengaturan sebelum mengaktifkan integrasi.

---
*Dibuat untuk keandalan pengelolaan jaringan dan sistem penagihan RT/RW Net Anda.*
