<?php 
session_start();
include 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'customer') {
  header("Location: index.php");
  exit();
}

$email = mysqli_real_escape_string($conn, $_SESSION['email']);
$name = $email;
$result = mysqli_query($conn, "SELECT name, wallet_balance FROM users WHERE email = '$email'");

if ($row = mysqli_fetch_assoc($result)) {
    $name = !empty($row['name']) ? $row['name'] : $email;
    $wallet_balance = number_format($row['wallet_balance'], 2);
} else {
    $wallet_balance = "0.00";
}

// Total spend
$query_total = mysqli_query($conn, "SELECT SUM(totalAmount) AS total_spent FROM orders WHERE email = '$email' AND payStatus = 'Paid'");
$data_total = mysqli_fetch_assoc($query_total);
$total_spent = $data_total['total_spent'] ?? 0;

// Total orders
$query_count = mysqli_query($conn, "SELECT COUNT(*) AS total_orders FROM orders WHERE email = '$email' AND payStatus = 'Paid'");
$data_count = mysqli_fetch_assoc($query_count);
$total_orders = $data_count['total_orders'] ?? 0;

// Last purchase
$query_last = mysqli_query($conn, "SELECT MAX(orderDate) AS last_order FROM orders WHERE email = '$email' AND payStatus = 'Paid'");
$data_last = mysqli_fetch_assoc($query_last);
$last_order = $data_last['last_order'] ?? '-';

// Spending chart data
$query_graph = mysqli_query($conn, "
    SELECT DATE_FORMAT(orderDate, '%Y-%m') AS order_month, 
           SUM(totalAmount) AS monthly_spend
    FROM orders
    WHERE email = '$email' AND payStatus = 'Paid'
    GROUP BY order_month
    ORDER BY order_month
");

$months = [];
$spend = [];
while ($row = mysqli_fetch_assoc($query_graph)) {
    $months[] = $row['order_month'];
    $spend[] = $row['monthly_spend'];
}

include('header.php');
?>

<style>
    .hover-effect {transition: transform 0.2s ease, box-shadow 0.2s ease;}
    .hover-effect:hover {transform: translateY(-5px);box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);}
</style>

<div class="container py-5">
  <h2 class="fw-bold mb-4">Welcome, <?= htmlspecialchars($name) ?></h2>

  <div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
      <div class="card text-white bg-primary shadow-sm hover-effect h-100">
        <div class="card-body">
          <h6 class="card-title">Wallet Balance</h6>
          <h4 class="card-text">RM <?= $wallet_balance ?></h4>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="card text-white bg-success shadow-sm hover-effect h-100">
        <div class="card-body">
          <h6 class="card-title">Total Spend</h6>
          <h4 class="card-text">RM <?= number_format($total_spent, 2) ?></h4>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="card text-white bg-info shadow-sm hover-effect h-100">
        <div class="card-body">
          <h6 class="card-title">Total Orders</h6>
          <h4 class="card-text"><?= $total_orders ?></h4>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="card text-white bg-dark shadow-sm hover-effect h-100">
        <div class="card-body">
          <h6 class="card-title">Last Purchase</h6>
          <h5 class="card-text">
            <?= $last_order !== '-' ? date("d M Y, H:i", strtotime($last_order)) : '-' ?>
          </h5>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow mb-4">
    <div class="card-body">
      <h5 class="card-title mb-4">Spending Trend (Per Month)</h5>
      <canvas id="spendingChart" height="120"></canvas>
    </div>
  </div>
</div>

<?php include('footer.php'); ?>

<script>
window.onload = () => {
    const ctx = document.getElementById('spendingChart').getContext('2d');
    const spendingChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [{
                label: 'Total Spend (RM)',
                data: <?= json_encode($spend) ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderColor: '#0d6efd',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'RM ' + value.toFixed(2);
                        }
                    },
                    title: {
                        display: true,
                        text: 'Amount (RM)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            }
        }
    });
};
</script>
