<?php
require '../vendor/autoload.php';
include '../config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Tarikh semasa
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime("monday this week"));
$month_start = date('Y-m-01');

// Jumlah jualan ringkasan
$daily = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE DATE(orderDate) = '$today'")->fetch_assoc();
$weekly = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE DATE(orderDate) >= '$week_start'")->fetch_assoc();
$monthly = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE DATE(orderDate) >= '$month_start'")->fetch_assoc();

// Earnings Summary (Filtered)
$summary_type = $_GET['summary_type'] ?? 'day';
$summary_date = $_GET['summary_date'] ?? $today;
$earnings = ['total' => 0]; // Default empty
$start = '';

if ($summary_type === 'week') {
    $start = date('Y-m-d', strtotime("monday this week", strtotime($summary_date)));
    $result = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE orderDate >= '$start'");
    if ($result) $earnings = $result->fetch_assoc();

} elseif ($summary_type === 'month') {
    $start = date('Y-m-01', strtotime($summary_date));
    $end = date('Y-m-t', strtotime($summary_date));
    $result = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE orderDate BETWEEN '$start' AND '$end'");
    if ($result) $earnings = $result->fetch_assoc();

} elseif ($summary_type === 'year') {
    $start = date('Y-01-01', strtotime($summary_date));
    $result = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE orderDate >= '$start'");
    if ($result) $earnings = $result->fetch_assoc();

} else {
    $start = $summary_date;
    $result = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE DATE(orderDate) = '$summary_date'");
    if ($result) $earnings = $result->fetch_assoc();
}

// Top 5 Best-Selling Items
$top_items_map = [];
$order_rows = $conn->query("SELECT orderDetails FROM orders");
while ($row = $order_rows->fetch_assoc()) {
    $details = json_decode($row['orderDetails'], true);
    foreach ($details as $item) {
        $name = $item['name'];
        $qty = $item['quantity'];
        $top_items_map[$name] = ($top_items_map[$name] ?? 0) + $qty;
    }
}
arsort($top_items_map);
$top_items = array_slice($top_items_map, 0, 5, true);

// Filter untuk jualan harian
$filter_start = $_GET['filter_start'] ?? date('Y-m-01');
$filter_end = $_GET['filter_end'] ?? date('Y-m-t');

$day_sales_query = $conn->query("SELECT DAYNAME(orderDate) AS day_name, SUM(totalAmount) AS total 
    FROM orders 
    WHERE orderDate BETWEEN '$filter_start' AND '$filter_end' 
    GROUP BY day_name 
    ORDER BY FIELD(day_name, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
$day_sales = [];
while ($row = $day_sales_query->fetch_assoc()) {
    $day_sales[$row['day_name']] = $row['total'];
}

// Setup Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Sales Report');

// Header
$sheet->setCellValue('A1', 'Sales Report - PETAKOM MART');

// Sales Summary
$sheet->setCellValue('A3', 'Daily Sales');
$sheet->setCellValue('B3', 'RM ' . number_format($daily['total'] ?? 0, 2));
$sheet->setCellValue('A4', 'Weekly Sales');
$sheet->setCellValue('B4', 'RM ' . number_format($weekly['total'] ?? 0, 2));
$sheet->setCellValue('A5', 'Monthly Sales');
$sheet->setCellValue('B5', 'RM ' . number_format($monthly['total'] ?? 0, 2));

// Filtered Earnings Summary
$summaryTypeLabel = ucfirst($summary_type);
$sheet->setCellValue('A6', "Total Earnings ($summaryTypeLabel)");
$sheet->setCellValue('B6', 'RM ' . number_format($earnings['total'] ?? 0, 2));
$sheet->setCellValue('C6', "($start)");

// Top Items
$sheet->setCellValue('A8', 'Top 5 Best-Selling Items');
$sheet->setCellValue('A9', 'Item Name');
$sheet->setCellValue('B9', 'Quantity Sold');

$row = 10;
foreach ($top_items as $itemName => $quantity) {
    $sheet->setCellValue("A$row", $itemName);
    $sheet->setCellValue("B$row", $quantity);
    $row++;
}

// Daily Sales Summary
$row += 2;
$sheet->setCellValue("A$row", 'Sales by Day (' . $filter_start . ' to ' . $filter_end . ')');
$row++;
$sheet->setCellValue("A$row", 'Day');
$sheet->setCellValue("B$row", 'Total (RM)');

foreach ($day_sales as $day => $total) {
    $row++;
    $sheet->setCellValue("A$row", $day);
    $sheet->setCellValue("B$row", number_format($total, 2));
}

// Output Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="sales-report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
