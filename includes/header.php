<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?><?= APP_NAME ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Preline UI (via CDN for styles if needed, though Tailwind handles most) -->
    <!-- Note: Preline JS is in footer -->

    <!-- Custom Tailwind Configuration -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    <script>
        // Theme Initialization
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme === 'dark' || (!savedTheme && systemTheme)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();

        // Check local storage and apply sidebar state immediately to avoid FOUC
        (function() {
            const isClosed = localStorage.getItem('sidebar-closed') === 'true';
            if (isClosed) {
                document.documentElement.classList.add('sidebar-closed');
            }
        })();
    </script>
    <style>
        /* Sidebar Transitions */
        #application-sidebar {
            transition: width 0.3s ease-in-out, transform 0.3s ease-in-out;
            overflow: hidden; /* Ensure text doesn't overflow during transition */
            white-space: nowrap;
        }

        /* --- Mini Sidebar State (Desktop Only) --- */
        @media (min-width: 1024px) {
            /* 1. Base Closed State (Mini) */
            html.sidebar-closed #application-sidebar {
                width: 5rem; /* w-20 */
                transform: none !important; /* Processed as mini, not hidden off-screen */
            }
            
            /* 2. Hover Expand State */
            html.sidebar-closed #application-sidebar:hover {
                width: 16rem; /* w-64 */
                box-shadow: 4px 0 24px rgba(0,0,0,0.1); /* Add shadow when floating over content */
                 z-index: 999; /* Ensure it stays on top */
            }

            /* 3. Hide Text when Mini (and not hovered) */
            html.sidebar-closed #application-sidebar:not(:hover) .sidebar-text,
            html.sidebar-closed #application-sidebar:not(:hover) .sidebar-group-label {
                display: none;
                opacity: 0;
            }
            
            /* 4. Adjust Main Content Padding */
            html.sidebar-closed .lg\:pl-64 {
                padding-left: 5rem !important; /* Match mini width */
            }
            
            /* 5. Hide User Profile Text specific if complex structure */
             html.sidebar-closed #application-sidebar:not(:hover) .line-clamp-1 {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-[#F1F5F9] dark:bg-slate-900 text-gray-700 dark:text-slate-300 antialiased transition-all duration-300">

