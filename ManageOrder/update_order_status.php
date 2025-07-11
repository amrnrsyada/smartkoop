<?php
session_start();
include '../config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderID = $_POST['orderID'] ?? '';
    $newStatus = $_POST['newStatus'] ?? '';
    
    if (empty($orderID) || empty($newStatus)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }
    
    // Validate status transition
    $stmt = $conn->prepare("SELECT orderStatus FROM orders WHERE orderID = ?");
    $stmt->bind_param("s", $orderID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    $currentStatus = $result->fetch_assoc()['orderStatus'];
    
    // Validate status transition logic
    $validTransitions = [
        'Preparing' => ['Ready To Pickup'],
        'Ready To Pickup' => ['Completed'],
        'Cancel Requested' => ['Cancelled']
    ];
    
    if (isset($validTransitions[$currentStatus])) {
        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status transition']);
            exit();
        }
    }
    
    // Update status
    $updateStmt = $conn->prepare("UPDATE orders SET orderStatus = ? WHERE orderID = ?");
    $updateStmt->bind_param("ss", $newStatus, $orderID);
    
    if ($updateStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
    }
    
    $updateStmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}