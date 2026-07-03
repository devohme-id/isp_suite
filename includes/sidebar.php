<?php
// Helper to check active state
function is_active($page) {
    return strpos($_SERVER['PHP_SELF'], $page) !== false ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-600 hover:bg-[#F1F5F9] hover:text-blue-600 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-blue-400';
}
?>
<!-- Sidebar -->
<aside id="application-sidebar" class="hs-overlay hs-overlay-open:translate-x-0 -translate-x-full transition-transform duration-300 ease-in-out transform hidden fixed top-0 left-0 bottom-0 z-[60] w-64 bg-white border-r border-gray-200 dark:bg-slate-800 dark:border-slate-700 flex flex-col lg:flex lg:translate-x-0 lg:right-auto lg:bottom-0">
    <!-- Brand -->
    <div class="px-6 pt-8 pb-4">
        <a class="flex-none text-xl font-semibold text-gray-800 dark:text-white flex items-center gap-x-3" href="<?= BASE_URL ?>/pages/dashboard.php" aria-label="Brand">
            <?php if(defined('APP_ICON') && APP_ICON !== 'default_icon.png' && file_exists(UPLOAD_DIR . APP_ICON)): ?>
                <img src="<?= BASE_URL ?>/uploads/<?= APP_ICON ?>" class="size-9 rounded-xl object-contain shadow-sm">
                <span class="sidebar-text font-bold text-lg tracking-tight"><?= APP_NAME ?></span>
            <?php else: ?>
                <span class="flex justify-center items-center size-9 rounded-xl bg-blue-600 text-white shadow-sm">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
                </span>
                <span class="sidebar-text font-bold text-lg tracking-tight"><?= APP_NAME ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Scrollable Navigation -->
    <nav class="hs-accordion-group p-3 w-full flex flex-col flex-wrap grow overflow-y-auto scrollbar-y" data-hs-accordion-always-open>
        <ul class="space-y-1.5 align-middle">
            <!-- Main -->
             <li class="pt-4 pb-2 px-2.5">
                <span class="block text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide">Main</span>
            </li>
            <li>
                <a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition-colors <?= is_active('dashboard.php') ?>" href="<?= BASE_URL ?>/pages/dashboard.php">
                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    <span class="sidebar-text truncate">Dashboard</span>
                </a>
            </li>

            <!-- Manajemen Pelanggan -->
            <li class="pt-4 pb-2 px-2.5">
                <span class="sidebar-group-label block text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide whitespace-normal break-words leading-tight">Pelanggan</span>
            </li>
            <li>
                <a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition-colors <?= is_active('customers.php') ?>" href="<?= BASE_URL ?>/pages/customers.php">
                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span class="sidebar-text truncate">Daftar Pelanggan</span>
                </a>
            </li>
            <li>
                <a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition-colors <?= is_active('packages.php') ?>" href="<?= BASE_URL ?>/pages/packages.php">
                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 9 3-3 3 3"/><path d="M13 18H7a2 2 0 0 1-2-2V6"/><path d="m22 15-3 3-3-3"/><path d="M11 6h6a2 2 0 0 1 2 2v10"/></svg>
                    <span class="sidebar-text truncate">Paket Internet</span>
                </a>
            </li>
            <li>
                <a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition-colors <?= is_active('odp_management.php') ?>" href="<?= BASE_URL ?>/pages/odp_management.php">
                     <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="12" x="3" y="10" rx="2"/><path d="M12 10v12"/><path d="M7 10v12"/><path d="M17 10v12"/><path d="M7 2h10"/><path d="M12 2v8"/></svg>
                    <span class="sidebar-text truncate">Manajemen ODP</span>
                </a>
            </li>

            <!-- Keuangan -->
            <li class="pt-4 pb-2 px-2.5">
                <span class="sidebar-group-label block text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide whitespace-normal break-words leading-tight">Keuangan</span>
            </li>
            <li>
                <a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition-colors <?= is_active('invoices.php') ?>" href="<?= BASE_URL ?>/pages/invoices.php">
                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                    <span class="sidebar-text truncate">Tagihan</span>
                </a>
            </li>
            <li>
                <a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition-colors <?= is_active('financial_report.php') ?>" href="<?= BASE_URL ?>/pages/financial_report.php">
                     <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span class="sidebar-text truncate">Laporan Keuangan</span>
                </a>
            </li>

            <!-- Pengeluaran -->
             <li class="pt-4 pb-2 px-2.5">
                <span class="sidebar-group-label block text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide whitespace-normal break-words leading-tight">Pengeluaran</span>
            </li>
            <li>
                <a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition-colors <?= is_active('expenses.php') ?>" href="<?= BASE_URL ?>/pages/expenses.php">
                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span class="sidebar-text truncate">Pengeluaran Operasional</span>
                </a>
            </li>
            <li>
                <a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition-colors <?= is_active('expense_report.php') ?>" href="<?= BASE_URL ?>/pages/expense_report.php">
                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <span class="sidebar-text truncate">Laporan Pengeluaran</span>
                </a>
            </li>

            <!-- Administrator -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Administrator'): ?>
            <li class="pt-4 pb-2 px-2.5">
                <span class="sidebar-group-label block text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide whitespace-normal break-words leading-tight">Administrator</span>
            </li>
             <li>
                <a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition-colors <?= is_active('users.php') ?>" href="<?= BASE_URL ?>/pages/users.php">
                     <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span class="sidebar-text truncate">Manajemen User</span>
                </a>
            </li>
            <li>
                <a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition-colors <?= is_active('settings.php') ?>" href="<?= BASE_URL ?>/pages/settings.php">
                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.47a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.39a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span class="sidebar-text truncate">Pengaturan</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    

</aside>
<!-- End Sidebar -->
