<?php
session_start();
include '../config.php';

// Prevent browser from caching the page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime("monday this week"));
$month_start = date('Y-m-01');

$daily = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE DATE(orderDate) = '$today'")->fetch_assoc();
$weekly = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE DATE(orderDate) >= '$week_start'")->fetch_assoc();
$monthly = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE DATE(orderDate) >= '$month_start'")->fetch_assoc();

// Earnings Summary Filter
$summary_type = $_GET['summary_type'] ?? 'day';
$summary_date = $_GET['summary_date'] ?? date('Y-m-d');

switch ($summary_type) {
    case 'week':
        $start = date('Y-m-d', strtotime("monday this week", strtotime($summary_date)));
        $earnings = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE orderDate >= '$start'")->fetch_assoc();
        break;
    case 'month':
        $start = date('Y-m-01', strtotime($summary_date)); // Get the 1st of the selected month
        $earnings = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE orderDate >= '$start'")->fetch_assoc();
        break;
    case 'year':
        $start = date('Y-01-01', strtotime($summary_date));
        $earnings = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE orderDate >= '$start'")->fetch_assoc();
        break;
    default:
        $earnings = $conn->query("SELECT SUM(totalAmount) AS total FROM orders WHERE DATE(orderDate) = '$summary_date'")->fetch_assoc();
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

// Sales by Day with Filter
$filter_start = $_GET['filter_start'] ?? date('Y-m-01');
$filter_end = $_GET['filter_end'] ?? date('Y-m-t');

$day_sales_query = $conn->query("SELECT DAYNAME(orderDate) AS day_name, SUM(totalAmount) AS total FROM orders WHERE orderDate BETWEEN '$filter_start' AND '$filter_end' GROUP BY day_name ORDER BY FIELD(day_name, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");

$day_names = [];
$day_totals = [];
while($row = $day_sales_query->fetch_assoc()) {
  $day_names[] = $row['day_name'];
  $day_totals[] = $row['total'];
}

include('../header.php');
?>

<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark"><i class="fas fa-chart-line"></i> Sales Report</h3>
    <div>
      <a href="export_pdf.php?summary_type=<?= $summary_type ?>&summary_date=<?= $summary_date ?>&filter_start=<?= $filter_start ?>&filter_end=<?= $filter_end ?>" class="btn btn-danger me-2">
        <i class="fas fa-file-pdf me-1"></i> PDF
      </a>
      <a href="export_excel.php?summary_type=<?= $summary_type ?>&summary_date=<?= $summary_date ?>&filter_start=<?= $filter_start ?>&filter_end=<?= $filter_end ?>" class="btn btn-success">
        <i class="fas fa-file-excel me-1"></i> Excel
      </a>
    </div>
  </div>

<!-- Ringkasan Jualan -->
<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm text-white bg-success">
      <div class="card-body">
        <h6 class="card-title mb-1">Today's Sales</h6>
        <h4 class="mb-0">RM <?= number_format($daily['total'] ?? 0, 2) ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm text-white bg-warning">
      <div class="card-body">
        <h6 class="card-title mb-1">This Week</h6>
        <h4 class="mb-0">RM <?= number_format($weekly['total'] ?? 0, 2) ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm text-white bg-info">
      <div class="card-body">
        <h6 class="card-title mb-1">This Month</h6>
        <h4 class="mb-0">RM <?= number_format($monthly['total'] ?? 0, 2) ?></h4>
      </div>
    </div>
  </div>
</div>

<!-- Borang Penapis Ringkasan -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body">
    <form class="row g-3 align-items-end" method="get">
      <div class="col-md-4">
        <label class="form-label">Summary Type</label>
        <select name="summary_type" class="form-select">
          <option value="day" <?= $summary_type == 'day' ? 'selected' : '' ?>>By Day</option>
          <option value="week" <?= $summary_type == 'week' ? 'selected' : '' ?>>By Week</option>
          <option value="month" <?= $summary_type == 'month' ? 'selected' : '' ?>>By Month</option>
          <option value="year" <?= $summary_type == 'year' ? 'selected' : '' ?>>By Year</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Choose Date</label>
        <input type="date" name="summary_date" value="<?= $summary_date ?>" class="form-control">
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">View Earnings</button>
      </div>
    </form>

    <div class="alert alert-primary mt-4 mb-0" role="alert">
      <strong>Total Earnings:</strong> RM <?= number_format($earnings['total'] ?? 0, 2) ?>
    </div>
  </div>
</div>


  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
      <h6 class="mb-0">Top 5 Best-Selling Items</h6>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Item</th>
            <th>Quantity Sold</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($top_items as $item => $qty): ?>
            <tr>
              <td><?= htmlspecialchars($item) ?></td>
              <td><?= $qty ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <h6 class="mb-0">Sales by Day</h6>
    </div>
    <div class="card-body">
      <form class="row g-3 mb-4" method="get">
        <input type="hidden" name="summary_type" value="<?= $summary_type ?>">
        <input type="hidden" name="summary_date" value="<?= $summary_date ?>">
        <div class="col-md-4">
          <label>Start Date</label>
          <input type="date" name="filter_start" value="<?= $filter_start ?>" class="form-control">
        </div>
        <div class="col-md-4">
          <label>End Date</label>
          <input type="date" name="filter_end" value="<?= $filter_end ?>" class="form-control">
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-outline-secondary mt-4">Filter</button>
        </div>
      </form>
      <canvas id="daySalesChart" height="120"></canvas>
    </div>
  </div>
</div>

<?php include('../footer.php'); ?>

<script>

window.addEventListener('pageshow', function(event) {
    if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
        window.location.reload();
    }
});

const ctx = document.getElementById('daySalesChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($day_names) ?>,
    datasets: [{
      label: 'RM',
      data: <?= json_encode($day_totals) ?>,
      backgroundColor: 'rgba(13, 110, 253, 0.6)',
      borderColor: 'rgba(13, 110, 253, 1)',
      borderWidth: 1,
      borderRadius: 5
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false }
    },
    scales: {
      y: {
        beginAtZero: true,
        title: { display: true, text: 'Sales (RM)' }
      },
      x: {
        title: { display: true, text: 'Day of Week' }
      }
    }
  }
});
</script>