<!-- Header -->
<header class="sticky top-0 inset-x-0 flex flex-wrap sm:justify-start sm:flex-nowrap z-[48] w-full bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 text-sm py-2.5 lg:pl-64 transition-all duration-300 ease-in-out">
    <nav class="flex basis-full items-center w-full mx-auto px-4 sm:px-6" aria-label="Global">
        <div class="me-5 flex items-center gap-x-2">
             <!-- Sidebar Toggle -->
            <button type="button" id="sidebar-toggle" class="py-2 px-3 flex justify-center items-center gap-x-2 size-10 text-[#1e293b] dark:text-slate-200 rounded-lg hover:bg-[#F1F5F9] dark:hover:bg-slate-700 focus:outline-none focus:bg-[#F1F5F9] dark:focus:bg-slate-700 disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="dialog" aria-expanded="false" aria-controls="application-sidebar" aria-label="Toggle navigation" data-manual-toggle="#application-sidebar">
                 <svg class="flex-shrink-0 size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" x2="21" y1="6" y2="6"/><line x1="3" x2="21" y1="12" y2="12"/><line x1="3" x2="21" y1="18" y2="18"/></svg>
            </button>
            <a class="flex-none text-xl font-semibold text-gray-800 dark:text-white lg:hidden" href="<?= BASE_URL ?>/pages/dashboard.php" aria-label="Brand"><?= APP_NAME ?></a>
        </div>

        <!-- Search Input -->
        <div class="hidden sm:flex items-center me-auto ms-4">
            <label for="icon" class="sr-only">Search</label>
            <div class="relative min-w-72 md:min-w-80">
                <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-4">
                    <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <input type="text" id="icon" name="icon" class="py-2 px-4 ps-11 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="Search data...">
            </div>
        </div>

        <div class="flex items-center justify-end ms-auto sm:justify-between sm:gap-x-3 sm:order-3">
            <!-- Search Input -->


            <!-- Right Content -->
            <div class="flex flex-row items-center justify-end gap-2">
                <!-- UAT Document Link -->
                <a href="<?= BASE_URL ?>/pages/uat_document.php" class="py-2 px-3 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none transition-colors hidden sm:inline-flex dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:hover:bg-slate-700">
                    <svg class="flex-shrink-0 size-4 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    Dokumen UAT
                </a>
                <a href="<?= BASE_URL ?>/pages/uat_document.php" class="sm:hidden py-2 px-3 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:hover:bg-slate-700">
                    <svg class="flex-shrink-0 size-4 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                </a>

                <!-- Landing Page Link -->
                <a href="<?= BASE_URL ?>/pages/landing.php" target="_blank" class="py-2 px-3 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none transition-colors hidden sm:inline-flex dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:hover:bg-slate-700">
                    <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
                    Lihat Web
                </a>
                <a href="<?= BASE_URL ?>/pages/landing.php" target="_blank" class="sm:hidden py-2 px-3 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:hover:bg-slate-700">
                    <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
                </a>

                <!-- Theme Switcher -->
                <div class="manual-dropdown relative inline-flex">
                    <button id="hs-dropdown-theme" type="button" class="manual-dropdown-toggle relative inline-flex justify-center items-center size-[38px] text-sm font-semibold rounded-lg text-gray-800 dark:text-white hover:bg-[#F1F5F9] dark:hover:bg-slate-700 focus:outline-none focus:bg-[#F1F5F9] dark:focus:bg-slate-700 disabled:opacity-50 disabled:pointer-events-none">
                        <!-- Sun Icon (Light) -->
                        <svg class="size-6 hidden dark:hidden" id="theme-icon-light" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                        <!-- Moon Icon (Dark) -->
                        <svg class="size-6 hidden dark:block" id="theme-icon-dark" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                        <!-- System Icon (Default/Initial can be tricky, using JS to toggle display) -->
                        <svg class="size-6 hidden" id="theme-icon-system" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                    </button>

                    <div class="manual-dropdown-menu absolute right-0 top-full z-50 transition-[opacity,margin] duration opacity-0 hidden min-w-32 bg-white dark:bg-slate-800 shadow-md rounded-lg p-1 mt-2 border border-gray-200 dark:border-slate-700" aria-labelledby="hs-dropdown-theme">
                         <button type="button" id="btn-theme-light" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-gray-200 hover:bg-[#F1F5F9] dark:hover:bg-slate-700" onclick="setTheme('light')">
                            <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                            Light
                        </button>
                        <button type="button" id="btn-theme-dark" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-gray-200 hover:bg-[#F1F5F9] dark:hover:bg-slate-700" onclick="setTheme('dark')">
                            <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                            Dark
                        </button>
                        <button type="button" id="btn-theme-system" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-gray-200 hover:bg-[#F1F5F9] dark:hover:bg-slate-700" onclick="setTheme('system')">
                             <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                            System
                        </button>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="manual-dropdown relative inline-flex">
                    <button id="hs-dropdown-notifications" type="button" class="manual-dropdown-toggle relative inline-flex justify-center items-center size-[38px] text-sm font-semibold rounded-lg text-gray-800 dark:text-white hover:bg-[#F1F5F9] dark:hover:bg-slate-700 focus:outline-none focus:bg-[#F1F5F9] dark:focus:bg-slate-700 disabled:opacity-50 disabled:pointer-events-none">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        <span class="absolute top-1.5 end-1.5 inline-flex items-center justify-center size-4 rounded-full bg-red-500 text-[10px] font-bold text-white leading-none transform translate-x-1/4 -translate-y-1/4">2</span>
                    </button>
                     <div class="manual-dropdown-menu absolute right-0 top-full z-50 transition-[opacity,margin] duration opacity-0 hidden min-w-60 bg-white dark:bg-slate-800 shadow-md rounded-lg p-1 space-y-0.5 mt-2 border border-gray-200 dark:border-slate-700" aria-labelledby="hs-dropdown-notifications">
                        <div class="flex items-center justify-between px-3 py-2 border-b border-gray-100 dark:border-slate-700">
                             <span class="text-xs font-semibold text-gray-800 dark:text-white uppercase">Notifications</span>
                             <a href="#" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Mark all read</a>
                        </div>
                        <a class="flex items-start gap-x-2.5 py-2 px-3 rounded-md hover:bg-[#F1F5F9] dark:hover:bg-slate-700 transition-colors" href="#">
                             <span class="flex-shrink-0 inline-flex items-center justify-center size-8 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M2 12h20"/></svg>
                             </span>
                             <div class="grow">
                                <p class="text-xs font-medium text-gray-800 dark:text-gray-200">New customer registered</p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">2 min ago</p>
                             </div>
                        </a>
                         <a class="flex items-start gap-x-2.5 py-2 px-3 rounded-md hover:bg-[#F1F5F9] dark:hover:bg-slate-700 transition-colors" href="#">
                             <span class="flex-shrink-0 inline-flex items-center justify-center size-8 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                             </span>
                             <div class="grow">
                                <p class="text-xs font-medium text-gray-800 dark:text-gray-200">Payment verified #INV-001</p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">1 hour ago</p>
                             </div>
                        </a>
                    </div>
                </div>

                <!-- User Dropdown -->
                <div class="manual-dropdown relative inline-flex">
                    <button id="hs-dropdown-custom" type="button" class="manual-dropdown-toggle relative inline-flex items-center gap-x-2 py-1 px-2 rounded-lg hover:bg-[#F8FAFC] dark:hover:bg-slate-700 disabled:opacity-50 disabled:pointer-events-none text-start">
                         <div class="shrink-0">
                            <!-- Display App Icon if available, else Initials -->
                            <?php if(defined('APP_ICON') && APP_ICON !== 'default_icon.png' && file_exists(UPLOAD_DIR . APP_ICON)): ?>
                                <img src="<?= BASE_URL ?>/uploads/<?= APP_ICON ?>" class="size-[36px] rounded-full object-cover border border-gray-200 dark:border-slate-700">
                            <?php else: ?>
                                <span class="inline-flex items-center justify-center size-[36px] rounded-full bg-blue-600 text-white font-semibold text-xs leading-none">
                                    <?= substr($_SESSION['name'] ?? 'U', 0, 2) ?>
                                </span>
                            <?php endif; ?>
                         </div>
                         <div class="hidden lg:block">
                             <span class="block text-sm font-bold text-gray-800 dark:text-white leading-none"><?= htmlspecialchars($_SESSION['name'] ?? 'Guest') ?></span>
                             <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?= htmlspecialchars($_SESSION['email'] ?? 'guest@example.com') ?></span>
                         </div>
                    </button>

                    <div class="manual-dropdown-menu absolute right-0 top-full z-50 transition-[opacity,margin] duration opacity-0 hidden min-w-60 bg-white dark:bg-slate-800 shadow-md rounded-lg p-1 mt-2 border border-gray-200 dark:border-slate-700" aria-labelledby="hs-dropdown-custom">
                         <div class="py-3 px-4 bg-[#F8FAFC] dark:bg-slate-900 rounded-t-lg border-b border-gray-200 dark:border-slate-700 mb-1">
                             <p class="text-sm font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></p>
                             <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($_SESSION['email'] ?? 'user@example.com') ?></p>
                        </div>
                        <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-gray-200 hover:bg-[#F1F5F9] dark:hover:bg-slate-700 focus:outline-none focus:bg-[#F1F5F9] dark:focus:bg-slate-700" href="<?= BASE_URL ?>/pages/settings.php">
                            <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Your Profile
                        </a>
                        <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-gray-200 hover:bg-[#F1F5F9] dark:hover:bg-slate-700 focus:outline-none focus:bg-[#F1F5F9] dark:focus:bg-slate-700" href="<?= BASE_URL ?>/pages/settings.php">
                            <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.47a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.39a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                            Settings
                        </a>
                        <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-gray-200 hover:bg-[#F1F5F9] dark:hover:bg-slate-700 focus:outline-none focus:bg-[#F1F5F9] dark:focus:bg-slate-700" href="<?= BASE_URL ?>/pages/invoices.php">
                            <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                            Billing
                        </a>
                        <div class="border-t border-gray-200 dark:border-slate-700 my-2"></div>
                        <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/10 focus:outline-none focus:bg-red-50 dark:focus:bg-red-900/10" href="<?= BASE_URL ?>/auth.php?action=logout">
                            <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                            Sign out
                        </a>
                    </div>
                </div>
                

            </div>
        </div>
    </nav>
</header>
<!-- End Header -->
