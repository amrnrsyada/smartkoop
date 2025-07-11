<?php 
require 'vendor/autoload.php';
include 'config.php';

// Ambil parameter dari URL
$summary_type = $_GET['summary_type'] ?? 'day';
$summary_date = $_GET['summary_date'] ?? date('Y-m-d');
$filter_start = $_GET['filter_start'] ?? date('Y-m-01');
$filter_end = $_GET['filter_end'] ?? date('Y-m-t');

// Sales Summary
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime("monday this week"));
$month_start = date('Y-m-01');

$daily = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE DATE(orderDate) = '$today'")->fetch_assoc();
$weekly = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE DATE(orderDate) >= '$week_start'")->fetch_assoc();
$monthly = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE DATE(orderDate) >= '$month_start'")->fetch_assoc();

// Total Earnings (ikut summary_type)
$earnings = ['total' => 0];
$start = '';

switch ($summary_type) {
    case 'week':
        $start = date('Y-m-d', strtotime("monday this week", strtotime($summary_date)));
        $result = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE orderDate >= '$start'");
        if ($result) $earnings = $result->fetch_assoc();
        break;

    case 'month':
        $start = date('Y-m-01', strtotime($summary_date));
        $end = date('Y-m-t', strtotime($summary_date));
        $result = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE orderDate BETWEEN '$start' AND '$end'");
        if ($result) $earnings = $result->fetch_assoc();
        break;

    case 'year':
        $start = date('Y-01-01', strtotime($summary_date));
        $result = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE orderDate >= '$start'");
        if ($result) $earnings = $result->fetch_assoc();
        break;

    default:
        $start = $summary_date;
        $result = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE DATE(orderDate) = '$summary_date'");
        if ($result) $earnings = $result->fetch_assoc();
        break;
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

// Sales by Day (between filter_start and filter_end)
$sales_by_day_result = $conn->query("
    SELECT DATE(orderDate) AS sale_date, SUM(totalAmount) AS total
    FROM orders
    WHERE DATE(orderDate) BETWEEN '$filter_start' AND '$filter_end'
    GROUP BY DATE(orderDate)
    ORDER BY DATE(orderDate)
");

$sales_by_day_rows = '';
while ($row = $sales_by_day_result->fetch_assoc()) {
    $sales_by_day_rows .= '<tr>
        <td>' . htmlspecialchars($row['sale_date']) . '</td>
        <td align="right">RM ' . number_format($row['total'], 2) . '</td>
    </tr>';
}

// Generate HTML untuk PDF
$summaryLabel = ucfirst($summary_type);
$labelDate = ($summary_type === 'day') ? $summary_date : "From $start";

$html = '
<h2>Sales Report - PETAKOM MART</h2>
<p><strong>Daily Sales:</strong> RM ' . number_format($daily['total'] ?? 0, 2) . '</p>
<p><strong>Weekly Sales:</strong> RM ' . number_format($weekly['total'] ?? 0, 2) . '</p>
<p><strong>Monthly Sales:</strong> RM ' . number_format($monthly['total'] ?? 0, 2) . '</p>
<p><strong>Total Earnings (' . $summaryLabel . '):</strong> RM ' . number_format($earnings['total'] ?? 0, 2) . ' <em>(' . $labelDate . ')</em></p>

<h3>Top 5 Best-Selling Items</h3>
<table border="1" cellspacing="0" cellpadding="5" width="100%">
  <thead>
    <tr style="background-color:#f2f2f2;">
      <th align="left">Item Name</th>
      <th align="right">Quantity Sold</th>
    </tr>
  </thead>
  <tbody>';

foreach ($top_items as $itemName => $qty) {
    $html .= '
    <tr>
      <td>' . htmlspecialchars($itemName) . '</td>
      <td align="right">' . $qty . '</td>
    </tr>';
}

$html .= '
  </tbody>
</table>

<h3>Sales by Day (' . htmlspecialchars($filter_start) . ' to ' . htmlspecialchars($filter_end) . ')</h3>
<table border="1" cellspacing="0" cellpadding="5" width="100%">
  <thead>
    <tr style="background-color:#f2f2f2;">
      <th align="left">Date</th>
      <th align="right">Total Sales</th>
    </tr>
  </thead>
  <tbody>' . $sales_by_day_rows . '
  </tbody>
</table>';

// Output PDF
$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML($html);
$mpdf->Output('sales-report.pdf', 'D');
exit;
