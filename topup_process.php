<?php
session_start();
include 'config.php';
require 'vendor/autoload.php';

if (!isset($_SESSION['email'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

$email = $_SESSION['email'];
$input = json_decode(file_get_contents('php://input'), true);
$amount = $input['amount'] ?? null;
$payment_method_id = $input['payment_method_id'] ?? null;

if (!$amount || !$payment_method_id) {
    echo json_encode(['error' => 'Invalid amount or payment method']);
    exit();
}

\Stripe\Stripe::setApiKey('sk_test_51QgmOsDnpRFQhcjqjxLP8ADS47UkDQ2tDPVRqytgmI2Eok3Vth0Ntv0WcnzsSLn9iGZjeovlxPTsZlpv5gtc76rF00JrUpfsz6'); //Secret Key

$amount_cents = intval(round($amount * 100)); // RM to sen

try {
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount_cents,
        'currency' => 'myr',
        'payment_method' => $payment_method_id,
        'confirmation_method' => 'manual',
        'confirm' => true,
        'return_url' => 'http://localhost/petakommart/topup.php?success=1',
    ]);

    if ($paymentIntent->status == 'succeeded') {
        // 1. Update wallet_balance user
        $stmt1 = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE email = ?");
        $stmt1->bind_param("ds", $amount, $email);
        $stmt1->execute();

        // 2. Insert rekod payment ke table payment
        $status = 'Completed';
        $stmt2 = $conn->prepare("INSERT INTO payment (email, amount, status) VALUES (?, ?, ?)");
        $stmt2->bind_param("sds", $email, $amount, $status);
        $stmt2->execute();

        echo json_encode(['success' => true]);
        exit();
    } else {
        echo json_encode(['error' => 'Payment failed: ' . $paymentIntent->status]);
        exit();
    }

} catch (\Stripe\Exception\ApiErrorException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}
