<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderID = $_POST['orderID'];

    $stmt = $conn->prepare("UPDATE orders SET orderStatus = 'Completed' WHERE orderID = ?");
    $stmt->bind_param("s", $orderID);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Order marked as completed.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order.']);
    }

    $stmt->close();
    $conn->close();
}
?>
