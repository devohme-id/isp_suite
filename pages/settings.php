<?php
require_once '../config.php';
require_login();
$page_title = 'Pengaturan Aplikasi';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Fetch Current Settings
// Fetch Current Settings
try {
    $stmt = $pdo->query("SELECT * FROM settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // format: ['app_name' => 'Value']
} catch (PDOException $e) {
    // Fallback or setup needed
    $settings = [];
    $error_msg = "Error loading settings (Table might be missing): " . $e->getMessage();
}

// Fetch User Profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Determine active tab from URL
$activeTab = $_GET['tab'] ?? 'profile';
$validTabs = ['profile', 'security', 'general', 'mikrotik'];
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'profile';
}
?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
        
        <!-- Header -->
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
                        <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Pengaturan</span>
                    </div>
                </li>
            </ol>
        </nav>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Pengaturan</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Kelola preferensi aplikasi dan akun Anda.</p>



        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-slate-700">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs" role="tablist">
                <button type="button" onclick="switchTab('profile')" id="tab-btn-profile" class="tab-btn py-4 px-1 inline-flex items-center gap-x-2 border-b-2 <?= $activeTab === 'profile' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold' : 'border-transparent text-gray-500 dark:text-gray-400' ?> text-sm whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none" role="tab">
                    Profil Saya
                </button>
                <button type="button" onclick="switchTab('security')" id="tab-btn-security" class="tab-btn py-4 px-1 inline-flex items-center gap-x-2 border-b-2 <?= $activeTab === 'security' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold' : 'border-transparent text-gray-500 dark:text-gray-400' ?> text-sm whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none" role="tab">
                    Keamanan
                </button>
                 <?php if ($_SESSION['role'] === 'Administrator'): ?>
                <button type="button" onclick="switchTab('general')" id="tab-btn-general" class="tab-btn py-4 px-1 inline-flex items-center gap-x-2 border-b-2 <?= $activeTab === 'general' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold' : 'border-transparent text-gray-500 dark:text-gray-400' ?> text-sm whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none" role="tab">
                    Pengaturan Umum
                </button>
                <button type="button" onclick="switchTab('mikrotik')" id="tab-btn-mikrotik" class="tab-btn py-4 px-1 inline-flex items-center gap-x-2 border-b-2 <?= $activeTab === 'mikrotik' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold' : 'border-transparent text-gray-500 dark:text-gray-400' ?> text-sm whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none" role="tab">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
                    MikroTik
                </button>
                <?php endif; ?>
            </nav>
        </div>

        <div class="mt-3">
             <!-- TAB 1: Profile -->
            <div id="tab-panel-profile" role="tabpanel" class="tab-panel <?= $activeTab !== 'profile' ? 'hidden' : '' ?>">
                <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm p-6 max-w-2xl">
                    <form action="../actions/settings_actions.php" method="POST">
                        <input type="hidden" name="action" value="profile">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <div class="grid gap-y-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Nama Lengkap</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required>
                            </div>
                            <div class="mt-2">
                                <span class="inline-flex items-center gap-x-1.5 py-1 px-3 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    Role: <?= htmlspecialchars($_SESSION['role']) ?>
                                </span>
                            </div>
                            <div class="pt-4">
                                <button type="submit" class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB 2: Security -->
            <div id="tab-panel-security" class="<?= $activeTab !== 'security' ? 'hidden' : '' ?> tab-panel" role="tabpanel">
                 <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm p-6 max-w-2xl">
                    <form action="../actions/settings_actions.php" method="POST">
                        <input type="hidden" name="action" value="security">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <div class="grid gap-y-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Password Saat Ini</label>
                                <input type="password" name="current_password" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required>
                            </div>
                            <div class="border-t border-gray-100 dark:border-slate-700 my-2"></div>
                             <div>
                                <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Password Baru</label>
                                <input type="password" name="new_password" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required minlength="6">
                            </div>
                              <div>
                                <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Konfirmasi Password Baru</label>
                                <input type="password" name="confirm_password" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required minlength="6">
                            </div>
                            <div class="pt-4">
                                <button type="submit" class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                                    Ganti Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB 3: General (Admin Only) -->
            <?php if ($_SESSION['role'] === 'Administrator'): ?>
            <div id="tab-panel-general" class="<?= $activeTab !== 'general' ? 'hidden' : '' ?> tab-panel" role="tabpanel">
                 <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm p-6 max-w-2xl">
                    <form action="../actions/settings_actions.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="general">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <div class="grid gap-y-4">
                             <div>
                                <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Nama Aplikasi</label>
                                <input type="text" name="app_name" value="<?= htmlspecialchars($settings['app_name'] ?? '') ?>" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Muncul di header dan tab browser.</p>
                            </div>
                              <div>
                                <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Nama Perusahaan/ISP</label>
                                <input type="text" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Icon / Logo</label>
                                <div class="flex items-center gap-4">
                                    <?php if(!empty($settings['app_icon'])): ?>
                                        <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($settings['app_icon']) ?>" class="size-12 rounded-lg object-contain border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-700">
                                    <?php endif; ?>
                                    <input type="file" name="app_icon" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:me-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Format: PNG, JPG, ICO. Max 500KB.</p>
                            </div>
                            <div class="pt-4">
                                <button type="submit" class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                                    Simpan Pengaturan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB 4: MikroTik (Admin Only) -->
            <div id="tab-panel-mikrotik" class="<?= $activeTab !== 'mikrotik' ? 'hidden' : '' ?> tab-panel" role="tabpanel">
                <div class="space-y-6">
                    <!-- MikroTik Settings Form -->
                    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm p-6 max-w-2xl">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Konfigurasi MikroTik</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Koneksi API-SSL untuk manajemen IP binding hotspot</p>
                            </div>
                        </div>

                        <form action="../actions/mikrotik_actions.php" method="POST" id="mikrotik-settings-form">
                            <input type="hidden" name="action" value="update_settings">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            
                            <div class="grid gap-y-4">
                                <!-- Enable Toggle -->
                                <div class="flex items-center justify-between py-3 px-4 bg-[#F1F5F9] dark:bg-slate-900 rounded-lg">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 dark:text-white">Aktifkan Integrasi</label>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Auto sync IP binding berdasarkan status pembayaran</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="mikrotik_enabled" value="1" class="sr-only peer" <?= ($settings['mikrotik_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                                        <div class="w-11 h-6 bg-gray-300 rounded-full peer dark:bg-slate-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>

                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Host IP</label>
                                        <input type="text" name="mikrotik_host" value="<?= htmlspecialchars($settings['mikrotik_host'] ?? '') ?>" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="contoh: 192.168.10.1">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Port API-SSL</label>
                                        <input type="number" name="mikrotik_port" value="<?= htmlspecialchars($settings['mikrotik_port'] ?? '') ?>" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="contoh: 8729">
                                    </div>
                                </div>

                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Username</label>
                                        <input type="text" name="mikrotik_user" value="<?= htmlspecialchars($settings['mikrotik_user'] ?? '') ?>" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="contoh: api-bot">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Password</label>
                                        <input type="password" name="mikrotik_password" value="" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="<?= !empty($settings['mikrotik_password']) ? '••••••••' : 'Masukkan password' ?>">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kosongkan jika tidak ingin mengubah password</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3 pt-4">
                                    <button type="submit" class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        Simpan Pengaturan
                                    </button>
                                    <button type="submit" formaction="../actions/mikrotik_actions.php" name="action" value="test_connection" class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Test Koneksi
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Sync Actions -->
                    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm p-6 max-w-2xl">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Sync IP Binding Status</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Sinkronkan status IP binding di MikroTik berdasarkan status pembayaran pelanggan.
                            Pelanggan dengan tagihan <span class="font-semibold text-red-600 dark:text-red-400">overdue</span> akan di-disable, 
                            pelanggan <span class="font-semibold text-green-600 dark:text-green-400">lunas</span> akan di-enable.
                        </p>
                        
                        <div class="flex flex-wrap gap-3">
                            <a href="../cron/cron_mikrotik_sync.php" class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-green-600 text-white hover:bg-green-700"
                               onclick="return confirm('Jalankan sync sekarang? Proses ini akan mengubah status IP binding di MikroTik.');">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                Sync Status Sekarang
                            </a>
                        </div>

                        <div class="mt-4 p-4 bg-gray-50 dark:bg-slate-900 rounded-lg">
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <strong>Untuk otomatis sync via cron:</strong><br>
                                <code class="bg-gray-200 dark:bg-slate-700 px-2 py-1 rounded text-xs mt-1 inline-block">
                                    */30 * * * * php <?= realpath(__DIR__ . '/../cron/cron_mikrotik_sync.php') ?>
                                </code>
                            </p>
                        </div>
                    </div>

                    <!-- Sync Data from MikroTik -->
                    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm p-6 max-w-2xl">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Sync Data IP Binding</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Import data IP & MAC dari MikroTik ke tabel customers</p>
                            </div>
                        </div>

                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Sinkronkan data <strong>MAC address</strong> dari MikroTik IP-binding ke tabel customers berdasarkan <strong>IP address</strong> sebagai kunci.
                            Data MikroTik adalah sumber utama (master).
                        </p>
                        
                        <div class="flex flex-wrap gap-3">
                            <form action="../actions/mikrotik_actions.php" method="POST" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="action" value="import_bindings_preview">
                                <button type="submit" class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    Preview Data
                                </button>
                            </form>
                            <form action="../actions/mikrotik_actions.php" method="POST" class="inline" onsubmit="return confirm('Update data customer yang sudah ada dengan data IP-binding dari MikroTik?');">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="action" value="sync_bindings_to_customers">
                                <button type="submit" class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                    Sync Data (Update)
                                </button>
                            </form>
                            <form action="../actions/mikrotik_actions.php" method="POST" class="inline" onsubmit="return confirm('MIGRASI TOTAL: Update semua customer yang match DAN buat customer baru dari binding yang tidak match. Lanjutkan?');">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="action" value="full_migration">
                                <button type="submit" class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" /></svg>
                                    Migrasi Total
                                </button>
                            </form>
                        </div>

                        <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <p class="text-xs text-yellow-800 dark:text-yellow-300">
                                <strong>Migrasi Total</strong>: Update customer existing + buat customer baru dari binding yang tidak match (nama dari comment, status pending).
                            </p>
                        </div>

                        <?php if (isset($_GET['preview']) && isset($_SESSION['mikrotik_preview'])): ?>
                        <?php $preview = $_SESSION['mikrotik_preview']; unset($_SESSION['mikrotik_preview']); ?>
                        <div class="mt-6 space-y-6">
                            <!-- Summary Stats -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="p-4 bg-gray-50 dark:bg-slate-900 rounded-lg text-center">
                                    <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= $preview['total'] ?></p>
                                    <p class="text-xs text-gray-500">Total Binding</p>
                                </div>
                                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
                                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= count($preview['will_update']) ?></p>
                                    <p class="text-xs text-blue-600 dark:text-blue-400">Akan Update</p>
                                </div>
                                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg text-center">
                                    <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?= count($preview['will_create']) ?></p>
                                    <p class="text-xs text-green-600 dark:text-green-400">Akan Dibuat</p>
                                </div>
                                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg text-center">
                                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"><?= count($preview['will_skip']) ?></p>
                                    <p class="text-xs text-yellow-600 dark:text-yellow-400">Akan Skip</p>
                                </div>
                            </div>

                            <!-- Will Update Table -->
                            <?php if (!empty($preview['will_update'])): ?>
                            <div class="border border-blue-200 dark:border-blue-800 rounded-lg overflow-hidden">
                                <div class="bg-blue-100 dark:bg-blue-900/30 px-4 py-2 border-b border-blue-200 dark:border-blue-800">
                                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300">🔄 Akan Update (<?= count($preview['will_update']) ?>)</h4>
                                </div>
                                <div class="overflow-x-auto max-h-64 overflow-y-auto">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-blue-50 dark:bg-blue-900/20 sticky top-0">
                                            <tr>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">No</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">MAC</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">IP (MikroTik)</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Comment</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Disabled</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Customer</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">MAC (DB)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                                            <?php $no = 1; ?>
                                            <?php foreach ($preview['will_update'] as $item): ?>
                                            <tr class="hover:bg-blue-50/50 dark:hover:bg-blue-900/10">
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400 text-[10px]"><?= $no++ ?></td>
                                                <td class="px-2 py-1.5 font-mono text-gray-600 dark:text-gray-400 text-[10px]"><?= htmlspecialchars($item['mac']) ?></td>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['address']) ?></td>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['comment']) ?></td>
                                                <td class="px-2 py-1.5">
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] <?= $item['disabled'] === 'Yes' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>"><?= $item['disabled'] ?></span>
                                                </td>
                                                <td class="px-2 py-1.5 text-gray-800 dark:text-gray-200 font-medium">
                                                    <?= htmlspecialchars($item['customer']['code'] ?? '') ?> - <?= htmlspecialchars($item['customer']['name'] ?? '') ?>
                                                </td>
                                                <td class="px-2 py-1.5 text-gray-500 dark:text-gray-400 font-mono text-[10px]"><?= htmlspecialchars($item['customer']['current_mac'] ?? '-') ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Will Create Table -->
                            <?php if (!empty($preview['will_create'])): ?>
                            <div class="border border-green-200 dark:border-green-800 rounded-lg overflow-hidden">
                                <div class="bg-green-100 dark:bg-green-900/30 px-4 py-2 border-b border-green-200 dark:border-green-800">
                                    <h4 class="text-sm font-semibold text-green-800 dark:text-green-300">➕ Akan Dibuat Baru (<?= count($preview['will_create']) ?>)</h4>
                                </div>
                                <div class="overflow-x-auto max-h-64 overflow-y-auto">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-green-50 dark:bg-green-900/20 sticky top-0">
                                            <tr>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">No</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">MAC</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">IP</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Comment</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Disabled</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">→ Nama</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">→ Alamat</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">→ Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                                            <?php $no = 1; ?>
                                            <?php foreach ($preview['will_create'] as $item): ?>
                                            <tr class="hover:bg-green-50/50 dark:hover:bg-green-900/10">
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400 text-[10px]"><?= $no++ ?></td>
                                                <td class="px-2 py-1.5 font-mono text-gray-600 dark:text-gray-400 text-[10px]"><?= htmlspecialchars($item['mac']) ?></td>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['address']) ?></td>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['comment']) ?></td>
                                                <td class="px-2 py-1.5">
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] <?= $item['disabled'] === 'Yes' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>"><?= $item['disabled'] ?></span>
                                                </td>
                                                <td class="px-2 py-1.5 text-green-700 dark:text-green-400 font-medium"><?= htmlspecialchars($item['parsed_name'] ?? '-') ?></td>
                                                <td class="px-2 py-1.5 text-green-600 dark:text-green-400"><?= htmlspecialchars($item['parsed_address'] ?? '-') ?></td>
                                                <td class="px-2 py-1.5">
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] <?= $item['new_status'] === 'suspended' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' ?>"><?= $item['new_status'] ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Will Skip Table -->
                            <?php if (!empty($preview['will_skip'])): ?>
                            <div class="border border-yellow-200 dark:border-yellow-800 rounded-lg overflow-hidden">
                                <div class="bg-yellow-100 dark:bg-yellow-900/30 px-4 py-2 border-b border-yellow-200 dark:border-yellow-800">
                                    <h4 class="text-sm font-semibold text-yellow-800 dark:text-yellow-300">⏭️ Akan Di-Skip (<?= count($preview['will_skip']) ?>)</h4>
                                </div>
                                <div class="overflow-x-auto max-h-64 overflow-y-auto">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-yellow-50 dark:bg-yellow-900/20 sticky top-0">
                                            <tr>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">No</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">MAC</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Address</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">To-Address</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Comment</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Alasan Skip</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                                            <?php $no = 1; ?>
                                            <?php foreach ($preview['will_skip'] as $item): ?>
                                            <tr class="hover:bg-yellow-50/50 dark:hover:bg-yellow-900/10">
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400 text-[10px]"><?= $no++ ?></td>
                                                <td class="px-2 py-1.5 font-mono text-gray-600 dark:text-gray-400 text-[10px]"><?= htmlspecialchars($item['mac']) ?></td>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['address']) ?></td>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['to_address']) ?></td>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['comment']) ?></td>
                                                <td class="px-2 py-1.5 text-yellow-700 dark:text-yellow-400 font-medium"><?= htmlspecialchars($item['skip_reason'] ?? '-') ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Will Terminate Table (Empty MAC with IP) -->
                            <?php if (!empty($preview['will_terminate'])): ?>
                            <div class="border border-orange-200 dark:border-orange-800 rounded-lg overflow-hidden">
                                <div class="bg-orange-100 dark:bg-orange-900/30 px-4 py-2 border-b border-orange-200 dark:border-orange-800">
                                    <h4 class="text-sm font-semibold text-orange-800 dark:text-orange-300">🚫 Akan Di-Terminate (<?= count($preview['will_terminate']) ?>)</h4>
                                    <p class="text-xs text-orange-600 dark:text-orange-400">Binding dengan IP tapi MAC kosong → customer akan di-terminate</p>
                                </div>
                                <div class="overflow-x-auto max-h-64 overflow-y-auto">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-orange-50 dark:bg-orange-900/20 sticky top-0">
                                            <tr>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">No</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">IP</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Comment</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Customer</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">MAC (DB)</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Status (DB)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                                            <?php $no = 1; ?>
                                            <?php foreach ($preview['will_terminate'] as $item): ?>
                                            <tr class="hover:bg-orange-50/50 dark:hover:bg-orange-900/10">
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400 text-[10px]"><?= $no++ ?></td>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['address']) ?></td>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['comment']) ?></td>
                                                <td class="px-2 py-1.5 text-gray-800 dark:text-gray-200 font-medium">
                                                    <?= htmlspecialchars($item['customer']['code'] ?? '') ?> - <?= htmlspecialchars($item['customer']['name'] ?? '') ?>
                                                </td>
                                                <td class="px-2 py-1.5 text-gray-500 dark:text-gray-400 font-mono text-[10px]"><?= htmlspecialchars($item['customer']['current_mac'] ?? '-') ?></td>
                                                <td class="px-2 py-1.5">
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] bg-gray-100 text-gray-700"><?= htmlspecialchars($item['customer']['current_status'] ?? '-') ?></span>
                                                    <span class="text-orange-600">→ terminated</span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Will Delete Table (Not in MikroTik) -->
                            <?php if (!empty($preview['will_delete'])): ?>
                            <div class="border border-red-200 dark:border-red-800 rounded-lg overflow-hidden">
                                <div class="bg-red-100 dark:bg-red-900/30 px-4 py-2 border-b border-red-200 dark:border-red-800">
                                    <h4 class="text-sm font-semibold text-red-800 dark:text-red-300">🗑️ Akan Di-Hapus dari Database (<?= count($preview['will_delete']) ?>)</h4>
                                    <p class="text-xs text-red-600 dark:text-red-400">Customer dengan IP tidak ditemukan di MikroTik (hanya berlaku pada Migrasi Total)</p>
                                </div>
                                <div class="overflow-x-auto max-h-64 overflow-y-auto">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-red-50 dark:bg-red-900/20 sticky top-0">
                                            <tr>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">No</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Customer Code</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Nama</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">IP (DB)</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">MAC (DB)</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Alasan</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                                            <?php $no = 1; ?>
                                            <?php foreach ($preview['will_delete'] as $item): ?>
                                            <tr class="hover:bg-red-50/50 dark:hover:bg-red-900/10">
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400 text-[10px]"><?= $no++ ?></td>
                                                <td class="px-2 py-1.5 text-red-700 dark:text-red-400 font-medium"><?= htmlspecialchars($item['customer']['code'] ?? '') ?></td>
                                                <td class="px-2 py-1.5 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($item['customer']['name'] ?? '') ?></td>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['customer']['current_ip'] ?? '-') ?></td>
                                                <td class="px-2 py-1.5 text-gray-500 dark:text-gray-400 font-mono text-[10px]"><?= htmlspecialchars($item['customer']['current_mac'] ?? '-') ?></td>
                                                <td class="px-2 py-1.5 text-red-600 dark:text-red-400"><?= htmlspecialchars($item['reason'] ?? '-') ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['migration']) && isset($_SESSION['migration_log'])): ?>
                        <?php $log = $_SESSION['migration_log']; unset($_SESSION['migration_log']); ?>
                        <div class="mt-6 space-y-6">
                            <!-- Migration Summary -->
                            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <h4 class="text-sm font-semibold text-green-800 dark:text-green-300 mb-2">📋 Log Migrasi Terakhir</h4>
                                <div class="grid grid-cols-3 md:grid-cols-6 gap-4 text-center">
                                    <div><span class="text-2xl font-bold text-gray-800 dark:text-white"><?= $log['total'] ?></span><br><span class="text-xs text-gray-500">Total</span></div>
                                    <div><span class="text-2xl font-bold text-blue-600"><?= $log['updated'] ?></span><br><span class="text-xs text-blue-600">Updated</span></div>
                                    <div><span class="text-2xl font-bold text-green-600"><?= $log['created'] ?></span><br><span class="text-xs text-green-600">Created</span></div>
                                    <div><span class="text-2xl font-bold text-red-600"><?= $log['deleted'] ?? 0 ?></span><br><span class="text-xs text-red-600">Deleted</span></div>
                                    <div><span class="text-2xl font-bold text-orange-600"><?= $log['terminated'] ?? 0 ?></span><br><span class="text-xs text-orange-600">Terminated</span></div>
                                    <div><span class="text-2xl font-bold text-yellow-600"><?= $log['skipped'] ?></span><br><span class="text-xs text-yellow-600">Skipped</span></div>
                                </div>
                            </div>

                            <!-- Updated Records (Important for audit) -->
                            <?php if (!empty($log['update_log'])): ?>
                            <div class="border border-blue-200 dark:border-blue-800 rounded-lg overflow-hidden">
                                <div class="bg-blue-100 dark:bg-blue-900/30 px-4 py-2 border-b border-blue-200 dark:border-blue-800">
                                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300">🔄 Record yang Di-Update (<?= count($log['update_log']) ?>) - AUDIT</h4>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-blue-50 dark:bg-blue-900/20">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">No</th>
                                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">MAC</th>
                                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">Comment</th>
                                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">Customer</th>
                                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">Perubahan</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                                            <?php $no = 1; ?>
                                            <?php foreach ($log['update_log'] as $item): ?>
                                            <tr class="bg-blue-50/50 dark:bg-blue-900/10">
                                                <td class="px-3 py-2 text-gray-600 dark:text-gray-400 text-[10px]"><?= $no++ ?></td>
                                                <td class="px-3 py-2 font-mono text-gray-600 dark:text-gray-400 text-[10px]"><?= htmlspecialchars($item['mac']) ?></td>
                                                <td class="px-3 py-2 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['comment']) ?></td>
                                                <td class="px-3 py-2 text-gray-800 dark:text-gray-200 font-medium"><?= htmlspecialchars($item['customer_code']) ?> - <?= htmlspecialchars($item['customer_name']) ?></td>
                                                <td class="px-3 py-2 text-blue-700 dark:text-blue-400"><?= htmlspecialchars($item['changes']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="p-4 bg-gray-50 dark:bg-slate-800 rounded-lg text-center">
                                <p class="text-sm text-gray-500">Tidak ada record yang di-update.</p>
                            </div>
                            <?php endif; ?>

                            <!-- Created Records (sample) -->
                            <?php if (!empty($log['create_log'])): ?>
                            <div class="border border-green-200 dark:border-green-800 rounded-lg overflow-hidden">
                                <div class="bg-green-100 dark:bg-green-900/30 px-4 py-2 border-b border-green-200 dark:border-green-800">
                                    <h4 class="text-sm font-semibold text-green-800 dark:text-green-300">➕ Sample Customer Baru (<?= min(count($log['create_log']), 50) ?> dari <?= $log['created'] ?>)</h4>
                                </div>
                                <div class="overflow-x-auto max-h-48 overflow-y-auto">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-green-50 dark:bg-green-900/20 sticky top-0">
                                            <tr>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">No</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Code</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Nama</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">Alamat</th>
                                                <th class="px-2 py-2 text-left text-gray-700 dark:text-gray-300">MAC</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                                            <?php $no = 1; ?>
                                            <?php foreach ($log['create_log'] as $item): ?>
                                            <tr>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400 text-[10px]"><?= $no++ ?></td>
                                                <td class="px-2 py-1.5 text-gray-800 dark:text-gray-200 font-medium"><?= htmlspecialchars($item['customer_code']) ?></td>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['name']) ?></td>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['address']) ?></td>
                                                <td class="px-2 py-1.5 font-mono text-gray-500 text-[10px]"><?= htmlspecialchars($item['mac']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MikroTik Error Modal -->
<div id="mikrotik-error-modal" class="hidden relative z-[60]" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-slate-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="bg-white dark:bg-slate-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white" id="modal-title">Koneksi Gagal</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400 break-words" id="mikrotik-error-message">
                                    Terjadi kesalahan saat menghubungkan ke MikroTik.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-slate-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="button" onclick="closeMikrotikModal()" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['mikrotik_error_details'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const errorMsg = <?= json_encode($_SESSION['mikrotik_error_details']) ?>;
        document.getElementById('mikrotik-error-message').textContent = errorMsg;
        document.getElementById('mikrotik-error-modal').classList.remove('hidden');
    });
</script>
<?php unset($_SESSION['mikrotik_error_details']); ?>
<?php endif; ?>

<script>
function switchTab(tabId) {
    // 1. Hide all panels
    document.querySelectorAll('.tab-panel').forEach(el => el.classList.add('hidden'));
    
    // 2. Reset all buttons
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('border-blue-600', 'text-blue-600', 'dark:text-blue-400', 'font-semibold');
        el.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
    });

    // 3. Show target panel
    document.getElementById('tab-panel-' + tabId).classList.remove('hidden');

    // 4. Highlight target button
    const btn = document.getElementById('tab-btn-' + tabId);
    btn.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
    btn.classList.add('border-blue-600', 'text-blue-600', 'dark:text-blue-400', 'font-semibold');

    // 5. Update URL without reload
    history.replaceState(null, '', '?tab=' + tabId);
}

function closeMikrotikModal() {
    const modal = document.getElementById('mikrotik-error-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>

