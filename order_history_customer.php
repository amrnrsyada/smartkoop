<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT * FROM orders WHERE email = ? ORDER BY orderDate DESC");
$stmt->bind_param("s", $email);
$stmt->execute();
$orders = $stmt->get_result();
include('header.php');
?>

<style>
    body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
    .card { border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
    .table th { background-color: #f8f9fa; }
    .img-thumbnail { width: 80px; height: 80px; object-fit: cover; }
</style>

<div class="container-fluid py-5">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h3 class="fw-bold text-dark d-flex align-items-center gap-2 mb-4">
                    <i class="fas fa-shopping-cart"></i> My Orders
                </h3>
            </div>

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
                                }
                                ?>
                            </td>
                            <td><?= number_format($row['totalAmount'], 2) ?></td>
                            <td><?= date("d M Y, H:i", strtotime($row['orderDate'])) ?></td>
                            <td>
                                <span class="badge 
                                    <?php
                                    switch ($row['orderStatus']) {
                                        case 'Preparing': echo 'bg-warning text-dark'; break;
                                        case 'Completed': echo 'bg-success'; break;
                                        case 'Cancelled': echo 'bg-danger'; break;
                                        default: echo 'bg-secondary';
                                    }
                                    ?>">
                                    <?= htmlspecialchars($row['orderStatus']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2 justify-content-center">
                                    <a href="receipt.php?orderID=<?= urlencode($row['orderID']) ?>" 
                                       class="btn btn-sm btn-success" 
                                       target="_blank">
                                        <i class="fas fa-receipt"></i> Receipt
                                    </a>
                                    <?php if ($row['orderStatus'] === 'Preparing' || $row['orderStatus'] === 'Processing'): ?>
                                    <button class="btn btn-sm btn-danger cancel-btn" 
                                            data-order-id="<?= $row['orderID'] ?>"
                                            data-amount="<?= $row['totalAmount'] ?>">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No orders found.</td></tr>
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
    $('#orderTable').DataTable({
        responsive: true,
        autoWidth: false
    });

    // Cancel order functionality
    $('.cancel-btn').click(function() {
        const orderID = $(this).data('order-id');
        const amount = $(this).data('amount');
        
        Swal.fire({
            title: 'Cancel Order?',
            text: `Are you sure you want to cancel order?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, cancel',
            cancelButtonText: 'No, keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show additional confirmation message
                Swal.fire({
                    title: 'Cancellation Request Submitted',
                    html: 'Your cancellation request has been submitted.<br><br>Please wait for admin to confirm and you will get your refund.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Proceed with AJAX call after they acknowledge the message
                    $.ajax({
                        url: 'cancel_order.php',
                        method: 'POST',
                        data: { 
                            orderID: orderID,
                            amount: amount
                        },
                        success: function(response) {
                            const res = JSON.parse(response);
                            if (res.success) {
                                Swal.fire({
                                    title: 'Order Cancelled',
                                    text: res.message,
                                    icon: 'success'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to cancel order', 'error');
                        }
                    });
                });
            }
        });
    });
});
</script>