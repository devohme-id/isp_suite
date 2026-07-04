# Dokumen User Acceptance Testing (UAT) End-to-End
## Sistem Manajemen RT/RW Net & Portal Jaringan FTTH (ISP Suite)

Dokumen ini disusun untuk memverifikasi fungsionalitas seluruh modul sistem secara menyeluruh dari sisi pengguna akhir (Teknisi, Admin, Finance, dan Administrator).

---

### Skenario UAT 1: Otentikasi, Hak Akses & Keamanan
| Langkah Pengujian | Hasil yang Diharapkan | Status |
|---|---|---|
| 1. Buka halaman login di `/login.php`. | Halaman login tampil bersih, responsif, dan mendeteksi tema sistem (light/dark mode). | Pass |
| 2. Input password salah sebanyak 5 kali berturut-turut. | Sistem mendeteksi brute-force dan memblokir upaya login sementara berdasarkan IP. | Pass |
| 3. Login menggunakan user `admin@isp.com` (Administrator). | Akses berhasil, diarahkan ke Dashboard dengan semua menu navigasi aktif di sidebar. | Pass |
| 4. Login menggunakan user `tech@isp.com` (Teknisi). | Diarahkan ke Dashboard, menu finansial/laporan disembunyikan secara otomatis. | Pass |

---

### Skenario UAT 2: Otomasi Tagihan & Verifikasi Pembayaran (Finance)
| Langkah Pengujian | Hasil yang Diharapkan | Status |
|---|---|---|
| 1. Tambah paket internet baru di menu *Paket Internet*. | Paket tersimpan secara real-time dan aktif dalam daftar paket langganan. | Pass |
| 2. Jalankan simulasi cron tagihan `/cron/cron_generate_invoices.php`. | Tagihan baru untuk semua pelanggan aktif otomatis di-generate sesuai tanggal jatuh tempo. | Pass |
| 3. Login sebagai pelanggan dan unggah bukti transfer pembayaran. | Bukti bayar terunggah ke folder `uploads/` dan status invoice berubah menjadi *Pending*. | Pass |
| 4. Buka menu Keuangan → Verifikasi Pembayaran (Admin/Finance), klik *Setujui*. | Status invoice berubah menjadi *Paid*, tanggal bayar tercatat, dan akses internet aktif kembali. | Pass |

---

### Skenario UAT 3: Manajemen Jaringan FTTH (Hulu-ke-Hilir)
| Langkah Pengujian | Hasil yang Diharapkan | Status |
|---|---|---|
| 1. Tambah perangkat OLT baru di tab *Manajemen OLT*. | OLT tersimpan, sistem otomatis meng-generate jumlah port optik default (misal 8 GPON). | Pass |
| 2. Masuk ke Detail OLT, ubah tipe Port 1 menjadi `XGS-PON` dan ubah labelnya. | Parameter port berhasil diperbarui dan status port berubah menjadi *Idle* (Grey). | Pass |
| 3. Buat Kabel Backbone baru di tab *Kabel & Core*, biarkan OLT Asal kosong (Opsional). | Kabel Backbone berhasil dibuat beserta grid matrix tube/core kosong. | Pass |
| 4. Buka Grid Core Backbone, klik Core 1, sambungkan ke OLT X Port 1. | Status core tersimpan dan pemetaan OLT terikat pada tingkat core secara independen. | Pass |
| 5. Di tab *Rumah Kabel*, hubungkan Core 1 Backbone ke Core 1 Distribusi (Splicing). | Koneksi silang tercatat di tabel `rk_connections` secara dinamis. | Pass |
| 6. Buat Drop Point (DP) baru di tab *Drop Point*, pilih RK → Kabel Distribusi → Core 1. | Kode DP otomatis terbentuk dengan format `DP-[Blok][BB][Tube][Core]` secara real-time. | Pass |
| 7. Buka Detail DP, periksa diagram *Upstream Path Tracing*. | Jalur rantai distribusi tergambar lengkap dari: `OLT -> Port OLT -> Core Backbone -> RK -> Core Distribusi -> DP`. | Pass |

---

### Skenario UAT 4: Pendaftaran Pelanggan & Collision Port DP
| Langkah Pengujian | Hasil yang Diharapkan | Status |
|---|---|---|
| 1. Daftarkan pelanggan baru di menu *Daftar Pelanggan*, pilih Drop Point `DP-A010203` Port 1. | Pelanggan berhasil disimpan, port 1 di DP tersebut ditandai sebagai *Occupied*. | Pass |
| 2. Coba daftarkan pelanggan kedua pada Drop Point yang sama di Port 1. | Sistem mendeteksi collision dan menolak pendaftaran dengan pesan error: "Port sudah terisi". | Pass |
| 3. Buka Detail Pelanggan, klik rute upstream. | Peta interaktif menampilkan jalur kabel optik dari rumah pelanggan ke hulu port OLT secara runtut. | Pass |

---

### Skenario UAT 5: Integrasi RouterOS MikroTik
| Langkah Pengujian | Hasil yang Diharapkan | Status |
|---|---|---|
| 1. Konfigurasi kredensial MikroTik di menu *Pengaturan* dan klik *Test Connection*. | Koneksi API teruji sukses dengan response status online. | Pass |
| 2. Ubah status pelanggan menjadi *Suspended* (karena telat bayar). | Sistem memicu API MikroTik untuk memasukkan IP pelanggan ke filter rule drop/isolasi secara instan. | Pass |

---

### Skenario UAT 6: Simulator Redaman (Attenuation Simulator)
| Langkah Pengujian | Hasil yang Diharapkan | Status |
|---|---|---|
| 1. Buka tab *FTTH Simulator* di portal jaringan. | Attenuation simulator termuat di dalam iframe tanpa masalah pemblokiran X-Frame-Options. | Pass |
| 2. Tambah splitter 1:8 dan ukur redaman sirkuit. | Simulator menghitung akumulasi dB loss secara real-time dan akurat. | Pass |
| 3. Ganti tema situs parent dari light ke dark mode. | Iframe simulator mendeteksi perubahan tema parent secara otomatis dan menyesuaikan warna canvas. | Pass |
