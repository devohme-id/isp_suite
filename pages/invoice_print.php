<?php
require_once '../config.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    die("Invoice ID tidak valid.");
}

// Fetch Invoice Details
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

// Prepare WhatsApp Link
$phone = preg_replace('/[^0-9]/', '', $inv['phone']);
if (substr($phone, 0, 1) == '0') {
    $phone = '62' . substr($phone, 1);
}

$message = "*INVOICE TAGIHAN INTERNET*\n";
$message .= APP_NAME . "\n\n";
$message .= "Halo *" . $inv['name'] . "*,\n";
$message .= "Berikut adalah rincian tagihan Anda:\n\n";
$message .= "No. Invoice: *" . $inv['invoice_number'] . "*\n";
$message .= "Periode: " . date('F Y', mktime(0, 0, 0, $inv['period_month'], 10, $inv['period_year'])) . "\n";
$message .= "Total: *" . format_rupiah($inv['amount']) . "*\n";
$message .= "Status: *" . ($inv['status'] == 'paid' ? 'LUNAS' : strtoupper($inv['status'])) . "*\n\n";

if ($inv['status'] == 'unpaid') {
    $message .= "Mohon segera melakukan pembayaran agar layanan tetap aktif. Terima kasih.\n\n";
} else {
    $message .= "Terima kasih telah melakukan pembayaran.\n\n";
}

// Generate Public Invoice Link
$id_str = (string)$inv['id'];
$oid = base64_encode($id_str);
$token = hash_hmac('sha256', $id_str, INVOICE_SECRET);
$public_link = BASE_URL . "/pages/invoice_view.php?oid=" . urlencode($oid) . "&token=" . urlencode($token);

$message .= "Lihat Invoice Digital:\n" . $public_link;
$message .= "\n\n_Pesan ini dikirim otomatis oleh sistem " . APP_NAME . ". Mohon simpan bukti pembayaran ini._";

$wa_url = "https://wa.me/" . $phone . "?text=" . urlencode($message);
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
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
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
<body class="bg-gray-100 min-h-screen py-8 flex justify-center items-start">

    <div class="w-full max-w-[380px] bg-white text-gray-800 rounded-xl shadow-lg print-shadow relative overflow-hidden">
        
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
                        <div class="inline-flex items-center justify-center size-12 rounded-full bg-blue-100 text-blue-600">
                            <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        </div>
                    <?php endif; ?>
                </div>
                <h1 class="text-xl font-bold tracking-tight text-gray-900"><?= APP_NAME ?></h1>
                <p class="text-xs text-gray-500 mt-1">Invoice Tagihan Internet</p>
            </div>

            <!-- Main Details -->
            <div class="space-y-4 border-b border-dashed border-gray-300 pb-6 mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs text-gray-500">Pelanggan</p>
                        <p class="font-bold text-sm text-gray-900"><?= htmlspecialchars($inv['name']) ?></p>
                        <p class="text-[10px] text-gray-500"><?= htmlspecialchars($inv['customer_code']) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Invoice No</p>
                        <p class="font-bold text-sm text-gray-900 font-mono">#<?= $inv['invoice_number'] ?></p>
                        <p class="text-[10px] text-gray-500"><?= date('d/m/Y', strtotime($inv['generated_at'])) ?></p>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs font-semibold text-gray-700"><?= $inv['package_name'] ?></span>
                        <span class="text-xs font-bold text-gray-900"><?= format_rupiah($inv['amount']) ?></span>
                    </div>
                    <div class="text-[10px] text-gray-500">
                        Periode: <?= date('F Y', mktime(0, 0, 0, $inv['period_month'], 10, $inv['period_year'])) ?>
                    </div>
                </div>
            </div>

            <!-- Totals -->
            <div class="flex justify-between items-center mb-6">
                <span class="text-gray-600 font-medium">Total Tagihan</span>
                <span class="text-2xl font-bold text-blue-600"><?= format_rupiah($inv['amount']) ?></span>
            </div>

            <!-- Details Table (For Payment) -->
            <?php if ($inv['status'] == 'paid'): ?>
            <div class="bg-green-50 rounded-lg p-3 border border-green-100 mb-6">
                <div class="flex gap-2 items-start">
                    <svg class="size-4 text-green-600 mt-0.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <div>
                        <p class="text-xs font-bold text-green-800">Pembayaran Diterima</p>
                        <p class="text-[10px] text-green-600">
                            Tgl: <?= date('d M Y H:i', strtotime($inv['payment_date'])) ?><br>
                            Metode: <?= ucfirst($inv['payment_method']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php elseif ($inv['status'] == 'unpaid'): ?>
             <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-100 mb-6">
                <p class="text-xs font-bold text-yellow-800 mb-1">Instruksi Pembayaran</p>
                <p class="text-[10px] text-yellow-700 leading-relaxed">
                    Silakan transfer ke:<br>
                    <strong>BCA 1234567890</strong> a.n ISP Billing<br>
                    Dan upload bukti di halaman pelanggan.
                </p>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="text-center">
                <p class="text-[10px] text-gray-400">Terima kasih telah berlangganan.</p>
                <div class="flex flex-col gap-2 mt-4 no-print">
                     <a href="<?= $wa_url ?>" target="_blank" class="w-full bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-600 transition shadow-lg flex justify-center items-center gap-2">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        Kirim ke WhatsApp
                    </a>
                    <div class="flex gap-2">
                        <button onclick="window.print()" class="flex-1 bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-800 transition shadow">
                            Cetak
                        </button>
                        <button onclick="window.close()" class="flex-1 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                            Tutup
                        </button>
                    </div>
                </div>
                <p class="text-[10px] text-gray-400 mt-4 no-print">Tips: Screenshot halaman ini untuk dibagikan secara manual.</p>
            </div>
        </div>
        
        <!-- Decoration Circles -->
        <div class="absolute -left-3 top-40 w-6 h-6 bg-gray-100 rounded-full"></div>
        <div class="absolute -right-3 top-40 w-6 h-6 bg-gray-100 rounded-full"></div>
    </div>

</body>
</html>
