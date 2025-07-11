<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['orderID'])) {
    die("No order ID provided.");
}

$orderID = $_GET['orderID'];

// Fetch order data
$stmt = $conn->prepare("SELECT * FROM orders WHERE orderID = ?");
$stmt->bind_param("s", $orderID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Order not found.");
}

$order = $result->fetch_assoc();
$orderItems = json_decode($order['orderDetails'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?= htmlspecialchars($order['orderID']) ?></title>
    <link rel="icon" href="https://umpsa.edu.my/themes/pana/favicon.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f9f9f9; font-family: 'Poppins', sans-serif; }
        .receipt-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .table th, .table td {
            vertical-align: middle;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="receipt-box my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold"><i class="fas fa-receipt"></i> Order Receipt</h4>
        <button class="btn btn-light no-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    </div>

    <div class="mb-4">
        <p><strong>Order ID:</strong> <?= htmlspecialchars($order['orderID']) ?></p>
        <p><strong>Customer:</strong> <?= htmlspecialchars($order['name']) ?> (<?= htmlspecialchars($order['email']) ?>)</p>
        <p><strong>Date:</strong> <?= date("d M Y, H:i", strtotime($order['orderDate'])) ?></p>
        <p><strong>Status:</strong> 
            <span class="badge 
                <?php
                switch ($order['orderStatus']) {
                    case 'Preparing': echo 'bg-warning text-dark'; break;
                    case 'Completed': echo 'bg-success'; break;
                    case 'Cancelled': echo 'bg-danger'; break;
                    case 'Cancel Requested': echo 'bg-info text-dark'; break;
                    default: echo 'bg-secondary';
                }
                ?>">
                <?= htmlspecialchars($order['orderStatus']) ?>
            </span>
        </p>
    </div>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Item</th>
                <th class="text-end">Quantity</th>
                <th class="text-end">Price (RM)</th>
                <th class="text-end">Subtotal (RM)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
            $total = 0;
            foreach ($orderItems as $item):
                $subtotal = $item['quantity'] * $item['price'];
                $total += $subtotal;
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td class="text-end"><?= intval($item['quantity']) ?></td>
                <td class="text-end"><?= number_format($item['price'], 2) ?></td>
                <td class="text-end"><?= number_format($subtotal, 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="4" class="text-end fw-bold">Total Amount (RM)</td>
                <td class="text-end fw-bold"><?= number_format($total, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="text-center mt-5">
        <p class="text-muted mb-0">Thank you for your purchase!</p>
    </div>
</div>

</body>
</html>
