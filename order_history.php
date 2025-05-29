<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['email'];
$role = $_SESSION['role'];

// Staff dapat semua order, user hanya order sendiri
if ($role === 'staff') {
    $stmt = $conn->prepare("SELECT * FROM orders ORDER BY orderDate DESC");
} else {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE email = ? ORDER BY orderDate DESC");
    $stmt->bind_param("s", $email);
}

$stmt->execute();
$orders = $stmt->get_result();
include('header.php');
?>

<style>
    body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
    .card { border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
    .table th { background-color: #f8f9fa; }
    .action-btn-group .btn { padding: 0.375rem 0.75rem; }
    .img-thumbnail { width: 80px; height: 80px; object-fit: cover; }
    .modal-header, .modal-footer { border: none; }
</style>

<div class="container-fluid py-5">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h3 class="fw-bold text-dark d-flex align-items-center gap-2 mb-4">
                    <i class="fas fa-shopping-cart"></i> Order History
                </h3>
            </div>

            <!-- Items Table -->
            <div class="table-responsive">
                <table id="orderTable" class="table table-striped table-hover" style="width:100%">
                    <thead class="table-light text-center">
                        <tr>
                        <th>#</th>
                        <th>Order ID</th>
                        <th>Items</th>
                        <th>Total (RM)</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($orders->num_rows > 0): ?>
                    <?php $i = 1; while($row = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['orderID']) ?></td>
                            <td style="text-align: left;">
                                <?php
                                $items = json_decode($row['orderDetails'], true);
                                if (is_array($items)) {
                                    foreach ($items as $item) {
                                        $itemName = htmlspecialchars($item['name']);
                                        $quantity = (int) $item['quantity'];
                                        echo "{$itemName} x {$quantity}<br>";
                                    }
                                } else {
                                    echo 'Invalid order data';
                                }
                                ?>
                            </td>
                            <td><?= number_format($row['totalAmount'], 2) ?></td>
                            <td><?= date("d M Y, H:i", strtotime($row['orderDate'])) ?></td>
                            <td>
                                <span class="badge 
                                    <?php
                                    switch ($row['orderStatus']) {
                                        case 'Pending': echo 'bg-warning text-dark'; break;
                                        case 'Completed': echo 'bg-success'; break;
                                        case 'Cancelled': echo 'bg-danger'; break;
                                        default: echo 'bg-success';
                                    }
                                    ?>">
                                    <?= htmlspecialchars($row['orderStatus']) ?>
                                </span>
                            </td>
                            <td>
                            <a href="receipt.php?orderID=<?= urlencode($row['orderID']) ?>" class="btn btn-sm btn-success" target="_blank">Receipt</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No order history found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<script>
$(document).ready(function() {
    // DataTable Initialization
    $('#orderTable').DataTable({
        "responsive": true,
        "autoWidth": false
    });
});

</script>