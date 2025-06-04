<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$orderID = $conn->real_escape_string($_POST['orderID']);
$status = $conn->real_escape_string($_POST['status']);

$stmt = $conn->prepare("UPDATE orders SET orderStatus = ? WHERE orderID = ?");
$stmt->bind_param("ss", $status, $orderID);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order status updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}
?>