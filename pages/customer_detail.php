<?php
require_once '../config.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: customers.php');
    exit;
}

// Fetch Customer Details
$stmt = $pdo->prepare("
    SELECT c.*, p.package_name, p.price as package_price, o.odp_name, o.zone_area
    FROM customers c 
    LEFT JOIN internet_packages p ON c.package_id = p.id 
    LEFT JOIN odp_points o ON c.odp_id = o.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    die("Pelanggan tidak ditemukan.");
}

$page_title = 'Detail Pelanggan';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Fetch Invoices & Payment History
$stmt_inv = $pdo->prepare("
    SELECT i.*, 
           (SELECT payment_date FROM payments WHERE invoice_id = i.id AND status = 'verified' LIMIT 1) as payment_date,
           (SELECT payment_method FROM payments WHERE invoice_id = i.id AND status = 'verified' LIMIT 1) as payment_method
    FROM invoices i 
    WHERE i.customer_id = ? 
    ORDER BY i.period_year DESC, i.period_month DESC
");
$stmt_inv->execute([$id]);
$invoices = $stmt_inv->fetchAll();

// Analytics
$total_invoices = count($invoices);
$total_paid = 0;
$total_unpaid = 0;
$on_time_count = 0;
$late_count = 0;
$payment_dates = [];

foreach ($invoices as $inv) {
    if ($inv['status'] == 'paid') {
        $total_paid += $inv['amount'];
        
        // Check reliability (On Time vs Late)
        if ($inv['payment_date']) {
            $pay_date = strtotime($inv['payment_date']);
            $due_date = strtotime($inv['due_date']);
            // Add slight buffer for "Same Day" calculation or just strict comparison
            // If pay_date <= due_date (end of day), it's on time.
            // Let's assume due_date is Y-m-d.
            if (date('Y-m-d', $pay_date) <= $inv['due_date']) {
                $on_time_count++;
            } else {
                $late_count++;
            }
            $payment_dates[] = $inv['payment_date'];
        }
    } else {
        $total_unpaid++;
    }
}

// Simple logic for trend: If more late than on time?
$reliability_score = $total_invoices > 0 ? (($on_time_count / ($on_time_count + $late_count + 1)) * 100) : 100; // Simplified

?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

        <!-- Header -->
        <!-- Header & Breadcrumb -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 dark:border-slate-700 pb-4">
            <div>
                 <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="customers.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                                </svg>
                                Pelanggan
                            </a>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Detail</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                 <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Detail Pelanggan</h1>
                 <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Analisa & Riwayat Pembayaran</p>
            </div>
            <a href="customers.php" class="py-2.5 px-4 inline-flex justify-center items-center gap-2 rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 font-medium text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:hover:bg-slate-700 transition-all">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                Kembali
            </a>
        </div>

        <!-- Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Profile Card -->
            <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-5 shadow-sm col-span-1 md:col-span-2">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($customer['name']) ?></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($customer['customer_code']) ?></p>
                    </div>
                    <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium <?= $customer['status'] == 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400' ?>">
                        <?= ucfirst($customer['status']) ?>
                    </span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm mt-6">
                    
                    <!-- Billing & Service -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-slate-700 pb-1">Layanan & Tagihan</h4>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">Paket Internet</p>
                            <p class="font-medium text-gray-800 dark:text-white"><?= htmlspecialchars($customer['package_name']) ?></p>
                            <p class="text-blue-600 dark:text-blue-400 font-semibold text-xs"><?= format_rupiah($customer['package_price']) ?> / bulan</p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">Jadwal Instalasi</p>
                            <p class="font-medium text-gray-800 dark:text-white">
                                <?= $customer['installation_date'] ? date('d F Y', strtotime($customer['installation_date'])) : '<span class="text-gray-400 italic">Belum Dijadwalkan</span>' ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">Jatuh Tempo</p>
                            <p class="font-medium text-gray-800 dark:text-white">Setiap tanggal <?= $customer['due_date_day'] ?? '10' ?></p>
                        </div>
                         <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">Terdaftar Sejak</p>
                            <p class="font-medium text-gray-800 dark:text-white"><?= date('d F Y H:i', strtotime($customer['created_at'])) ?></p>
                        </div>
                    </div>

                    <!-- Tech & Location -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-slate-700 pb-1">Teknis & Lokasi</h4>
                        
                        <div class="grid grid-cols-2 gap-2">
                             <div>
                                <p class="text-gray-500 dark:text-gray-400 text-xs mb-1">IP Address</p>
                                <?php if(!empty($customer['ip_address'])): ?>
                                    <div class="flex items-center gap-2">
                                        <p class="font-mono text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-slate-700 px-2 py-1 rounded border border-gray-100 dark:border-slate-600 inline-block">
                                            <?= $customer['ip_address'] ?>
                                        </p>
                                        <a href="http://<?= $customer['ip_address'] ?>" target="_blank" class="text-blue-600 hover:text-blue-800 transition-colors" title="Remote Webfig">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400 italic text-xs">-</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-xs">MAC Address</p>
                                <p class="font-mono text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-slate-700 px-2 py-0.5 rounded border border-gray-100 dark:border-slate-600 inline-block">
                                    <?= $customer['mac_address'] ?: '<span class="text-gray-400">-</span>' ?>
                                </p>
                            </div>
                        </div>

                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs mb-1">Koneksi ODP</p>
                            <?php if(!empty($customer['odp_name'])): ?>
                                <div class="flex flex-col bg-gray-50 dark:bg-slate-700/50 p-2 rounded-lg border border-gray-100 dark:border-slate-700">
                                    <div class="flex justify-between items-center">
                                        <p class="font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($customer['odp_name']) ?></p>
                                        <span class="text-xs font-mono bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-0.5 rounded">Port: <?= $customer['odp_port'] ?? '-' ?></span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
                                        <svg class="size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                        <?= htmlspecialchars($customer['zone_area']) ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-400 italic text-xs">Belum terhubung ke ODP</span>
                            <?php endif; ?>
                        </div>

                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">Kontak (WhatsApp)</p>
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-gray-800 dark:text-white"><?= htmlspecialchars($customer['phone']) ?></p>
                                <?php
                                    $wa_phone = preg_replace('/[^0-9]/', '', $customer['phone']);
                                    if(substr($wa_phone, 0, 1) === '0') $wa_phone = '62' . substr($wa_phone, 1);
                                ?>
                                <a href="https://wa.me/<?= $wa_phone ?>" target="_blank" class="text-green-500 hover:text-green-600">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                </a>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs mt-0.5"><?= htmlspecialchars($customer['email'] ?? '') ?></p>
                        </div>

                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">Alamat & Koordinat</p>
                            <p class="text-gray-800 dark:text-gray-200 text-xs leading-relaxed mb-1"><?= htmlspecialchars($customer['address']) ?></p>
                            <?php if(!empty($customer['latitude']) && !empty($customer['longitude'])): ?>
                                <a href="https://www.google.com/maps/search/?api=1&query=<?= $customer['latitude'] ?>,<?= $customer['longitude'] ?>" target="_blank" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline">
                                    <svg class="size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                    Cek Maps (<?= $customer['latitude'] ?>, <?= $customer['longitude'] ?>)
                                </a>
                            <?php else: ?>
                                <span class="text-xs text-gray-400 italic">Koordinat belum diset</span>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Stats Card -->
            <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-5 shadow-sm space-y-4">
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Ringkasan Pembayaran</h3>
                
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Dibayar</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?= format_rupiah($total_paid) ?></p>
                    <p class="text-xs text-gray-400 mt-1">Dari <?= count($invoices) ?> Tagihan</p>
                </div>

                <div class="border-t border-gray-100 dark:border-slate-700 pt-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Tren Ketepatan Waktu</p>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="flex w-full h-2 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                            <?php 
                                $total_p = $on_time_count + $late_count; 
                                $pct = $total_p > 0 ? ($on_time_count / $total_p) * 100 : 0;
                            ?>
                            <div class="flex flex-col justify-center overflow-hidden bg-blue-500" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <span class="text-xs font-semibold text-gray-800 dark:text-white"><?= round($pct) ?>%</span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>On-time: <?= $on_time_count ?></span>
                        <span>Late: <?= $late_count ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Table -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                 <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Riwayat & Tren Tagihan</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-[#F8FAFC] dark:bg-slate-900">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">No</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Periode</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tagihan</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Bayar Pada</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status Tren</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (count($invoices) > 0): ?>
                                <?php $no = 1; ?>
                                <?php foreach ($invoices as $inv): ?>
                                    <?php 
                                        // Determine Trend Status
                                        $trend_html = '<span class="text-gray-400">-</span>';
                                        if ($inv['status'] == 'paid' && $inv['payment_date']) {
                                            $p_date = strtotime($inv['payment_date']);
                                            $d_date = strtotime($inv['due_date']);
                                            if (date('Y-m-d', $p_date) <= $inv['due_date']) {
                                                $trend_html = '<span class="inline-flex items-center gap-1 text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded-md"><svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Tepat Waktu</span>';
                                            } else {
                                                $diff = floor(($p_date - $d_date) / (60 * 60 * 24));
                                                $trend_html = '<span class="inline-flex items-center gap-1 text-xs font-semibold text-orange-600 bg-orange-50 px-2 py-1 rounded-md"><svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Telat ' . $diff . ' hari</span>';
                                            }
                                        } elseif ($inv['status'] == 'overdue') {
                                             $trend_html = '<span class="inline-flex items-center gap-1 text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded-md">Overdue</span>';
                                        }
                                    ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?= $no++ ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-white">
                                            <?= date('F Y', mktime(0, 0, 0, $inv['period_month'], 10, $inv['period_year'])) ?>
                                            <div class="text-xs text-gray-400 font-normal">Due: <?= date('d M Y', strtotime($inv['due_date'])) ?></div>
                                        </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-white">
                                        <?= format_rupiah($inv['amount']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-x-1.5 py-1 px-2.5 rounded-full text-xs font-medium <?= $inv['status'] == 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400' : ($inv['status'] == 'pending' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-400' : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400') ?>">
                                            <?= ucfirst($inv['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        <?= $inv['payment_date'] ? date('d M Y H:i', strtotime($inv['payment_date'])) : '-' ?>
                                        <div class="text-xs text-gray-400"><?= $inv['payment_method'] ? ucfirst($inv['payment_method']) : '' ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?= $trend_html ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Belum ada riwayat tagihan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>

<?php 
require_once '../includes/footer.php';
?>
