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
                    <i class="fas fa-cash-register"></i> In-Store Payments
                </h3>
            </div>

            <!-- Items Table -->
            <div class="table-responsive">
                <table id="instoreTable" class="table table-striped table-hover" style="width:100%">
                    <thead class="table-light text-center">
                        <tr>
                            <th>#</th>
                            <th>Order ID</th>
                            <th>Total (RM)</th>
                            <th>Paid (RM)</th>
                            <th>Balance (RM)</th>
                            <th>Payment Method</th>
                            <th>Transaction Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM instore ORDER BY transactionDate DESC");
                        if ($result && $result->num_rows > 0):
                            $i = 1;
                            while ($row = $result->fetch_assoc()):
                        ?>
                        <tr class="text-center">
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['orderID']) ?></td>
                            <td><?= number_format($row['totalAmount'], 2) ?></td>
                            <td><?= number_format($row['amountPaid'], 2) ?></td>
                            <td><?= number_format($row['balance'], 2) ?></td>
                            <td><span class="badge bg-success"><?= ucfirst(htmlspecialchars($row['payMethod'])) ?></span></td>
                            <td><?= date('d M Y, H:i', strtotime($row['transactionDate'])) ?></td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <em>No in-store transactions found.</em>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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

$(document).ready(function() {
    // DataTable Initialization
    $('#instoreTable').DataTable({
        "responsive": true,
        "autoWidth": false
    });
});

</script>


