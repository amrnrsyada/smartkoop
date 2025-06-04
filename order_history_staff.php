<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'staff') {
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM orders ORDER BY orderDate DESC");
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
    .status-select { min-width: 120px; }
</style>

<div class="container-fluid py-5">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h3 class="fw-bold text-dark d-flex align-items-center gap-2 mb-4">
                    <i class="fas fa-shopping-cart"></i> Order Management
                </h3>
                <a href="notifications.php" class="btn btn-warning position-relative">
                    <i class="fas fa-bell"></i>
                    <?php
                    $unread = $conn->query("SELECT COUNT(*) FROM notifications WHERE email = '{$_SESSION['email']}' AND is_read = FALSE")->fetch_row()[0];
                    if ($unread > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $unread ?>
                    </span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="table-responsive">
                <table id="orderTable" class="table table-striped table-hover" style="width:100%">
                    <thead class="table-light text-center">
                        <tr>
                            <th>#</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total (RM)</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($orders->num_rows > 0): ?>
                    <?php $i = 1; while($row = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['orderID']) ?></td>
                            <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
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
                                <select class="form-select status-select" data-order-id="<?= $row['orderID'] ?>">
                                    <option value="Preparing" <?= $row['orderStatus'] === 'Preparing' ? 'selected' : '' ?>>Preparing</option>
                                    <option value="Completed" <?= $row['orderStatus'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= $row['orderStatus'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </td>
                            <td>
                                <span class="badge <?= $row['payStatus'] === 'Paid' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= htmlspecialchars($row['payStatus']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="receipt.php?orderID=<?= urlencode($row['orderID']) ?>" class="btn btn-sm btn-success" target="_blank">
                                        <i class="fas fa-receipt"></i> Receipt
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9">No orders found.</td></tr>
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

    // Update order status
    $('.status-select').change(function() {
        const orderID = $(this).data('order-id');
        const newStatus = $(this).val();
        
        Swal.fire({
            title: 'Update Status?',
            text: `Change order ${orderID} to ${newStatus}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Update',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'update_order_status.php',
                    method: 'POST',
                    data: { orderID: orderID, status: newStatus },
                    success: function(response) {
                        const res = JSON.parse(response);
                        if (res.success) {
                            Swal.fire('Success', res.message, 'success');
                            // Update the badge color
                            const badgeClass = newStatus === 'Completed' ? 'bg-success' : 
                                            newStatus === 'Cancelled' ? 'bg-danger' : 'bg-warning text-dark';
                            $(this).removeClass('bg-success bg-danger bg-warning text-dark').addClass(badgeClass);
                        } else {
                            Swal.fire('Error', res.message, 'error');
                            // Revert selection
                            $(this).val($(this).data('previous-value'));
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to update status', 'error');
                        $(this).val($(this).data('previous-value'));
                    }
                });
            } else {
                // Revert selection if cancelled
                $(this).val($(this).data('previous-value'));
            }
        });
    });

    // Store initial value for reverting if needed
    $('.status-select').each(function() {
        $(this).data('previous-value', $(this).val());
    });
});
</script>