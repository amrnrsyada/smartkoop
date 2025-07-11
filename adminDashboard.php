<?php  
session_start();
include 'config.php';

// Prevent browser from caching the page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'staff') {
  header("Location: index.php");
  exit();
}

//welcome
$email = mysqli_real_escape_string($conn, $_SESSION['email']);
$name = $email;
$query = "SELECT name FROM users WHERE email = '$email'";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $name = !empty($row['name']) ? $row['name'] : $email;
}

//total items
$result_items = mysqli_query($conn, "SELECT COUNT(*) AS total_items FROM items");
$total_items = mysqli_fetch_assoc($result_items)['total_items'] ?? 0;

//total sales
$result_sales = mysqli_query($conn, "SELECT SUM(totalAmount) AS total_sales FROM orders WHERE payStatus = 'Paid'");
$total_sales = mysqli_fetch_assoc($result_sales)['total_sales'] ?? 0;

//total orders
$result_orders = mysqli_query($conn, "SELECT COUNT(*) AS total_orders FROM orders");
$total_orders = mysqli_fetch_assoc($result_orders)['total_orders'] ?? 0;

//sales trend monthly
$result_trend = mysqli_query($conn, "
    SELECT DATE_FORMAT(orderDate, '%Y-%m') AS order_month, 
           SUM(totalAmount) AS monthly_sales
    FROM orders
    WHERE payStatus = 'Paid'
    GROUP BY order_month
    ORDER BY order_month
");

$months = [];
$sales = [];
while ($row = mysqli_fetch_assoc($result_trend)) {
    $months[] = $row['order_month'];
    $sales[] = $row['monthly_sales'];
}

//latest orders
$result_latest = mysqli_query($conn, "
    SELECT orderID, name, email, totalAmount, orderDate, orderStatus
    FROM orders
    ORDER BY orderDate DESC
    LIMIT 5
");

//expired reminder alert
$expiring_items = [];
$target_date = date('Y-m-d', strtotime('+2 days')); //remind 2 hari sebelum
$expiry_query = "SELECT itemName, expiryDate FROM items WHERE expiryDate = '$target_date'";
$expiry_result = mysqli_query($conn, $expiry_query);
while ($item = mysqli_fetch_assoc($expiry_result)) {
    $expiring_items[] = $item;
}

//low stock reminder
$low_stock_items = [];
$stock_query = "SELECT itemName, availableStock FROM items WHERE availableStock < 3"; //kurang dari 3
$stock_result = mysqli_query($conn, $stock_query);
while ($item = mysqli_fetch_assoc($stock_result)) {
    $low_stock_items[] = $item;
}

include('header.php');
?>

<style>
    .hover-effect {transition: transform 0.2s ease, box-shadow 0.2s ease;}
    .hover-effect:hover {transform: translateY(-5px);box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);}
    .chart-container {width: 100%;min-height: 300px;}
    @media (max-width: 768px) {#salesChart {max-width: 100%;}}
</style>

<div class="container py-5">

    <!-- Notification + Welcome Row -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Welcome, <?= htmlspecialchars($name) ?></h2>
        <button class="btn btn-warning position-relative" data-bs-toggle="modal" data-bs-target="#alertModal">
            <i class="fa-solid fa-bell"></i>
            <?php if (!empty($expiring_items) || !empty($low_stock_items)): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    !
                    <span class="visually-hidden">Alert</span>
                </span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Total data -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary shadow-sm hover-effect">
                <div class="card-body">
                    <h6 class="card-title">Total Items</h6>
                    <h3><?= $total_items ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success shadow-sm hover-effect">
                <div class="card-body">
                    <h6 class="card-title">Total Sales</h6>
                    <h3>RM <?= number_format($total_sales, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info shadow-sm hover-effect">
                <div class="card-body">
                    <h6 class="card-title">Total Orders</h6>
                    <h3><?= $total_orders ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Trend -->
    <div class="card mb-4 shadow">
        <div class="card-body">
            <h5 class="card-title">Sales Trend (Monthly)</h5>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Latest Order -->
    <div class="card shadow">
        <div class="card-body">
            <h5 class="card-title">Latest Orders</h5>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>Name</th>
                            <th>Total (RM)</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = mysqli_fetch_assoc($result_latest)): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['name'] ?? 'N/A') ?></td> 
                                <td><?= number_format($order['totalAmount'], 2) ?></td>
                                <td><?= $order['orderDate'] ?></td>
                                <td><span class="badge bg-success"><?= htmlspecialchars($order['orderStatus']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Alert Modal -->
<div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="alertModalLabel"><i class="fa-solid fa-bell"></i> Reminder Alerts</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php if (!empty($expiring_items)): ?>
            <div >
                <strong>Expiring Soon:</strong>
                <ul class="mb-0">
                    <?php foreach ($expiring_items as $item): ?>
                        <li><strong><?= htmlspecialchars($item['itemName']) ?></strong> (Expiry: <?= htmlspecialchars($item['expiryDate']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($low_stock_items)): ?>
            <div >
                <strong>Low Stock:</strong>
                <ul class="mb-0">
                    <?php foreach ($low_stock_items as $item): ?>
                        <li><strong><?= htmlspecialchars($item['itemName']) ?></strong> (Stock: <?= htmlspecialchars($item['availableStock']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($expiring_items) && empty($low_stock_items)): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> No current alerts. All items are in good condition.
            </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php'); ?>


<script>

    window.addEventListener('pageshow', function(event) {
        if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
            window.location.reload();
        }
    });

    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [{
                label: 'Monthly Sales (RM)',
                data: <?= json_encode($sales) ?>,
                borderColor: 'rgba(13, 110, 253, 1)',
                backgroundColor: 'rgba(13, 110, 253, 0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                min: 0,           // Minimum value on y-axis
                max: 400,        // Maximum value on y-axis
                title: { display: true, text: 'Amount (RM)' }
            },
            x: {
                title: { display: true, text: 'Month' },
                ticks: {
                    maxRotation: 45,
                    minRotation: 0,
                    autoSkip: true
                },
                min: 0,
                max: 11
            }
        }
    }

    });
</script>
