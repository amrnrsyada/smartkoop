<?php
include '../config.php';

header('Content-Type: application/json');

if (!isset($_GET['barcode']) || empty($_GET['barcode'])) {
    echo json_encode(['success' => false, 'message' => 'Barcode not given']);
    exit;
}

$barcode = $conn->real_escape_string($_GET['barcode']);

$sql = "SELECT itemName, sellingPrice, availableStock FROM items WHERE barcode = '$barcode' LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $item = $result->fetch_assoc();
    echo json_encode(['success' => true, 'item' => $item]);
} else {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
}
?>
