<?php
include 'config.php';

if (!isset($_GET['orderID'])) {
    echo "Order ID not provided.";
    exit;
}

$orderID = $_GET['orderID'];

$stmt = $conn->prepare("SELECT * FROM orders WHERE orderID = ?");
$stmt->bind_param("s", $orderID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Order not found.";
    exit;
}

$order = $result->fetch_assoc();
$items = json_decode($order['orderDetails'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Order Receipt - <?= htmlspecialchars($orderID) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f2f2f2; }
        .total { text-align: right; font-weight: bold; }
        .print-button {
            margin: 20px 0;
            text-align: center;
        }
        @media print {
            .print-button { display: none; }
        }

        .print-button button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }
        .print-button button:hover {
            background-color: #218838;
        }
        .print-button button:active {
            background-color: #1e7e34;
        }
    </style>
</head>
<body>
    <h1><i class="fas fa-receipt"></i> Order Receipt</h1>
    <p><strong>Order ID:</strong> <?= htmlspecialchars($orderID) ?></p>
    <p><strong>Date:</strong> <?= date("d M Y, H:i", strtotime($order['orderDate'])) ?></p>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th>Price (RM)</th>
                <th>Subtotal (RM)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (is_array($items)) {
                foreach ($items as $item): 
                    $subtotal = $item['price'] * $item['quantity'];
            ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= (int)$item['quantity'] ?></td>
                    <td><?= number_format($item['price'], 2) ?></td>
                    <td><?= number_format($subtotal, 2) ?></td>
                </tr>
            <?php endforeach; 
            } else { ?>
                <tr><td colspan="4">Invalid item data.</td></tr>
            <?php } ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="total">Total Amount</td>
                <td><?= number_format($order['totalAmount'], 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="print-button">
        <button onclick="window.print()">Print Receipt</button>
    </div>
</body>
</html>
