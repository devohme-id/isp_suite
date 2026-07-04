<?php
require_once '../config.php';
require_login();

$page_title = 'Dokumen User Acceptance Testing (UAT) - ISP Suite';
include '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen transition-all duration-300 ease-in-out">
    <div class="p-4 sm:p-6 space-y-6">
        
        <!-- Breadcrumbs & Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                 <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Dokumen UAT</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">User Acceptance Testing (UAT)</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Dokumen rujukan pengujian sistem penagihan RT/RW Net & topologi Jaringan FTTH secara end-to-end.</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl p-6 shadow-sm space-y-8">
            <div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Verifikasi Fungsionalitas Modul Utama</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">Tabel berikut menjabarkan skenario pengujian fungsionalitas aplikasi dan status kelayakannya di lapangan.</p>
            </div>

            <!-- Skenario 1 -->
            <div class="space-y-3">
                <h3 class="text-sm font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Skenario UAT 1: Otentikasi & Keamanan</h3>
                <div class="overflow-x-auto border border-gray-100 dark:border-slate-700 rounded-xl">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Langkah Pengujian</th>
                                <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Hasil yang Diharapkan</th>
                                <th class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">1. Buka halaman login di <code>/login.php</code>.</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">Halaman login tampil bersih, responsif, dan mendeteksi tema sistem (light/dark mode).</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">2. Input password salah sebanyak 5 kali berturut-turut.</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">Sistem mendeteksi brute-force dan memblokir upaya login sementara berdasarkan IP.</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">3. Login menggunakan user <code>admin@isp.com</code> (Administrator).</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">Akses berhasil, diarahkan ke Dashboard dengan semua menu navigasi aktif di sidebar.</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Skenario 2 -->
            <div class="space-y-3">
                <h3 class="text-sm font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Skenario UAT 2: Otomasi Tagihan & Pembayaran (Finance)</h3>
                <div class="overflow-x-auto border border-gray-100 dark:border-slate-700 rounded-xl">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Langkah Pengujian</th>
                                <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Hasil yang Diharapkan</th>
                                <th class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">1. Tambah paket internet baru di menu <em>Paket Internet</em>.</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">Paket tersimpan secara real-time dan aktif dalam daftar paket langganan.</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">2. Jalankan cron tagihan <code>/cron/cron_generate_invoices.php</code>.</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">Tagihan baru untuk semua pelanggan aktif otomatis di-generate sesuai tanggal jatuh tempo.</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">3. Buka menu Keuangan → Verifikasi Pembayaran, klik <em>Setujui</em>.</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">Status invoice berubah menjadi <em>Paid</em>, tanggal bayar tercatat, dan akses internet aktif kembali.</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Skenario 3 -->
            <div class="space-y-3">
                <h3 class="text-sm font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Skenario UAT 3: Manajemen Jaringan FTTH (Hulu-ke-Hilir)</h3>
                <div class="overflow-x-auto border border-gray-100 dark:border-slate-700 rounded-xl">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Langkah Pengujian</th>
                                <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Hasil yang Diharapkan</th>
                                <th class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">1. Tambah perangkat OLT baru di tab <em>Manajemen OLT</em>.</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">OLT tersimpan, sistem otomatis meng-generate jumlah port optik default (misal 8 GPON).</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">2. Buat Kabel Backbone baru, biarkan OLT Asal kosong (Opsional).</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">Kabel Backbone berhasil dibuat beserta grid matrix tube/core kosong.</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">3. Buka Grid Core Backbone, klik Core 1, sambungkan ke OLT X Port 1.</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">Status core tersimpan dan pemetaan OLT terikat pada tingkat core secara independen (mendukung 1 kabel untuk banyak OLT).</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">4. Di tab <em>Rumah Kabel</em>, hubungkan Core 1 Backbone ke Core 1 Distribusi (Splicing).</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">Koneksi silang tercatat di tabel <code>rk_connections</code> secara dinamis.</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">5. Buat Drop Point (DP) baru, pilih RK → Kabel Distribusi → Core 1.</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">Kode DP otomatis terbentuk dengan format standard <code>DP-[Blok][BB][Tube][Core]</code> secara real-time.</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Skenario 4 -->
            <div class="space-y-3">
                <h3 class="text-sm font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Skenario UAT 4: Pendaftaran Pelanggan & Collision Port DP</h3>
                <div class="overflow-x-auto border border-gray-100 dark:border-slate-700 rounded-xl">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Langkah Pengujian</th>
                                <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Hasil yang Diharapkan</th>
                                <th class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">1. Daftarkan pelanggan baru, pilih Drop Point <code>DP-A010203</code> Port 1.</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">Pelanggan berhasil disimpan, port 1 di DP tersebut ditandai sebagai *Occupied*.</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">2. Coba daftarkan pelanggan kedua pada Drop Point yang sama di Port 1.</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">Sistem mendeteksi collision dan menolak pendaftaran dengan pesan error: "Port sudah terisi".</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Skenario 5 -->
            <div class="space-y-3">
                <h3 class="text-sm font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Skenario UAT 5: Simulator Redaman (Attenuation Simulator)</h3>
                <div class="overflow-x-auto border border-gray-100 dark:border-slate-700 rounded-xl">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Langkah Pengujian</th>
                                <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Hasil yang Diharapkan</th>
                                <th class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">1. Buka tab <em>FTTH Simulator</em> di portal jaringan.</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">Simulator termuat di dalam iframe tanpa masalah pemblokiran X-Frame-Options.</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">2. Ganti tema situs parent dari light ke dark mode.</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">Iframe simulator mendeteksi perubahan tema parent secara otomatis dan menyesuaikan warna canvas.</td>
                                <td class="px-6 py-4 text-end"><span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">Pass</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>
