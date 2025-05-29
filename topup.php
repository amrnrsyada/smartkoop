<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'customer') {
    header("Location: index.php");
    exit();
}

$success = isset($_GET['success']) && $_GET['success'] == 1;
include('header.php');
?>

<script src="https://js.stripe.com/v3/"></script>

<style>
    body {
        background-color: #f8f9fa;
    }
    .topup-container {
        max-width: 500px;
        margin: 60px auto;
    }
    .card {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-radius: 12px;
    }
    .modal {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999;
    }
    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        max-width: 400px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    .modal-content h2 {
        color: green;
    }
    .close-btn {
        margin-top: 20px;
        background-color: #333;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
    }
</style>

<div class="container topup-container">
    <div class="card">
        <div class="card-header bg-primary text-white text-center">
            <h4 class="mb-0">Top Up Wallet</h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="amount" class="form-label">Enter Top-up Amount (RM):</label>
                <input type="number" step="0.01" min="1" id="amount" class="form-control" placeholder="e.g. 10.00" required>
            </div>
            <div id="card-element" class="form-control mb-3"></div>
            <button id="payBtn" class="btn btn-success w-100 mb-3">Proceed to Payment</button>
            <a href="makeOrder.php" class="btn btn-danger me-2 w-100">Back to Menu</a>
        </div>
    </div>
</div>

<div class="modal" id="topupSuccessModal" style="display: none;">
    <div class="modal-content">
        <h2>Topup Successful!</h2>
        <p>Your wallet has been updated.</p>
        <button class="close-btn" onclick="document.getElementById('topupSuccessModal').style.display='none'">Close</button>
    </div>
</div>

<script>
    const stripe = Stripe('pk_test_51QgmOsDnpRFQhcjq7xeiSo68NUPkaYwnTyrIsb0TrdJ6ngXJ8BBEs0ysyvXyr5vkE5zAenzEL1SFT4nQMFwbwspc00u9hueAY0'); //Stripe publishable key
    const elements = stripe.elements();
    const cardElement = elements.create('card');
    cardElement.mount('#card-element');

    document.getElementById('payBtn').addEventListener('click', async () => {
        const amount = parseFloat(document.getElementById('amount').value);
        if (!amount || amount < 1) {
            alert("Please enter a valid amount.");
            return;
        }

        const {paymentMethod, error} = await stripe.createPaymentMethod({
            type: 'card',
            card: cardElement
        });

        if (error) {
            alert(error.message);
            return;
        }

        const res = await fetch('topup_process.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                amount: amount,
                payment_method_id: paymentMethod.id
            })
        });

        const result = await res.json();
        if (result.success) {
            document.getElementById('topupSuccessModal').style.display = 'flex';
        } else {
            alert(result.error);
        }
    });
</script>

<?php include('footer.php'); ?>
