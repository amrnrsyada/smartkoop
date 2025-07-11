<?php
session_start();
include '../config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (isset($_POST['orderID'])) {
    $orderID = $_POST['orderID'];
    
    $stmt = $conn->prepare("UPDATE orders SET orderStatus = 'Ready To Pickup' WHERE orderID = ?");
    $stmt->bind_param("s", $orderID);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Order marked as ready for pickup']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}