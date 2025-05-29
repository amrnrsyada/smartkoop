<?php   
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['email'];
$role = $_SESSION['role'];

// Get wallet balance
$stmtWallet = $conn->prepare("SELECT wallet_balance FROM users WHERE email = ?");
$stmtWallet->bind_param("s", $email);
$stmtWallet->execute();
$resultWallet = $stmtWallet->get_result();
$walletBalance = 0;
if ($resultWallet->num_rows > 0) {
    $rowWallet = $resultWallet->fetch_assoc();
    $walletBalance = $rowWallet['wallet_balance'];
}

// Fetch payment history
if ($role === 'staff') {
    $stmt = $conn->prepare("SELECT p.*, u.name FROM payment p JOIN users u ON p.email = u.email ORDER BY p.created_at DESC");
} else {
    $stmt = $conn->prepare("SELECT p.*, u.name FROM payment p JOIN users u ON p.email = u.email WHERE p.email = ? ORDER BY p.created_at DESC");
    $stmt->bind_param("s", $email);
}

$stmt->execute();
$payments = $stmt->get_result();

include('header.php');
?>

<div class="container-fluid py-5">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h3 class="fw-bold text-dark d-flex align-items-center gap-2"><i class="fas fa-wallet me-2"></i>Top-up History</h3>
            </div>
            <div class="text-start fs-5 fw-semibold text-success mb-3">Wallet Balance: RM <?= number_format($walletBalance, 2) ?></div>

            <div class="table-responsive">
                <table id="topupTable" class="table table-striped table-hover" style="width:100%">
                    <thead class="table-light text-center">
                        <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Amount (RM)</th>
                        <th>Status</th>
                        <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($payments->num_rows > 0): ?>
                        <?php $i = 1; while($row = $payments->fetch_assoc()): ?>
                            <tr class="text-left">
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td>RM <?= number_format($row['amount'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= $row['status'] === 'Success' ? 'success' : 'success' ?>">
                                <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td><?= date("d M Y, H:i", strtotime($row['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No top-up history found.</td>
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
  $(document).ready(function() {
    $('#topupTable').DataTable({
      "order": [[ 4, "desc" ]], // Sort by date descending
      "columnDefs": [
        { "orderable": false, "targets": 3 } // Make "Status" column not orderable
      ]
    });
  });
</script>


