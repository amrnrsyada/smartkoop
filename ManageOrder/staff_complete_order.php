<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderID = $_POST['orderID'] ?? '';

    if (empty($orderID)) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE orders SET orderStatus = 'Completed' WHERE orderID = ?");
    $stmt->bind_param("s", $orderID);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Order marked as completed.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No order updated or already completed.']);
    }
}
?>
