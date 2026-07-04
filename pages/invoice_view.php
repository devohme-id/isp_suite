<?php
require_once '../config.php';

// Public Access Logic
$oid = isset($_GET['oid']) ? $_GET['oid'] : '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (!$oid || !$token) {
    die("Link tidak valid.");
}

// Decode ID
$id_str = base64_decode($oid);
$id = (int)$id_str;

// Verify Token
$expected_token = hash_hmac('sha256', $id_str, INVOICE_SECRET);
if (!hash_equals($expected_token, $token)) {
    die("Akses ditolak: Token tidak valid.");
}

// Fetch Invoice Details (Same as invoice_print.php but for public)
$stmt = $pdo->prepare("
    SELECT i.*, c.name, c.customer_code, c.address, c.phone, p.package_name, pm.payment_method, pm.payment_date 
    FROM invoices i 
    JOIN customers c ON i.customer_id = c.id 
    LEFT JOIN internet_packages p ON c.package_id = p.id
    LEFT JOIN payments pm ON i.id = pm.invoice_id AND pm.status = 'verified'
    WHERE i.id = ?
");
$stmt->execute([$id]);
$inv = $stmt->fetch();

if (!$inv) {
    die("Invoice tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?= $inv['invoice_number'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
    <style>
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .print-shadow { box-shadow: none !important; border: 1px solid #ddd; }
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-slate-900 min-h-screen py-8 flex justify-center items-start transition-colors duration-200">

    <div class="w-full max-w-[380px] bg-white dark:bg-slate-800 text-gray-800 dark:text-white rounded-xl shadow-lg print-shadow relative overflow-hidden transition-colors duration-200">
        
        <!-- Status Banner -->
        <?php if ($inv['status'] == 'paid'): ?>
            <div class="bg-green-600 text-white text-center py-2 text-sm font-bold uppercase tracking-wider">
                Lunas / Paid
            </div>
        <?php elseif ($inv['status'] == 'pending'): ?>
            <div class="bg-orange-500 text-white text-center py-2 text-sm font-bold uppercase tracking-wider">
                Menunggu Verifikasi
            </div>
        <?php else: ?>
            <div class="bg-red-600 text-white text-center py-2 text-sm font-bold uppercase tracking-wider">
                Belum Dibayar
            </div>
        <?php endif; ?>

        <div class="p-6">
            <!-- Header -->
            <div class="text-center mb-6">
                <!-- Branding Mockup -->
                <div class="inline-flex items-center justify-center mb-3">
                    <?php if(defined('APP_ICON') && APP_ICON !== 'default_icon.png' && file_exists(UPLOAD_DIR . APP_ICON)): ?>
                        <img src="<?= BASE_URL ?>/uploads/<?= APP_ICON ?>" class="h-24 w-auto object-contain">
                    <?php else: ?>
                         <div class="inline-flex items-center justify-center size-12 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                            <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        </div>
                    <?php endif; ?>
                </div>
                <h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white"><?= APP_NAME ?></h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Invoice Tagihan Internet</p>
            </div>

            <!-- Main Details -->
            <div class="space-y-4 border-b border-dashed border-gray-300 dark:border-slate-600 pb-6 mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pelanggan</p>
                        <p class="font-bold text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($inv['name']) ?></p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400"><?= htmlspecialchars($inv['customer_code']) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Invoice No</p>
                        <p class="font-bold text-sm text-gray-900 dark:text-white font-mono">#<?= $inv['invoice_number'] ?></p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400"><?= date('d/m/Y', strtotime($inv['generated_at'])) ?></p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-3">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200"><?= $inv['package_name'] ?></span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white"><?= format_rupiah($inv['amount']) ?></span>
                    </div>
                    <div class="text-[10px] text-gray-500 dark:text-gray-400">
                        Periode: <?= date('F Y', mktime(0, 0, 0, $inv['period_month'], 10, $inv['period_year'])) ?>
                    </div>
                </div>
            </div>

            <!-- Totals -->
            <div class="flex justify-between items-center mb-6">
                <span class="text-gray-600 dark:text-gray-400 font-medium">Total Tagihan</span>
                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= format_rupiah($inv['amount']) ?></span>
            </div>

            <!-- Details Table (For Payment) -->
            <?php if ($inv['status'] == 'paid'): ?>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 border border-green-100 dark:border-green-800 mb-6">
                <div class="flex gap-2 items-start">
                    <svg class="size-4 text-green-600 dark:text-green-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <div>
                        <p class="text-xs font-bold text-green-800 dark:text-green-300">Pembayaran Diterima</p>
                        <p class="text-[10px] text-green-600 dark:text-green-400">
                            Tgl: <?= date('d M Y H:i', strtotime($inv['payment_date'])) ?><br>
                            Metode: <?= ucfirst($inv['payment_method']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php elseif ($inv['status'] == 'unpaid'): ?>
             <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3 border border-yellow-100 dark:border-yellow-800 mb-6">
                <p class="text-xs font-bold text-yellow-800 dark:text-yellow-400 mb-1">Instruksi Pembayaran</p>
                <p class="text-[10px] text-yellow-700 dark:text-yellow-500 leading-relaxed">
                    Silakan transfer ke:<br>
                    <strong>BCA 1234567890</strong> a.n ISP Suite<br>
                    Dan upload bukti di halaman pelanggan.
                </p>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="text-center">
                <p class="text-[10px] text-gray-400">Invoice Digital Resmi.</p>
                <div class="flex justify-center gap-2 mt-4 no-print">
                    <button onclick="window.print()" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-800 transition shadow-lg">
                        Download PDF
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Decoration Circles -->
        <div class="absolute -left-3 top-40 w-6 h-6 bg-gray-100 dark:bg-slate-900 rounded-full"></div>
        <div class="absolute -right-3 top-40 w-6 h-6 bg-gray-100 dark:bg-slate-900 rounded-full"></div>
    </div>

</body>
</html>
