<?php
session_start();
include 'config.php';

if (!isset($_POST['item_id'])) {
    die(json_encode(['error' => 'Invalid request']));
}

$itemId = intval($_POST['item_id']);
$stmt = $conn->prepare("SELECT availableStock FROM items WHERE itemID = ?");
$stmt->bind_param("i", $itemId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['availableStock' => $row['availableStock']]);
} else {
    echo json_encode(['error' => 'Item not found']);
}
?>