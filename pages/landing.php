<?php
require_once '../config.php';

// Fetch Active Packages
try {
    $stmt = $pdo->query("SELECT * FROM internet_packages WHERE is_active = 1 ORDER BY (price = 0) ASC, price ASC");
    $packages = $stmt->fetchAll();
} catch (PDOException $e) {
    $packages = [];
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasang Baru - <?= APP_NAME ?></title>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <script>
        // Check for dark mode preference
        if (localStorage.getItem('hs_theme') === 'dark' || (!('hs_theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-slate-900 text-gray-700 dark:text-gray-300 font-sans antialiased transition-colors duration-200">

    <!-- Navigation -->
    <nav class="fixed top-0 z-50 w-full bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-gray-200 dark:border-slate-800 transition-colors duration-300">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center gap-2">
                    <a href="#home" class="flex items-center gap-2">
                        <?php if(defined('APP_ICON') && APP_ICON !== 'default_icon.png' && file_exists(UPLOAD_DIR . APP_ICON)): ?>
                            <img src="<?= BASE_URL ?>/uploads/<?= APP_ICON ?>" class="h-10 w-auto object-contain">
                        <?php else: ?>
                            <span class="text-xl font-bold text-blue-600 dark:text-blue-500 tracking-tight"><?= APP_NAME ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#home" class="desktop-link text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Home</a>
                    <a href="#features" class="desktop-link text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Keunggulan</a>
                    <a href="#packages" class="desktop-link text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Paket Internet</a>
                    <a href="#howto" class="desktop-link text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Cara Daftar</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="../pages/dashboard.php" class="py-2 px-4 text-sm font-semibold rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900 transition-colors flex items-center gap-2">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                             Hello, <?= htmlspecialchars(explode(' ', $_SESSION['name'])[0]) ?>
                        </a>
                    <?php else: ?>
                        <a href="../login.php" class="py-2 px-4 text-sm font-semibold rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900 transition-colors">
                            Login
                        </a>
                    <?php endif; ?>
                    <a href="#register" class="py-2 px-4 text-sm font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-md hover:shadow-lg transition-all">
                        Daftar Sekarang
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center gap-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="../pages/dashboard.php" class="text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-blue-600 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <?= htmlspecialchars(explode(' ', $_SESSION['name'])[0]) ?>
                        </a>
                    <?php else: ?>
                        <a href="../login.php" class="text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-blue-600">Login</a>
                    <?php endif; ?>
                    <button id="mobile-menu-btn" type="button" class="p-2 rounded-md text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800 focus:outline-none transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu (Hidden by default) -->
        <div id="mobile-menu" class="hidden md:hidden bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 absolute w-full left-0 top-16 shadow-lg">
            <div class="px-4 pt-2 pb-6 space-y-2">
                <a href="#home" class="mobile-link block px-3 py-3 rounded-md text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400">Home</a>
                <a href="#features" class="mobile-link block px-3 py-3 rounded-md text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400">Keunggulan</a>
                <a href="#packages" class="mobile-link block px-3 py-3 rounded-md text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400">Paket Internet</a>
                <a href="#howto" class="mobile-link block px-3 py-3 rounded-md text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400">Cara Daftar</a>
                <a href="#register" class="mobile-link block px-3 py-3 rounded-md text-base font-bold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-800">Daftar Sekarang</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="pt-32 pb-20 bg-gradient-to-b from-blue-50 to-white dark:from-slate-900 dark:to-slate-800">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-gray-900 dark:text-white mb-6">
                Internet Cepat, <span class="text-blue-600 dark:text-blue-400">Tanpa Batas</span>
            </h1>
            <p class="text-lg md:text-xl text-gray-600 dark:text-gray-400 mb-8 max-w-2xl mx-auto">
                Nikmati koneksi stabil dan kencang untuk streaming, gaming, dan bekerja dari rumah. Gabung sekarang dan rasakan bedanya.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="#register" class="py-3 px-6 rounded-xl bg-blue-600 text-white font-bold shadow-lg hover:bg-blue-700 transform hover:-translate-y-1 transition-all">
                    Pasang Sekarang
                </a>
                <a href="#packages" class="py-3 px-6 rounded-xl bg-white dark:bg-slate-700 text-gray-700 dark:text-gray-200 font-bold border border-gray-200 dark:border-slate-600 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-600 transition-all">
                    Lihat Paket
                </a>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section id="features" class="py-16 bg-white dark:bg-slate-900 border-b border-gray-100 dark:border-slate-800">
        <div class="container mx-auto px-4">
             <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div class="flex flex-col items-center text-center p-4">
                    <div class="size-12 bg-blue-100 dark:bg-blue-900 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400 mb-4">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" x2="12" y1="20" y2="20"/></svg>
                    </div>
                    <h3 class="font-bold text-lg text-gray-800 dark:text-white mb-2">Koneksi Stabil & Cepat</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Jaringan Fiber Optic terbaru menjamin koneksi minim lag untuk aktivitas tanpa henti.</p>
                </div>
                <!-- Feature 2 -->
                <div class="flex flex-col items-center text-center p-4">
                    <div class="size-12 bg-green-100 dark:bg-green-900 rounded-2xl flex items-center justify-center text-green-600 dark:text-green-400 mb-4">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                    </div>
                    <h3 class="font-bold text-lg text-gray-800 dark:text-white mb-2">Layanan Pelanggan 24/7</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Tim teknis kami selalu siap membantu kapan saja jika ada kendala jaringan Anda.</p>
                </div>
                <!-- Feature 3 -->
                <div class="flex flex-col items-center text-center p-4">
                    <div class="size-12 bg-purple-100 dark:bg-purple-900 rounded-2xl flex items-center justify-center text-purple-600 dark:text-purple-400 mb-4">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <h3 class="font-bold text-lg text-gray-800 dark:text-white mb-2">Harga Jujur & Transparan</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Satu harga flat setiap bulan. Tanpa biaya tersembunyi, tanpa kejutan tagihan akhir bulan.</p>
                </div>
                <!-- Feature 4 -->
                <div class="flex flex-col items-center text-center p-4">
                    <div class="size-12 bg-orange-100 dark:bg-orange-900 rounded-2xl flex items-center justify-center text-orange-600 dark:text-orange-400 mb-4">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>
                    </div>
                    <h3 class="font-bold text-lg text-gray-800 dark:text-white mb-2">Instalasi Cepat</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Proses pemasangan maksimal 1x24 jam setelah data terverifikasi oleh tim admin.</p>
                </div>
             </div>
        </div>
    </section>

    <!-- Packages Section -->
    <section id="packages" class="py-20 bg-gray-50 dark:bg-slate-800">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Pilihan Paket Terbaik</h2>
                <p class="text-gray-600 dark:text-gray-400">Pilih kecepatan yang sesuai dengan kebutuhan digital Anda</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-7xl mx-auto">
                <?php foreach($packages as $pkg): ?>
                <?php 
                    $isBestSeller = (strpos($pkg['package_name'], 'Fast Stream') !== false); 
                    $isCustom = ($pkg['price'] == 0);

                    // Dynamic Classes
                    if ($isBestSeller) {
                        $cardBg = 'bg-gradient-to-br from-[#0F172A] to-[#1E3A8A] border-[#1E40AF] shadow-2xl transform md:scale-105 ring-4 ring-blue-500/30 z-10 relative';
                        $titleColor = 'text-white';
                        $priceColor = 'text-white';
                        $periodColor = 'text-blue-200';
                        $textColor = 'text-blue-100';
                        $iconColor = 'text-sky-400';
                        $badge = '<div class="absolute top-0 right-0 bg-yellow-400 text-blue-900 text-xs font-extrabold px-3 py-1 rounded-bl-xl z-20 shadow-md">BEST SELLER</div>';
                        $btnClass = 'bg-white text-blue-900 hover:bg-blue-50 border-transparent shadow-lg';
                        $glow = '<div class="absolute -top-20 -right-20 w-40 h-40 bg-blue-500 rounded-full blur-[50px] opacity-20 pointer-events-none"></div>' .
                                '<div class="absolute -bottom-20 -left-20 w-40 h-40 bg-purple-500 rounded-full blur-[50px] opacity-20 pointer-events-none"></div>';
                    } elseif ($isCustom) {
                        // Enterprise / Custom Theme (Light: Indigo/Slate, Dark: Slate/Indigo)
                        $cardBg = 'bg-[#F8FAFC] dark:bg-slate-800 border-indigo-100 dark:border-indigo-900/50 shadow-xl hover:shadow-2xl hover:border-indigo-500 transition-all ring-1 ring-indigo-50/50 dark:ring-indigo-900/30';
                        $titleColor = 'text-slate-900 dark:text-white';
                        $priceColor = 'text-indigo-600 dark:text-indigo-400'; 
                        $periodColor = 'text-slate-500 dark:text-gray-400';
                        $textColor = 'text-slate-600 dark:text-gray-300';
                        $iconColor = 'text-indigo-500 dark:text-indigo-400';
                        $badge = '<div class="absolute top-0 right-0 bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-xs font-extrabold px-3 py-1 rounded-bl-xl z-20 border-b border-l border-indigo-100 dark:border-indigo-800">ENTERPRISE</div>';
                        $btnClass = 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700 hover:bg-indigo-600 dark:hover:bg-indigo-600 hover:text-white shadow-sm';
                        $glow = '<div class="absolute -top-20 -right-20 w-40 h-40 bg-indigo-400 rounded-full blur-[60px] opacity-10 pointer-events-none"></div>';
                    } else {
                        // Standard Theme
                        $cardBg = 'bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-700 shadow-sm hover:shadow-xl hover:border-blue-500 dark:hover:border-blue-500';
                        $titleColor = 'text-gray-900 dark:text-white';
                        $priceColor = 'text-gray-900 dark:text-gray-200';
                        $periodColor = 'text-gray-500 dark:text-gray-400';
                        $textColor = 'text-gray-600 dark:text-gray-400';
                        $iconColor = 'text-green-500 dark:text-green-400';
                        $badge = '';
                        $btnClass = 'bg-gray-50 dark:bg-slate-800 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white dark:hover:bg-blue-600 dark:hover:text-white border-blue-100 dark:border-slate-600';
                        $glow = '<div class="absolute top-0 left-0 w-full h-1 bg-blue-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>';
                    }
                ?>
                <div class="border rounded-2xl p-6 transition-all relative overflow-hidden group h-full flex flex-col <?= $cardBg ?>">
                    
                    <?= $badge ?>
                    <?= $glow ?>

                    <h3 class="text-xl font-bold mb-2 <?= $titleColor ?>"><?= htmlspecialchars($pkg['package_name']) ?></h3>
                    
                    <?php if($pkg['price'] > 0): ?>
                        <div class="flex items-baseline mb-6">
                            <span class="text-4xl font-extrabold <?= $priceColor ?>"><?= format_rupiah($pkg['price']) ?></span>
                            <span class="ml-1 <?= $periodColor ?>">/bulan</span>
                        </div>
                    <?php else: ?>
                        <div class="flex items-baseline mb-6">
                            <span class="text-3xl font-extrabold <?= $priceColor ?>">Hubungi Admin</span>
                            <span class="ml-1 <?= $periodColor ?> text-sm">/Sesuai Kebutuhan</span>
                        </div>
                    <?php endif; ?>

                    <ul class="space-y-4 mb-8 flex-grow">
                        <li class="flex items-center <?= $textColor ?>">
                            <svg class="size-5 <?= $iconColor ?> mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <?php 
                                $highlightClass = $isBestSeller ? 'text-white' : ($isCustom ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-900 dark:text-white');
                            ?>
                            <?php if($pkg['speed_mbps'] > 0): ?>
                                Up to&nbsp; <strong class="<?= $highlightClass ?>"><?= $pkg['speed_mbps'] ?> Mbps</strong>
                            <?php else: ?>
                                Kecepatan&nbsp; <strong class="<?= $highlightClass ?>">Sesuai Request</strong>
                            <?php endif; ?>
                        </li>
                        <li class="flex items-center <?= $textColor ?>">
                            <svg class="size-5 <?= $iconColor ?> mr-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                            <?php
                                $deviceRec = '';
                                if ($isCustom) {
                                    $deviceRec = "Solusi untuk Kantor / Bisnis Besar";
                                } elseif ($pkg['speed_mbps'] < 10) {
                                     $deviceRec = "Ideal untuk 1-2 Perangkat (HP/Laptop)";
                                } elseif ($pkg['speed_mbps'] <= 20) {
                                     $deviceRec = "Ideal untuk 3-5 Perangkat (Smart TV/HP)";
                                } elseif ($pkg['speed_mbps'] <= 50) {
                                     $deviceRec = "Ideal untuk 5-10 Perangkat (Multi Smart TV)";
                                } else {
                                     $deviceRec = "Ideal untuk 10+ Perangkat & Smart Home";
                                }
                            ?>
                            <?= $deviceRec ?>
                        </li>
                        <li class="flex items-center <?= $textColor ?>">
                            <svg class="size-5 <?= $iconColor ?> mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Unlimited Quota
                        </li>
                        <li class="flex items-center <?= $textColor ?>">
                            <svg class="size-5 <?= $iconColor ?> mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <?= $isCustom ? 'Dedicated Support' : '24/7 Support' ?>
                        </li>
                    </ul>
                    
                    <a href="#register" onclick="selectPackage(<?= $pkg['id'] ?>)" class="block w-full py-3 px-4 font-bold text-center rounded-xl transition-all border mt-auto <?= $btnClass ?>">
                        <?= $pkg['price'] > 0 ? 'Pilih Paket' : 'Konsultasi Sekarang' ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- How to Subscribe -->
    <section id="howto" class="py-20 bg-blue-600 dark:bg-blue-900 relative overflow-hidden">
        <!-- Decoration -->
        <div class="absolute top-0 left-0 w-64 h-64 bg-white/5 rounded-full blur-3xl pointer-events-none -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-3xl pointer-events-none translate-x-1/2 translate-y-1/2"></div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-white mb-4">Cara Mudah Berlangganan</h2>
                <p class="text-blue-100 text-lg">Hanya butuh 3 langkah untuk nikmati internet super cepat</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Step 1 -->
                <div class="relative flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full bg-white text-blue-600 font-black text-2xl flex items-center justify-center shadow-lg mb-6 z-10">1</div>
                    <h3 class="text-xl font-bold text-white mb-3">Isi Formulir</h3>
                    <p class="text-blue-100 leading-relaxed">Pilih paket yang Anda inginkan dan lengkapi data diri pada form pendaftaran di bawah ini.</p>
                    
                    <!-- Connector Line (Desktop) -->
                    <div class="hidden md:block absolute top-8 left-1/2 w-full h-1 bg-blue-500/50 -z-0"></div>
                </div>
                <!-- Step 2 -->
                <div class="relative flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full bg-white text-blue-600 font-black text-2xl flex items-center justify-center shadow-lg mb-6 z-10">2</div>
                    <h3 class="text-xl font-bold text-white mb-3">Verifikasi Admin</h3>
                    <p class="text-blue-100 leading-relaxed">Tim kami akan menghubungi Anda via WhatsApp untuk konfirmasi area dan jadwal pasang.</p>
                    
                    <!-- Connector Line (Desktop) -->
                    <div class="hidden md:block absolute top-8 left-1/2 w-full h-1 bg-blue-500/50 -z-0"></div>
                </div>
                <!-- Step 3 -->
                <div class="relative flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full bg-white text-blue-600 font-black text-2xl flex items-center justify-center shadow-lg mb-6 z-10">3</div>
                    <h3 class="text-xl font-bold text-white mb-3">Instalasi & Aktif</h3>
                    <p class="text-blue-100 leading-relaxed">Teknisi datang ke lokasi untuk instalasi. Internet langsung aktif dan siap digunakan!</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Registration Section -->
    <section id="register" class="py-20 bg-gray-50 dark:bg-slate-900">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-slate-700">
                <div class="bg-blue-600 p-8 text-white text-center">
                    <h2 class="text-2xl font-bold mb-2">Formulir Pendaftaran</h2>
                    <p class="text-blue-100">Isi data diri Anda untuk mulai berlangganan</p>
                </div>
                <div class="p-8 md:p-10">
                    <?php if(isset($_SESSION['public_flash'])): ?>
                        <div class="mb-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300">
                            <?= $_SESSION['public_flash'] ?>
                            <?php unset($_SESSION['public_flash']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="../actions/public_register.php" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nama Lengkap</label>
                                <input type="text" name="name" required class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white dark:bg-slate-700 dark:text-white" placeholder="Sesuai KTP">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nomor WhatsApp</label>
                                <input type="text" name="phone" required class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white dark:bg-slate-700 dark:text-white" placeholder="Contoh: 08123456789">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email (Opsional)</label>
                            <input type="email" name="email" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white dark:bg-slate-700 dark:text-white" placeholder="email@contoh.com">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alamat Pemasangan</label>
                            <textarea name="address" required rows="3" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white dark:bg-slate-700 dark:text-white" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pilih Paket</label>
                            <select name="package_id" id="package_select" required class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white dark:bg-slate-700 dark:text-white" onchange="checkCustomPackage()">
                                <option value="">-- Pilih Paket Internet --</option>
                                <?php foreach($packages as $pkg): ?>
                                    <?php if($pkg['price'] > 0): ?>
                                        <option value="<?= $pkg['id'] ?>"><?= $pkg['package_name'] ?> - <?= format_rupiah($pkg['price']) ?></option>
                                    <?php else: ?>
                                        <option value="<?= $pkg['id'] ?>">Custom Package (By Request) - Chat Admin</option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <p id="custom_package_hint" class="hidden mt-2 text-sm text-blue-600 dark:text-blue-400">
                                ℹ️ Untuk paket ini, silakan lanjut chat admin setelah daftar.
                            </p>
                        </div>

                        <div class="bg-gray-50 dark:bg-slate-700/50 p-4 rounded-lg border border-gray-200 dark:border-slate-600">
                            <h4 class="font-semibold text-gray-800 dark:text-white mb-2 text-sm">Syarat & Ketentuan Singkat</h4>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 list-disc list-inside space-y-1">
                                <li>Sistem pembayaran adalah <strong>Prabayar (Dibayar di Awal)</strong>.</li>
                                <li>Pemasangan akan dijadwalkan setelah konfirmasi admin.</li>
                                <li>Biaya instalasi (jika ada) dibayarkan saat teknisi datang.</li>
                                <li>Pastikan nomor WhatsApp aktif untuk komunikasi.</li>
                            </ul>
                            <div class="mt-3">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" required class="rounded border-gray-300 dark:border-slate-500 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Saya setuju dengan syarat dan ketentuan</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-4 bg-blue-600 text-white font-bold rounded-xl shadow-md hover:bg-blue-700 transition-all transform hover:-translate-y-0.5">
                            Kirim Pendaftaran
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-white dark:bg-slate-800 py-8 border-t border-gray-200 dark:border-slate-700 mt-12">
        <div class="container mx-auto px-4 text-center text-gray-500 dark:text-gray-400 text-sm">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function selectPackage(id) {
            const select = document.getElementById('package_select');
            select.value = id;
            checkCustomPackage();
        }

        function checkCustomPackage() {
            const select = document.getElementById('package_select');
            const hint = document.getElementById('custom_package_hint');
            const selectedText = select.options[select.selectedIndex].text;
            
            if (selectedText.includes('Custom Package')) {
                hint.classList.remove('hidden');
            } else {
                hint.classList.add('hidden');
            }
        }

        // Mobile Menu Logic
        const mobileBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileLinks = document.querySelectorAll('.mobile-link');

        if (mobileBtn && mobileMenu) {
            mobileBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });

            // Close menu when clicking a link
            mobileLinks.forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                });
            });
        }

        // Active Menu Scrollspy Using Intersection Observer
        const desktopLinks = document.querySelectorAll('.desktop-link');
        const sections = document.querySelectorAll('section');

        // Logic: Trigger when section occupies the top center of the screen
        const observerOptions = {
            root: null,
            rootMargin: '-20% 0px -79% 0px', // Active zone: Top 20% to Top 21% roughly (thin strip near top)
            threshold: 0
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const id = entry.target.getAttribute('id');
                if (entry.isIntersecting && id) {
                    desktopLinks.forEach(link => {
                        const href = link.getAttribute('href');
                        if (href === '#' + id) {
                            link.classList.add('text-blue-600', 'dark:text-blue-400', 'font-bold');
                            link.classList.remove('text-gray-700', 'dark:text-gray-200', 'font-medium');
                        } else {
                            link.classList.remove('text-blue-600', 'dark:text-blue-400', 'font-bold');
                            link.classList.add('text-gray-700', 'dark:text-gray-200', 'font-medium');
                        }
                    });
                }
            });
        }, observerOptions);

        sections.forEach(section => {
            observer.observe(section);
        });

        // Add 'active' class to Home by default if at top
        window.addEventListener('load', () => {
             if (window.scrollY < 100) {
                 const homeLink = document.querySelector('a[href="#home"]');
                 if(homeLink) {
                     homeLink.classList.add('text-blue-600', 'dark:text-blue-400', 'font-bold');
                     homeLink.classList.remove('text-gray-700', 'dark:text-gray-200', 'font-medium');
                 }
             }
        });
    </script>
</body>
</html>
