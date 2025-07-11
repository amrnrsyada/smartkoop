<?php
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'customer') {
  echo json_encode(['count' => 0]);
  exit();
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$count = 0;

foreach ($cart as $item) {
  $count += $item['quantity'];
}

echo json_encode(['count' => $count]);
