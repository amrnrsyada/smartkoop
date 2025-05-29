<?php  
include 'config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

// Validate request
if (
    !$data || 
    empty($data['items']) || 
    !isset($data['payMethod']) || 
    !isset($data['totalAmount']) || 
    !isset($data['amountPaid'])
) {
    echo json_encode(['success' => false, 'message' => 'Incomplete data']);
    exit;
}

$items = $data['items'];
$payMethod = $conn->real_escape_string($data['payMethod']);
$totalAmount = floatval($data['totalAmount']);
$amountPaid = floatval($data['amountPaid']);
$balance = $amountPaid - $totalAmount;

if ($balance < 0) {
    echo json_encode(['success' => false, 'message' => 'Amount paid not enough']);
    exit;
}

// Group items by name
$groupedItems = [];
foreach ($items as $item) {
    $name = $item['itemName'];
    $price = floatval($item['sellingPrice']);

    if (isset($groupedItems[$name])) {
        $groupedItems[$name]['quantity'] += 1;
    } else {
        $groupedItems[$name] = [
            'name' => $name,
            'price' => $price,
            'quantity' => 1
        ];
    }
}

$orderID = uniqid('ORD');
$orderStatus = 'Completed';
$payStatus = ($payMethod === 'cash' || $payMethod === 'online') ? 'Paid' : 'Unpaid';
$orderDate = date('Y-m-d H:i:s');
$orderDetails = json_encode(array_values($groupedItems));

$conn->begin_transaction();

try {
    // Insert into orders
    $stmt = $conn->prepare("INSERT INTO orders (orderID, orderDetails, totalAmount, orderStatus, payStatus, orderDate) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssdsss', $orderID, $orderDetails, $totalAmount, $orderStatus, $payStatus, $orderDate);
    $stmt->execute();

    // Check & update stock
    foreach ($groupedItems as $item) {
        $stmtCheck = $conn->prepare("SELECT availableStock FROM items WHERE itemName = ?");
        $stmtCheck->bind_param('s', $item['name']);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Item not found: {$item['name']}");
        }

        $row = $result->fetch_assoc();
        if ($row['availableStock'] < $item['quantity']) {
            throw new Exception("Not enough stock for: {$item['name']}");
        }

        $stmtUpdate = $conn->prepare("UPDATE items SET availableStock = availableStock - ? WHERE itemName = ?");
        $stmtUpdate->bind_param('is', $item['quantity'], $item['name']);
        $stmtUpdate->execute();
    }

    // Insert into instore
    $stmt2 = $conn->prepare("INSERT INTO instore (orderID, totalAmount, amountPaid, balance, payMethod) VALUES (?, ?, ?, ?, ?)");
    $stmt2->bind_param('sddds', $orderID, $totalAmount, $amountPaid, $balance, $payMethod);
    $stmt2->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Checkout successful!']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Checkout failed: ' . $e->getMessage()]);
}
?>
