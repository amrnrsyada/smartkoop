<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['message' => 'Invalid request']);
    exit;
}

if (!isset($_SESSION['email'])) {
    echo json_encode(['message' => 'User not logged in']);
    exit;
}

$email = $_SESSION['email'];

// Ambil user info dan wallet_balance
$userQuery = mysqli_query($conn, "SELECT name, wallet_balance FROM users WHERE email = '$email'");
if (!$userQuery || mysqli_num_rows($userQuery) === 0) {
    echo json_encode(['message' => 'User not found']);
    exit;
}
$user = mysqli_fetch_assoc($userQuery);

// Ambil data POST (json)
$data = json_decode(file_get_contents('php://input'), true);
$cart = $data['cart'] ?? [];
$totalAmount = $data['amount'] ?? 0;
$payStatus = $data['payStatus'] ?? 'Unpaid';

if (empty($cart)) {
    echo json_encode(['message' => 'Cart is empty']);
    exit;
}

if ($user['wallet_balance'] < $totalAmount) {
    echo json_encode(['message' => 'Insufficient wallet balance']);
    exit;
}

// Mulai transaction
mysqli_begin_transaction($conn);

try {
    // Cek dan deduct stock tiap item
    foreach ($cart as $item) {
        $itemID = (int)$item['id'];         // Pastikan id sesuai dengan cart key (id or itemID)
        $qty = (int)$item['quantity'];

        // Lock row item
        $stockResult = mysqli_query($conn, "SELECT availableStock FROM items WHERE itemID = $itemID FOR UPDATE");
        if (!$stockResult || mysqli_num_rows($stockResult) === 0) {
            throw new Exception("Item ID $itemID not found.");
        }
        $product = mysqli_fetch_assoc($stockResult);
        $currentStock = (int)$product['availableStock'];

        if ($currentStock < $qty) {
            throw new Exception("Insufficient stock for item ID $itemID.");
        }

        // Update stock
        $newStock = $currentStock - $qty;
        $updateStock = mysqli_query($conn, "UPDATE items SET availableStock = $newStock WHERE itemID = $itemID");
        if (!$updateStock) {
            throw new Exception("Failed to update stock for item ID $itemID.");
        }
    }

    // Generate orderID dan orderDetails JSON
    $orderID = uniqid('ORD');
    $orderDetails = json_encode($cart);
    $orderDate = date('Y-m-d H:i:s');

    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (orderID, email, name, orderDetails, totalAmount, orderStatus, payStatus, orderDate) VALUES (?, ?, ?, ?, ?, 'Completed', ?, ?)");
    $stmt->bind_param("ssssdss", $orderID, $email, $user['name'], $orderDetails, $totalAmount, $payStatus, $orderDate);
    if (!$stmt->execute()) {
        throw new Exception("Error inserting order.");
    }

    // Update wallet balance
    $newBalance = $user['wallet_balance'] - $totalAmount;
    $updateWallet = mysqli_query($conn, "UPDATE users SET wallet_balance = $newBalance WHERE email = '$email'");
    if (!$updateWallet) {
        throw new Exception("Failed to update wallet balance.");
    }

    // Commit semua perubahan
    mysqli_commit($conn);

    echo json_encode(['message' => 'Checkout successful!']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['message' => $e->getMessage()]);
    exit;
}
