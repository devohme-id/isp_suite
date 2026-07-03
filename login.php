<?php
require_once 'config.php';
// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/pages/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <script>
        // Init Theme
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-[#f8fafc] dark:bg-slate-900 flex items-center justify-center min-h-screen transition-colors duration-300">

    <div class="w-full max-w-md p-6 bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl shadow-xl">
        <div class="text-center mb-8">
            <?php if(defined('APP_ICON') && APP_ICON !== 'default_icon.png' && file_exists(UPLOAD_DIR . APP_ICON)): ?>
                <img src="<?= BASE_URL ?>/uploads/<?= APP_ICON ?>" class="h-24 w-auto mx-auto mb-4 object-contain">
            <?php else: ?>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">NET<span class="text-brand-500">MANAGE</span></h1>
            <?php endif; ?>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Masuk untuk mengelola sistem billing.</p>
        </div>

        <?php 
        $flash = get_flash_message();
        if ($flash): 
            $isError = ($flash['type'] === 'error');
            $alertClass = $isError 
                ? 'bg-red-50 border-red-200 text-red-800' 
                : 'bg-green-50 border-green-200 text-green-800';
        ?>
            <div class="<?= $alertClass ?> border text-sm rounded-lg p-4 mb-6" role="alert">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <form action="auth.php" method="POST" class="space-y-5">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div>
                <label for="email" class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Email Address</label>
                <div class="relative">
                    <input type="email" id="email" name="email" class="py-3 px-4 block w-full border border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-brand-500 focus:ring-brand-500 bg-gray-50 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500" required placeholder="admin@example.com">
                    <div class="absolute inset-y-0 right-0 flex items-center pointer-events-none pr-4">
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    </div>
                </div>
            </div>

            <div>
                 <div class="flex justify-between items-center mb-2">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                </div>
                <div class="relative">
                    <input type="password" id="password" name="password" class="py-3 px-4 block w-full border border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-brand-500 focus:ring-brand-500 bg-gray-50 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500" required placeholder="••••••••">
                     <div class="absolute inset-y-0 right-0 flex items-center pointer-events-none pr-4">
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-brand-600 text-white hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-all">
                Sign in
            </button>
        </form>

        <div class="mt-6 text-center">
            <div class="relative flex py-2 items-center">
                <div class="flex-grow border-t border-gray-100 dark:border-slate-700"></div>
                <span class="flex-shrink-0 mx-4 text-xs text-gray-400 dark:text-gray-500">Atau</span>
                <div class="flex-grow border-t border-gray-100 dark:border-slate-700"></div>
            </div>
            <a href="<?= BASE_URL ?>/pages/landing.php" class="mt-2 py-2.5 px-4 w-full inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-brand-600 transition-all">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
                Lihat Landing Page
            </a>
        </div>
    </div>

    <div class="absolute bottom-4 text-center text-xs text-gray-400">
        &copy; <?= date('Y') ?> ISP Billing System. All rights reserved.
    </div>

</body>
</html>
