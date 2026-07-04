<?php
require_once '../config.php';
require_login();

$page_title = 'Tentang Aplikasi - ISP Suite';
include '../includes/header.php';
require_once '../includes/sidebar.php';

// Fetch Database Version
$db_version = 'Unknown';
try {
    $db_version = $pdo->query("SELECT VERSION()")->fetchColumn();
} catch (Exception $e) {}

// Calculate Disk Space
$disk_total = disk_total_space(__DIR__);
$disk_free = disk_free_space(__DIR__);
$disk_used = $disk_total - $disk_free;
$disk_percentage = ($disk_total > 0) ? ($disk_used / $disk_total) * 100 : 0;

function format_bytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Fetch MikroTik status
$mt_status = 'Non-Aktif';
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'mikrotik_host'");
    $stmt->execute();
    $host = $stmt->fetchColumn();
    if (!empty($host)) {
        $mt_status = 'Aktif (' . htmlspecialchars($host) . ')';
    }
} catch (Exception $e) {}
?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen transition-all duration-300 ease-in-out">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
        
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
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Tentang Aplikasi</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Tentang ISP Suite</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Status informasi sistem, spesifikasi teknologi, dan modul terintegrasi.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left Info Panel -->
            <div class="md:col-span-2 space-y-6">
                <!-- App Details -->
                <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center gap-x-4 mb-4">
                        <div class="p-3 bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 rounded-2xl">
                            <svg class="size-8" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-800 dark:text-white">ISP Suite (NetManage Billing)</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Sistem manajemen RT/RW Net & Jaringan FTTH Handal</p>
                        </div>
                    </div>
                    
                    <p class="text-sm text-gray-600 dark:text-slate-300 leading-relaxed mb-4">
                        ISP Suite dirancang sebagai platform penagihan, operasional teknisi di lapangan, serta otomasi infrastruktur MikroTik secara real-time. Aplikasi ini memudahkan pengelolaan data pelanggan, tagihan bulanan otomatis, kas keluar, pemetaan fisik kabel fiber optik, redaman sirkuit, hingga pemutusan otomatis pelanggan yang telat bayar.
                    </p>

                    <div class="border-t border-gray-100 dark:border-slate-700 pt-4 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-400 block text-xs">Versi Aplikasi</span>
                            <strong class="text-gray-800 dark:text-white font-semibold">v1.5.2-stable</strong>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-xs">Lisensi Penggunaan</span>
                            <strong class="text-gray-800 dark:text-white font-semibold">Commercial License</strong>
                        </div>
                    </div>
                </div>

                <!-- Specs -->
                <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl p-6 shadow-sm">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="size-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="16" x="4" y="4" rx="2"/><rect width="6" height="6" x="9" y="9" rx="1"/></svg>
                        Spesifikasi & Lingkungan Server
                    </h3>
                    
                    <div class="divide-y divide-gray-100 dark:divide-slate-700 text-sm">
                        <div class="py-2.5 flex justify-between">
                            <span class="text-gray-500">Bahasa Pemrograman</span>
                            <span class="font-semibold text-gray-800 dark:text-white">PHP v<?= PHP_VERSION ?></span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-gray-500">Database Driver</span>
                            <span class="font-semibold text-gray-800 dark:text-white">PDO MySQL (InnoDB)</span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-gray-500">Versi MySQL Server</span>
                            <span class="font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($db_version) ?></span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-gray-500">Web Server</span>
                            <span class="font-semibold text-gray-800 dark:text-white truncate max-w-[200px]"><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') ?></span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-gray-500">Waktu & Zona Waktu</span>
                            <span class="font-semibold text-gray-800 dark:text-white"><?= date_default_timezone_get() ?> (<?= date('H:i') ?>)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Info Panel -->
            <div class="space-y-6">
                <!-- Modul Integrasi -->
                <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl p-6 shadow-sm">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-4">Status Modul Eksternal</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-900 rounded-xl">
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full bg-green-500"></span>
                                <span class="text-xs font-semibold text-gray-800 dark:text-white">Simulator FTTH</span>
                            </div>
                            <span class="text-[10px] bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 font-bold px-2 py-0.5 rounded">Active</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-900 rounded-xl">
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full bg-green-500"></span>
                                <span class="text-xs font-semibold text-gray-800 dark:text-white">Unified FTTH Portal</span>
                            </div>
                            <span class="text-[10px] bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 font-bold px-2 py-0.5 rounded">Active</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-900 rounded-xl">
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full <?= $mt_status !== 'Non-Aktif' ? 'bg-green-500' : 'bg-gray-400' ?>"></span>
                                <span class="text-xs font-semibold text-gray-800 dark:text-white">RouterOS API</span>
                            </div>
                            <span class="text-[10px] <?= $mt_status !== 'Non-Aktif' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' ?> font-bold px-2 py-0.5 rounded">
                                <?= $mt_status !== 'Non-Aktif' ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- System Disk Usage -->
                <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl p-6 shadow-sm">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-2">Kapasitas Penyimpanan</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Utilitas ruang penyimpanan server lokal.</p>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between text-xs font-semibold text-gray-600 dark:text-gray-300">
                            <span>Terpakai: <?= format_bytes($disk_used) ?></span>
                            <span>Total: <?= format_bytes($disk_total) ?></span>
                        </div>
                        <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden dark:bg-gray-700">
                            <div class="h-full bg-blue-600 rounded-full" style="width: <?= $disk_percentage ?>%"></div>
                        </div>
                        <p class="text-[10px] text-gray-400 text-right"><?= number_format($disk_percentage, 1) ?>% Terpakai (Tersedia: <?= format_bytes($disk_free) ?>)</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>
