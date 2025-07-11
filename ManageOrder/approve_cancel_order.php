<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderID = $_POST['orderID'];
    $amount = $_POST['amount'];
    $email = $_POST['email'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Refund to wallet
        $stmt1 = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE email = ?");
        $stmt1->bind_param("ds", $amount, $email);
        $stmt1->execute();

        // Update order status
        $stmt2 = $conn->prepare("UPDATE orders SET orderStatus = 'Cancelled' WHERE orderID = ?");
        $stmt2->bind_param("s", $orderID);
        $stmt2->execute();

        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Order cancelled and refunded.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed: ' . $e->getMessage()]);
    }
}
?>
