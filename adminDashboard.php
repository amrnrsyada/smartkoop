<?php  
session_start();
include 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'staff') {
  header("Location: index.php");
  exit();
}

$email = mysqli_real_escape_string($conn, $_SESSION['email']);
$name = $email;
$query = "SELECT name FROM users WHERE email = '$email'";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $name = !empty($row['name']) ? $row['name'] : $email;
}

$result_items = mysqli_query($conn, "SELECT COUNT(*) AS total_items FROM items");
$total_items = mysqli_fetch_assoc($result_items)['total_items'] ?? 0;

$result_sales = mysqli_query($conn, "SELECT SUM(totalAmount) AS total_sales FROM orders WHERE payStatus = 'Paid'");
$total_sales = mysqli_fetch_assoc($result_sales)['total_sales'] ?? 0;

$result_orders = mysqli_query($conn, "SELECT COUNT(*) AS total_orders FROM orders");
$total_orders = mysqli_fetch_assoc($result_orders)['total_orders'] ?? 0;

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

$result_weekly = mysqli_query($conn, "
    SELECT DATE_FORMAT(orderDate, '%Y-%u') AS order_week,
           SUM(totalAmount) AS weekly_sales
    FROM orders
    WHERE payStatus = 'Paid'
    GROUP BY order_week
    ORDER BY order_week
");

$weeks = [];
$weekly_sales = [];
while ($row = mysqli_fetch_assoc($result_weekly)) {
    $weeks[] = $row['order_week'];
    $weekly_sales[] = $row['weekly_sales'];
}

$result_latest = mysqli_query($conn, "
    SELECT orderID, name, email, totalAmount, orderDate, orderStatus
    FROM orders
    ORDER BY orderDate DESC
    LIMIT 5
");

$expiring_items = [];
$today = date('Y-m-d');
$target_date = date('Y-m-d', strtotime('+2 days'));

$expiry_query = "SELECT itemName, expiryDate FROM items WHERE expiryDate = '$target_date'";
$expiry_result = mysqli_query($conn, $expiry_query);

while ($item = mysqli_fetch_assoc($expiry_result)) {
    $expiring_items[] = $item;
}

$low_stock_items = [];

$stock_query = "SELECT itemName, availableStock FROM items WHERE availableStock < 4";
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
    <?php if (!empty($expiring_items)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert"><i class="fa-regular fa-bell"></i>
            <strong>Heads up!</strong> The following item(s) will expire in 2 days:
            <ul class="mb-0">
                <?php foreach ($expiring_items as $item): ?>
                    <li><strong><?= htmlspecialchars($item['itemName']) ?></strong> (Expiry: <?= htmlspecialchars($item['expiryDate']) ?>)</li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($low_stock_items)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fa-regular fa-bell"></i>
            <strong>Low Stock Alert!</strong> The following item(s) have less than 3 left in stock:
            <ul class="mb-0">
                <?php foreach ($low_stock_items as $item): ?>
                    <li><strong><?= htmlspecialchars($item['itemName']) ?></strong> (Stock: <?= htmlspecialchars($item['availableStock']) ?>)</li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>


    <h2 class="mb-4">Welcome, <?= htmlspecialchars($name) ?></h2>

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

    <div class="card mb-4 shadow">
        <div class="card-body">
            <h5 class="card-title">Sales Trend (Monthly | Weekyly)</h5>
            <div class="chart-container" style="position: relative; width: 100%; overflow-x: auto;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

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
                                <td><?= htmlspecialchars($order['name']) ?></td>
                                <td><?= number_format($order['totalAmount'], 2) ?></td>
                                <td><?= $order['orderDate'] ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($order['orderStatus']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<script>
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
        },
        {
                label: 'Weekly Sales (RM)',
                data: <?= json_encode($weekly_sales) ?>,
                borderColor: 'rgba(40, 167, 69, 1)',
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                fill: true,
                tension: 0.4
        }
    ]},
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'RM' }
            },
            x: {
                title: { display: true, text: 'Month/ Week' },
                ticks: {
                    maxRotation: 45,
                    minRotation: 0,
                    autoSkip: true
                }
            }
        }
    }
});
</script>
