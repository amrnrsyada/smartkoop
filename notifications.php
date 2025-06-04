<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'staff') {
    header("Location: index.php");
    exit();
}

// Mark notifications as read when viewed
$conn->query("UPDATE notifications SET is_read = TRUE WHERE email = '{$_SESSION['email']}'");

$notifications = $conn->query("SELECT * FROM notifications WHERE email = '{$_SESSION['email']}' ORDER BY created_at DESC");
include('header.php');
?>

<div class="container-fluid py-5">
    <div class="card">
        <div class="card-body">
            <h3 class="fw-bold text-dark mb-4">
                <i class="fas fa-bell me-2"></i> Notifications
            </h3>
            
            <div class="list-group">
                <?php if ($notifications->num_rows > 0): ?>
                <?php while($note = $notifications->fetch_assoc()): ?>
                <div class="list-group-item <?= $note['is_read'] ? 'bg-light' : 'bg-primary bg-opacity-10 border-start border-primary border-5' ?> mb-2 rounded">
                    <div class="d-flex w-100 justify-content-between">
                        <p class="mb-1 <?= $note['is_read'] ? 'text-dark' : 'fw-semibold' ?>">
                            <?= htmlspecialchars($note['message']) ?>
                        </p>
                        <small class="<?= $note['is_read'] ? 'text-muted' : 'text-primary' ?>">
                            <?= date("d M Y, H:i", strtotime($note['created_at'])) ?>
                        </small>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <div class="list-group-item bg-light">
                    <p class="mb-1 text-muted">No notifications found</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>