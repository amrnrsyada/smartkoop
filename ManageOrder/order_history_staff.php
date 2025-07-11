<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');
include '../config.php';

// Prevent browser from caching the page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'staff') {
  header("Location: ../index.php");
  exit();
}

$email = $_SESSION['email'];

// Get all orders
$stmt = $conn->prepare("SELECT * FROM orders ORDER BY orderDate DESC");
$stmt->execute();
$orders = $stmt->get_result();

// Get count of pending cancellation requests
$pendingCancel = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE orderStatus = 'Cancel Requested'")
                      ->fetch_assoc()['total'];

include('../header.php');
?>

<style>
    body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
    .card { border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
    .table th { background-color: #f8f9fa; }
    .table-danger { background-color: rgba(220, 53, 69, 0.1) !important; }
    .badge-cancel-requested { 
        background-color: #ffc107 !important; 
        color: #212529 !important; 
        font-weight: 600;
    }
    .alert-danger {
        border-left: 5px solid #dc3545;
    }
    .cancel-request-btn {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
</style>

<div class="container-fluid py-5">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h3 class="fw-bold text-dark d-flex align-items-center gap-2 mb-4">
                    <i class="fas fa-clipboard-list"></i> Order Lists
                </h3>
            </div>
            
            <!-- pending cancel alert -->
            <?php if ($pendingCancel > 0): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong><?= $pendingCancel ?> cancellation request<?= $pendingCancel > 1 ? 's' : '' ?> pending!</strong>
                </div>
            <?php endif; ?>

            <!-- Table order -->
            <div class="table-responsive">
                <table id="staffOrderTable" class="table table-striped table-hover" style="width:100%">
                    <thead class="table-light text-center">
                        <tr>
                            <th>#</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total (RM)</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($orders->num_rows > 0): ?>
                        <?php $i = 1; while ($row = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['orderID']) ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td style="text-align:left;">
                                <?php
                                $items = json_decode($row['orderDetails'], true);
                                if (is_array($items)) {
                                    foreach ($items as $item) {
                                        echo htmlspecialchars($item['name']) . " x " . intval($item['quantity']) . "<br>";
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
                                    case 'Ready To Pickup': echo 'bg-secondary'; break;
                                    case 'Completed': echo 'bg-success'; break;
                                    case 'Cancelled': echo 'bg-danger'; break;
                                    case 'Cancel Requested': echo 'bg-danger'; break;
                                    default: echo 'bg-secondary';
                                }
                                ?>">
                                <?= htmlspecialchars($row['orderStatus']) ?>
                            </span>
                            </td>
                           <td class="text-center">
                            <div class="dropdown">
                                <button class="border-0 bg-transparent p-1" type="button" id="dropdownMenuButton<?= $row['orderID'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton<?= $row['orderID'] ?>">
                                    <li>
                                        <a class="dropdown-item" href="receipt.php?orderID=<?= urlencode($row['orderID']) ?>" target="_blank">
                                            <i class="fas fa-receipt me-2"></i> View Receipt
                                        </a>
                                    </li>

                                    <?php if ($row['orderStatus'] === 'Cancel Requested'): ?>
                                        <li>
                                            <button class="dropdown-item approve-cancel-btn"
                                                    data-order-id="<?= $row['orderID'] ?>"
                                                    data-email="<?= $row['email'] ?>"
                                                    data-amount="<?= $row['totalAmount'] ?>"
                                                    data-name="<?= $row['name'] ?>">
                                                <i class="fas fa-times-circle me-2 text-danger"></i> Approve Cancel
                                            </button>
                                        </li>
                                    <?php elseif ($row['orderStatus'] === 'Preparing'): ?>
                                        <li>
                                            <button class="dropdown-item ready-btn"
                                                    data-order-id="<?= $row['orderID'] ?>">
                                                <i class="fas fa-box-open me-2 text-secondary"></i> Mark Ready
                                            </button>
                                        </li>
                                    <?php elseif ($row['orderStatus'] === 'Ready To Pickup'): ?>
                                        <li>
                                            <button class="dropdown-item complete-btn"
                                                    data-order-id="<?= $row['orderID'] ?>">
                                                <i class="fas fa-check-circle me-2 text-success"></i> Mark Complete
                                            </button>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </td>

                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">No orders found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>

<script>

    $(document).ready(function() {
    window.addEventListener('pageshow', function(event) {
    if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
        window.location.reload();
    }
    });

    // Handle Approve Cancel button
    $('.approve-cancel-btn').on('click', function() {
        const orderID = $(this).data('order-id');
        const amount = $(this).data('amount');
        const email = $(this).data('email');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Approve Cancellation?',
            text: `Refund RM${amount} to ${name}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'approve_cancel_order.php',
                    type: 'POST',
                    data: { orderID: orderID, amount: amount, email: email },
                    success: function(response) {
                        const res = JSON.parse(response);
                        if (res.success) {
                            Swal.fire('Approved', res.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'AJAX request failed.', 'error');
                    }
                });
            }
        });
    });
});
</script>

<script>

$(document).ready(function () {
    // Handle 'Mark Complete' button click
    $('.complete-btn').on('click', function () {
        const orderID = $(this).data('order-id');

        Swal.fire({
            title: 'Mark this order as completed?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, complete it',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'mark_order_complete.php',
                    type: 'POST',
                    data: { orderID: orderID },
                    success: function (response) {
                        const res = JSON.parse(response);
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error!', 'Failed to send request.', 'error');
                    }
                });
            }
        });
    });

    // Handle 'Mark Ready' button click
    $('.ready-btn').on('click', function() {
        const orderID = $(this).data('order-id');

        Swal.fire({
            title: 'Mark this order as ready for pickup?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, mark as ready',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'mark_order_ready.php',
                    type: 'POST',
                    data: { orderID: orderID },
                    success: function(response) {
                        const res = JSON.parse(response);
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Failed to send request.', 'error');
                    }
                });
            }
        });
    });

});

    $(document).ready(function() {
        // DataTable Initialization
        $('#staffOrderTable').DataTable({
            "responsive": true,
            "autoWidth": false
        });
    });
</script>

