<?php
session_start();
include 'config.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'customer') {
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

// Fetch topups
$stmtTopup = $conn->prepare("SELECT amount, 'Topup' AS type, status, created_at FROM payment WHERE email = ? ORDER BY created_at DESC");
$stmtTopup->bind_param("s", $email);
$stmtTopup->execute();
$resultTopup = $stmtTopup->get_result();

// Fetch deductions (orders paid)
$stmtDeduct = $conn->prepare("SELECT totalAmount AS amount, 'Deducted' AS type, orderStatus AS status, orderDate AS created_at FROM orders WHERE email = ? AND payStatus = 'Paid' ORDER BY orderDate DESC");
$stmtDeduct->bind_param("s", $email);
$stmtDeduct->execute();
$resultDeduct = $stmtDeduct->get_result();

// Combine both results into one array
$transactions = [];
while ($row = $resultTopup->fetch_assoc()) {
    $transactions[] = $row;
}
while ($row = $resultDeduct->fetch_assoc()) {
    $row['amount'] = '-' . $row['amount']; // make deducted amount negative
    $transactions[] = $row;
}

// Sort combined array by created_at descending
usort($transactions, function ($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

include('header.php');
?>

<div class="container-fluid py-5">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h3 class="fw-bold text-dark d-flex align-items-center gap-2"><i class="fas fa-wallet me-2"></i>eWallet History</h3>
            </div>
            <div class="text-start fs-5 fw-semibold text-success mb-3">Wallet Balance: RM <?= number_format($walletBalance, 2) ?></div>

            <div class="table-responsive">
                <table id="walletTable" class="table table-striped table-hover" style="width:100%">
                    <thead class="table-light text-center">
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Amount (RM)</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions)): ?>
                            <?php $i = 1; foreach($transactions as $row): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['type'] === 'Topup' ? 'primary' : 'danger' ?>">
                                            <?= $row['type'] ?>
                                        </span>
                                    </td>
                                    <td class="<?= $row['type'] === 'Deducted' ? 'text-dark' : 'text-dark' ?>">
                                        RM <?= number_format(abs($row['amount']), 2) ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['status']) ?></td>
                                    <td><?= date("d M Y, H:i", strtotime($row['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No transactions found.</td>
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
    $('#walletTable').DataTable({
    "order": [] // No default sorting
});

});
</script>
