<?php
session_start();
include 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$orderID = $conn->real_escape_string($_POST['orderID']);
$amount = floatval($_POST['amount']);
$userEmail = $_SESSION['email'];

$conn->begin_transaction();

try {
    // 1. Check if order can be cancelled
    $stmt = $conn->prepare("SELECT orderStatus FROM orders WHERE orderID = ? AND email = ?");
    $stmt->bind_param("ss", $orderID, $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Order not found or not owned by user");
    }
    
    $order = $result->fetch_assoc();
    if ($order['orderStatus'] !== 'Preparing' && $order['orderStatus'] !== 'Completed') {
        throw new Exception("Order cannot be cancelled at this stage");
    }

    // 2. Update order status
    $newStatus = 'Cancelled';
    $stmt = $conn->prepare("UPDATE orders SET orderStatus = ? WHERE orderID = ?");
    $stmt->bind_param("ss", $newStatus, $orderID);
    $stmt->execute();

    // 3. Refund to wallet
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE email = ?");
    $stmt->bind_param("ds", $amount, $userEmail);
    $stmt->execute();

    // 4. Create notification for staff
    $staffMessage = "Order #$orderID has been cancelled by customer. Refund of RM" . number_format($amount, 2) . " processed.";
    $stmt = $conn->prepare("INSERT INTO notifications (email, message) VALUES (?, ?)");
    
    // Notify all staff (in a real app, you might have a staff list)
    $staffEmails = ['petakomumpsa@gmail.com']; // Replace with actual staff emails
    foreach ($staffEmails as $staffEmail) {
        $stmt->bind_param("ss", $staffEmail, $staffMessage);
        $stmt->execute();
    }

    // 5. Create notification for customer
    $customerMessage = "Your order #$orderID has been cancelled. RM" . number_format($amount, 2) . " has been refunded to your wallet.";
    $stmt->bind_param("ss", $userEmail, $customerMessage);
    $stmt->execute();

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled successfully. Amount refunded to your wallet.'
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>