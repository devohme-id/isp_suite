<?php
require_once '../config.php';
require_login();
require_role(['Administrator', 'Finance']);

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$filename = "Laporan_Keuangan_$start_date-$end_date.xls";

// Backend Logic (Reused)
// 1. Total Income
$stmt = $pdo->prepare("SELECT SUM(amount_paid) FROM payments WHERE status = 'verified' AND DATE(payment_date) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_income = $stmt->fetchColumn() ?: 0;

// 2. Total Expenses
$stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_expense = $stmt->fetchColumn() ?: 0;

// 3. Net Balance
$net_balance = $total_income - $total_expense;

// 4. Income Breakdown
$stmt = $pdo->prepare("SELECT payment_method, SUM(amount_paid) as total FROM payments WHERE status = 'verified' AND DATE(payment_date) BETWEEN ? AND ? GROUP BY payment_method ORDER BY total DESC");
$stmt->execute([$start_date, $end_date]);
$income_breakdown = $stmt->fetchAll();

// 5. Expense Breakdown
$stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ? GROUP BY category ORDER BY total DESC");
$stmt->execute([$start_date, $end_date]);
$expense_breakdown = $stmt->fetchAll();

// Excel Headers
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
?>

<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .header-section { margin-bottom: 20px; }
        .text-right { text-align: right; }
        .bg-green { background-color: #dcfce7; color: #166534; }
        .bg-red { background-color: #fee2e2; color: #991b1b; }
        .bg-blue { background-color: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>
    <div class="title">Laporan Keuangan: <?= $start_date ?> s/d <?= $end_date ?></div>
    
    <h3>Ringkasan</h3>
    <table>
        <tr>
            <th class="bg-blue">Total Pemasukan</th>
            <th class="bg-red">Total Pengeluaran</th>
            <th>Laba Bersih</th>
        </tr>
        <tr>
            <td><?= format_rupiah($total_income) ?></td>
            <td><?= format_rupiah($total_expense) ?></td>
            <td style="font-weight: bold; color: <?= $net_balance >= 0 ? 'green' : 'red' ?>"><?= format_rupiah($net_balance) ?></td>
        </tr>
    </table>

    <br>

    <h3>Rincian Pemasukan</h3>
    <table>
        <thead>
            <tr>
                <th>Metode Pembayaran</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($income_breakdown as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['payment_method']) ?></td>
                <td class="text-right"><?= format_rupiah($row['total']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th>Total Pemasukan</th>
                <th class="text-right"><?= format_rupiah($total_income) ?></th>
            </tr>
        </tfoot>
    </table>

    <br>

    <h3>Rincian Pengeluaran</h3>
    <table>
        <thead>
            <tr>
                <th>Kategori</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
             <?php foreach($expense_breakdown as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td class="text-right"><?= format_rupiah($row['total']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
         <tfoot>
            <tr>
                <th>Total Pengeluaran</th>
                <th class="text-right"><?= format_rupiah($total_expense) ?></th>
            </tr>
        </tfoot>
    </table>

</body>
</html>
