# Panduan Setup Jadwal Periodik - Windows Task Scheduler

Dokumen ini menjelaskan cara mengatur jadwal periodik untuk menjalankan script PHP cron menggunakan Windows Task Scheduler.

---

## 📋 Daftar Script Cron

| No | Script | Fungsi | Jadwal Rekomendasi |
|----|--------|--------|-------------------|
| 1 | `cron_generate_invoices.php` | Generate tagihan bulanan otomatis | **Setiap hari pukul 00:01** |
| 2 | `cron_mikrotik_sync.php` | Sinkronisasi status customer dengan MikroTik | **Setiap 15 menit** |

---

## 🔧 Persiapan

### 1. Tentukan Lokasi PHP CLI
Cari path PHP executable di sistem Windows Anda:
- **XAMPP**: `C:\xampp\php\php.exe`
- **WAMP**: `C:\wamp64\bin\php\php8.x.x\php.exe`
- **Laragon**: `C:\laragon\bin\php\php-8.x.x-Win32-vs16-x64\php.exe`
- **MAMP for Windows**: `C:\MAMP\bin\php\php8.x.x\php.exe`

### 2. Tentukan Lokasi Script Cron
Lokasi folder cron di webserver Anda:
```
C:\path\to\your\webroot\billing-wifi\cron\
```

Contoh:
- XAMPP: `C:\xampp\htdocs\billing-wifi\cron\`
- WAMP: `C:\wamp64\www\billing-wifi\cron\`

---

## 📝 Langkah-Langkah Setup Task Scheduler

### Task 1: Generate Invoice (Harian)

#### A. Buka Task Scheduler
1. Tekan **Win + R**, ketik `taskschd.msc`, tekan **Enter**
2. Atau cari "Task Scheduler" di Start Menu

#### B. Buat Task Baru
1. Klik **Create Basic Task...** di panel kanan
2. Isi nama: `Billing WiFi - Generate Invoice`
3. Deskripsi: `Generate tagihan bulanan otomatis untuk semua pelanggan aktif`
4. Klik **Next**

#### C. Set Trigger (Jadwal)
1. Pilih **Daily**
2. Klik **Next**
3. Set Start time: `00:01:00`
4. Recur every: `1` days
5. Klik **Next**

#### D. Set Action
1. Pilih **Start a program**
2. Klik **Next**
3. Isi:
   - **Program/script**: 
     ```
     C:\xampp\php\php.exe
     ```
   - **Add arguments**: 
     ```
     C:\xampp\htdocs\billing-wifi\cron\cron_generate_invoices.php
     ```
   - **Start in (optional)**: 
     ```
     C:\xampp\htdocs\billing-wifi\cron
     ```
4. Klik **Next**
5. Klik **Finish**

#### E. Konfigurasi Tambahan (Penting!)
1. Klik kanan task yang baru dibuat → **Properties**
2. Tab **General**:
   - ✅ Centang "Run whether user is logged on or not"
   - ✅ Centang "Run with highest privileges"
3. Tab **Settings**:
   - ✅ Centang "Allow task to be run on demand"
   - ✅ Centang "Run task as soon as possible after a scheduled start is missed"
4. Klik **OK**, masukkan password Windows jika diminta

---

### Task 2: MikroTik Sync (Setiap 15 Menit)

#### A. Buat Task Baru
1. Klik **Create Task...** (bukan Basic Task, agar bisa set interval < 1 hari)
2. Tab **General**:
   - Name: `Billing WiFi - MikroTik Sync`
   - Description: `Sinkronisasi status customer dengan MikroTik setiap 15 menit`
   - ✅ "Run whether user is logged on or not"
   - ✅ "Run with highest privileges"

#### B. Tab Triggers
1. Klik **New...**
2. Begin the task: **On a schedule**
3. Settings: **Daily**
4. Start: Hari ini, pukul `00:00:00`
5. ✅ Centang **Repeat task every**: `15 minutes`
6. For a duration of: `1 day`
7. ✅ Enabled
8. Klik **OK**

#### C. Tab Actions
1. Klik **New...**
2. Action: **Start a program**
3. Isi:
   - **Program/script**: 
     ```
     C:\xampp\php\php.exe
     ```
   - **Add arguments**: 
     ```
     C:\xampp\htdocs\billing-wifi\cron\cron_mikrotik_sync.php
     ```
   - **Start in**: 
     ```
     C:\xampp\htdocs\billing-wifi\cron
     ```
4. Klik **OK**

#### D. Tab Settings
1. ✅ Allow task to be run on demand
2. ✅ Run task as soon as possible after a scheduled start is missed
3. ✅ If the task is already running, then: **Do not start a new instance**
4. Klik **OK**, masukkan password jika diminta

---

## 🧪 Testing

### Test Manual dari Command Line
Buka **Command Prompt** (Run as Administrator):

```cmd
REM Test Generate Invoice
C:\xampp\php\php.exe C:\xampp\htdocs\billing-wifi\cron\cron_generate_invoices.php

REM Test MikroTik Sync
C:\xampp\php\php.exe C:\xampp\htdocs\billing-wifi\cron\cron_mikrotik_sync.php
```

### Test dari Task Scheduler
1. Klik kanan pada task → **Run**
2. Cek kolom "Last Run Result": harus `0x0` (sukses)

---

## 📊 Monitoring & Logging

### Aktifkan Logging ke File (Opsional)
Untuk menyimpan output script ke file log, modifikasi **Add arguments** menjadi:

```
C:\xampp\htdocs\billing-wifi\cron\cron_generate_invoices.php >> C:\xampp\htdocs\billing-wifi\logs\cron_invoices.log 2>&1
```

> **Note**: Buat folder `logs` terlebih dahulu jika belum ada.

### Cek History Task
1. Buka Task Scheduler
2. Klik task yang ingin dilihat
3. Tab **History** di panel bawah

---

## ⚠️ Troubleshooting

### Error: Task gagal dengan kode 0x1
- **Penyebab**: Path PHP atau script salah
- **Solusi**: Pastikan path menggunakan backslash (`\`) dan path lengkap

### Error: Access Denied
- **Penyebab**: Permission issue
- **Solusi**: 
  - Jalankan Task Scheduler as Administrator
  - Pastikan "Run with highest privileges" dicentang

### Script tidak berjalan saat komputer sleep
- **Solusi**: 
  - Tab **Conditions** → Uncheck "Start the task only if the computer is on AC power"
  - Tab **Settings** → Check "Wake the computer to run this task"

### Task tidak berjalan tepat waktu
- **Solusi**: Pastikan Windows Time Service aktif dan waktu sistem akurat

---

## 📅 Ringkasan Jadwal

| Task | Jadwal | Interval |
|------|--------|----------|
| Generate Invoice | 00:01 setiap hari | Daily |
| MikroTik Sync | Sepanjang hari | Setiap 15 menit |

---

## 🔒 Security Best Practices

1. **Gunakan akun khusus** - Buat Windows user khusus untuk menjalankan task
2. **Minimal privileges** - Berikan hanya akses yang diperlukan
3. **Lindungi credentials** - Jangan hardcode password di script
4. **Monitor logs** - Review log secara berkala

---

*Dokumen ini dibuat untuk ISP Suite & FTTH Manager*
*Terakhir diupdate: Januari 2026*
