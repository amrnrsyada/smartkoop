<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderID = $_POST['orderID'] ?? '';
    $email = $_POST['email'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);

    if (empty($orderID) || empty($email) || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. Refund to wallet
        $updateWallet = $conn->prepare("UPDATE users SET wallet = wallet + ? WHERE email = ?");
        $updateWallet->bind_param("ds", $amount, $email);
        $updateWallet->execute();

        if ($updateWallet->affected_rows === 0) {
            throw new Exception("Failed to refund wallet.");
        }

        // 2. Update order status
        $updateOrder = $conn->prepare("UPDATE orders SET orderStatus = 'Cancelled' WHERE orderID = ?");
        $updateOrder->bind_param("s", $orderID);
        $updateOrder->execute();

        if ($updateOrder->affected_rows === 0) {
            throw new Exception("Failed to update order status.");
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Order cancelled and RM " . number_format($amount, 2) . " refunded."]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
