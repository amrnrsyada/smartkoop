<?php
session_start();
include '../config.php';

if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

// 1. JSON input dari AJAX (add to cart)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER["CONTENT_TYPE"] ?? '', 'application/json') !== false) {
  $data = json_decode(file_get_contents("php://input"), true);
  $itemId = $data['item_id'];
  $quantity = $data['quantity'];

  // Elak input jahat
  $itemId = intval($itemId);
  $quantity = intval($quantity);
  if ($itemId <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item or quantity']);
    exit;
  }

  // Dapatkan item dari DB
  $result = mysqli_query($conn, "SELECT * FROM items WHERE itemID = '$itemId'");
  $item = mysqli_fetch_assoc($result);

  if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit;
  }

  // Semak dan update quantity dalam session
  $exists = false;
  foreach ($_SESSION['cart'] as &$cartItem) {
    if ($cartItem['id'] == $itemId) {
      $cartItem['quantity'] += $quantity;
      $exists = true;
      break;
    }
  }
  unset($cartItem);

  // Jika belum ada dalam cart, tambah baru
  if (!$exists) {
    $_SESSION['cart'][] = [
      'id' => $item['itemID'],
      'name' => $item['itemName'],
      'price' => $item['sellingPrice'],
      'quantity' => $quantity,
      'image' => $item['image']
    ];
  }

  // Kira jumlah item dalam cart untuk update badge
  $cartCount = 0;
  foreach ($_SESSION['cart'] as $cartItem) {
    $cartCount += $cartItem['quantity'];
  }

  echo json_encode([
    'success' => true,
    'message' => 'Item added to cart',
    'cart_count' => $cartCount
  ]);
  exit;
}

// 2. Standard form POST (from cart.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $itemId = $_POST['item_id'] ?? '';

  foreach ($_SESSION['cart'] as $index => $item) {
    if ($item['id'] == $itemId) {
      if ($action === 'update_quantity') {
        $change = $_POST['change'] ?? '';
        if ($change === 'increase') {
          $_SESSION['cart'][$index]['quantity'] += 1;
        } elseif ($change === 'decrease' && $_SESSION['cart'][$index]['quantity'] > 1) {
          $_SESSION['cart'][$index]['quantity'] -= 1;
        }
      } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$index]);
      }
      break;
    }
  }

  // Reset array keys
  $_SESSION['cart'] = array_values($_SESSION['cart']);
  header("Location: cart.php");
  exit;
}

// Invalid Request
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
