<?php
session_start();
include '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$email = $_SESSION['email'];
$orderID = $_POST['orderID'] ?? '';
$amount = floatval($_POST['amount'] ?? 0);

if (empty($orderID) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    exit;
}

// Check if the order exists and belongs to the user
$stmt = $conn->prepare("SELECT orderStatus FROM orders WHERE orderID = ? AND email = ?");
$stmt->bind_param("ss", $orderID, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    exit;
}

$row = $result->fetch_assoc();
$status = $row['orderStatus'];

if ($status !== 'Preparing' && $status !== 'Ready To Pickup') {
    echo json_encode(['success' => false, 'message' => 'Cannot cancel this order.']);
    exit;
}

// Update status to 'Cancel Requested'
$update = $conn->prepare("UPDATE orders SET orderStatus = 'Cancel Requested' WHERE orderID = ?");
$update->bind_param("s", $orderID);
if ($update->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cancellation request sent. Awaiting approval.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update order status.']);
}
?>
