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
        border: none;
    }
    .card-header {
        border-radius: 12px 12px 0 0 !important;
    }
    #card-element {
        padding: 12px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        background: white;
    }
    .btn-pay {
        padding: 10px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .success-icon {
        font-size: 4rem;
        color: #28a745;
        margin-bottom: 1rem;
    }
    .is-invalid {
        border-color: #dc3545 !important;
    }
    .invalid-feedback {
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.25rem;
    }
</style>

<div class="container topup-container">
    <div class="card">
        <div class="card-header bg-primary text-white text-center py-3">
            <h4 class="mb-0"><i class="fas fa-wallet me-2"></i>Top Up Wallet</h4>
        </div>
        <div class="card-body p-4">
            <form id="payment-form">
                <div class="mb-4">
                    <label for="amount" class="form-label fw-semibold">Enter Amount (RM)</label>
                    <div class="input-group">
                        <span class="input-group-text">RM</span>
                        <input type="text" id="amount" class="form-control py-2" 
                               placeholder="e.g. 10.00" required
                               pattern="^\d+(\.\d{1,2})?$"
                               oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')">
                    </div>
                    <small class="text-muted">Minimum top-up amount: RM 2.00</small>
                    <div id="amount-error" class="invalid-feedback"></div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-semibold mb-2">Payment Details</label>
                    <div id="card-element" class="mb-3"></div>
                    <div id="card-errors" role="alert" class="text-danger small"></div>
                </div>
                
                <div class="d-grid gap-2">
                    <button id="payBtn" class="btn btn-success btn-pay">
                        <i class="fas fa-credit-card me-2"></i>Proceed Payment
                    </button>
                    <a href="makeOrder.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Menu
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="topupSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-5">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="mb-3">Top Up Successful!</h3>
                <p class="mb-4">Your wallet balance has been updated.</p>
                <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">
                    Back to Order
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-5">
                <div class="text-danger mb-3" style="font-size: 4rem;">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h3 class="mb-3" id="errorTitle">Payment Failed</h3>
                <p class="mb-4" id="errorMessage"></p>
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                    Try Again
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Stripe
    const stripe = Stripe('pk_test_51QgmOsDnpRFQhcjq7xeiSo68NUPkaYwnTyrIsb0TrdJ6ngXJ8BBEs0ysyvXyr5vkE5zAenzEL1SFT4nQMFwbwspc00u9hueAY0');
    const elements = stripe.elements();
    
    // Custom styling for card element
    const style = {
        base: {
            color: '#32325d',
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
                color: '#aab7c4'
            }
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };
    
    const card = elements.create('card', { style: style });
    card.mount('#card-element');
    
    // Handle real-time validation errors
    card.addEventListener('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
    
    // Validate amount input
    function validateAmount(amountStr) {
        const amountInput = document.getElementById('amount');
        const errorDisplay = document.getElementById('amount-error');
        
        // Check if input matches the required format (digits with optional 1-2 decimal places)
        if (!/^\d+(\.\d{1,2})?$/.test(amountStr)) {
            amountInput.classList.add('is-invalid');
            errorDisplay.textContent = 'Please enter a valid amount with max 2 decimal places';
            return false;
        }
        
        const amount = parseFloat(amountStr);
        if (amount < 2) {
            amountInput.classList.add('is-invalid');
            errorDisplay.textContent = 'Minimum top-up amount is RM 2.00';
            return false;
        }
        
        amountInput.classList.remove('is-invalid');
        errorDisplay.textContent = '';
        return true;
    }
    
    // Handle form submission
    const form = document.getElementById('payment-form');
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        
        const amountStr = document.getElementById('amount').value;
        
        // Validate amount format and value
        if (!validateAmount(amountStr)) {
            return;
        }
        
        const amount = parseFloat(amountStr);
        const payBtn = document.getElementById('payBtn');
        payBtn.disabled = true;
        payBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
        
        try {
            const { paymentMethod, error } = await stripe.createPaymentMethod({
                type: 'card',
                card: card
            });
            
            if (error) {
                throw error;
            }
            
            const response = await fetch('topup_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    amount: amount.toFixed(2), // Ensure exactly 2 decimal places
                    payment_method_id: paymentMethod.id
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                const successModal = new bootstrap.Modal(document.getElementById('topupSuccessModal'));
                successModal.show();
                
                // Reset form on success
                form.reset();
                card.clear();
            } else {
                throw new Error(result.error || 'Payment failed');
            }
        } catch (error) {
            document.getElementById('errorMessage').textContent = error.message;
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
        } finally {
            payBtn.disabled = false;
            payBtn.innerHTML = '<i class="fas fa-credit-card me-2"></i>Pay Now';
        }
    });
    
    // Validate amount on input change
    document.getElementById('amount').addEventListener('input', function() {
        validateAmount(this.value);
    });
    
    // Close success modal and redirect if needed
    document.getElementById('topupSuccessModal').addEventListener('hidden.bs.modal', function () {
        window.location.href = 'makeOrder.php';
    });
</script>

<?php include('footer.php'); ?>