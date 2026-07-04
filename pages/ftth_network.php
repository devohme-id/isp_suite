<?php
require_once '../config.php';
require_login();

// Validate tab parameter
$valid_tabs = ['olt', 'cable', 'rk', 'dp', 'simulator'];
$active_tab = $_GET['tab'] ?? 'olt';
if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'olt';
}

// Map tab names for page titles
$tab_titles = [
    'olt' => 'Manajemen OLT',
    'cable' => 'Kabel & Core',
    'rk' => 'Rumah Kabel (RK)',
    'dp' => 'Drop Point (DP)',
    'simulator' => 'FTTH Simulator'
];

$page_title = 'Jaringan FTTH - ' . $tab_titles[$active_tab];
include '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen transition-all duration-300 ease-in-out">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
        
        <!-- Breadcrumbs -->
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
                <li>
                    <div class="flex items-center">
                        <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                        </svg>
                        <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Jaringan FTTH</span>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                        </svg>
                        <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400"><?= $tab_titles[$active_tab] ?></span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Premium Tab Navigation Bar -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl shadow-sm p-2 flex flex-wrap gap-2 mb-6">
            <?php
            $tabs_config = [
                'olt' => ['label' => 'Manajemen OLT', 'icon' => '<rect width="20" height="8" x="2" y="2" rx="2" ry="2"/><rect width="20" height="8" x="2" y="14" rx="2" ry="2"/><line x1="6" x2="6.01" y1="6" y2="6"/><line x1="6" x2="6.01" y1="18" y2="18"/>'],
                'cable' => ['label' => 'Kabel & Core', 'icon' => '<path d="M12 2v20"/><path d="M5 12h14"/>'],
                'rk' => ['label' => 'Rumah Kabel (RK)', 'icon' => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M12 3v18"/><path d="M3 12h18"/>'],
                'dp' => ['label' => 'Drop Point (DP)', 'icon' => '<rect width="18" height="12" x="3" y="10" rx="2"/><path d="M12 10v12"/><path d="M7 10v12"/><path d="M17 10v12"/><path d="M7 2h10"/><path d="M12 2v8"/>'],
                'simulator' => ['label' => 'FTTH Simulator', 'icon' => '<line x1="6" x2="6.01" y1="3" y2="3"/><line x1="6" x2="6.01" y1="15" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/>']
            ];
            foreach ($tabs_config as $tab_key => $tab_info):
                $is_current = ($active_tab === $tab_key);
                $tab_classes = $is_current 
                    ? 'bg-blue-600 text-white shadow-md' 
                    : 'text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-blue-600 dark:hover:text-blue-400';
            ?>
                <a href="?tab=<?= $tab_key ?>" class="py-2.5 px-4 flex items-center gap-x-2.5 text-sm font-semibold rounded-xl transition-all <?= $tab_classes ?>">
                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $tab_info['icon'] ?></svg>
                    <span><?= $tab_info['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Render active tab content panel -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl p-6 shadow-sm min-h-[400px]">
            <?php include "ftth_tabs/{$active_tab}.php"; ?>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>
