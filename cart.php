<?php 
session_start();
include 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'customer') {
  header("Location: index.php");
  exit();
}

$email = $_SESSION['email'];
$sql = mysqli_query($conn, "SELECT wallet_balance FROM users WHERE email = '$email'");
$row = mysqli_fetch_assoc($sql);
$wallet_balance = $row['wallet_balance'];

include('header.php');
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark"><i class="fas fa-shopping-cart me-2"></i>My Cart</h2>
    <div>
      <a href="makeOrder.php" class="btn btn-danger me-2">Browse Menu</a>
      <a href="topup.php" class="btn btn-primary">Top Up</a>
    </div>
  </div>

  <div class="row g-4">
    <!-- Cart Items -->
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="vstack gap-3" id="cart-items">
            <!-- Items will be injected here -->
          </div>
        </div>
      </div>
    </div>

    <!-- Receipt Section -->
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Receipt</h5>
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-primary">
                <tr>
                  <th>Item</th>
                  <th>Qty</th>
                  <th>Price</th>
                </tr>
              </thead>
              <tbody id="cart-items-table"></tbody>
            </table>
          </div>
          <p class="text-end fw-bold fs-5 mt-3">Total: RM <span id="total-amount">0.00</span></p>
          <p class="text-end">Wallet Balance: RM <span class="fw-semibold" id="wallet-balance"><?= number_format($wallet_balance, 2) ?></span></p>
          <div class="d-grid mt-4">
            <button onclick="checkout()" class="btn btn-success btn-lg">Checkout</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center">
      <div class="modal-header">
        <h5 class="modal-title" id="checkoutLabel">Confirm Checkout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to proceed with the checkout?
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" onclick="proceedCheckout()">Confirm</button>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php'); ?>

<script>
let cart = JSON.parse(localStorage.getItem("cart")) || [];
let walletBalance = <?= $wallet_balance ?>;

function updateCart() {
  const itemsContainer = document.getElementById('cart-items');
  const tableBody = document.getElementById('cart-items-table');
  const totalElement = document.getElementById('total-amount');

  itemsContainer.innerHTML = '';
  tableBody.innerHTML = '';
  let total = 0;

  if (cart.length > 0) {
    cart.forEach(item => {
      const subtotal = item.price * item.quantity;
      total += subtotal;

      tableBody.innerHTML += `
        <tr>
          <td>${item.name}</td>
          <td>${item.quantity}</td>
          <td>RM ${subtotal.toFixed(2)}</td>
        </tr>`;

      itemsContainer.innerHTML += `
        <div class="d-flex justify-content-between align-items-center border rounded p-3">
          <div class="d-flex align-items-center gap-3">
            <img src="uploads/${item.image}" width="80" height="80" class="rounded" style="object-fit: cover;">
            <div>
              <h6 class="mb-1 fw-semibold">${item.name}</h6>
              <div class="d-flex gap-2 align-items-center">
                <button class="btn btn-sm btn-outline-primary" onclick="updateQuantity(${item.id}, 'decrease')">-</button>
                <span>${item.quantity}</span>
                <button class="btn btn-sm btn-outline-primary" onclick="updateQuantity(${item.id}, 'increase')">+</button>
              </div>
            </div>
          </div>
          <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.id})">Remove</button>
        </div>`;
    });
  } else {
    itemsContainer.innerHTML = `<p class="text-muted">Your cart is empty.</p>`;
  }

  totalElement.innerText = total.toFixed(2);
}

function updateQuantity(id, action) {
  cart = cart.map(item => {
    if (item.id === id) {
      item.quantity = action === 'increase' ? item.quantity + 1 : Math.max(1, item.quantity - 1);
    }
    return item;
  });
  localStorage.setItem("cart", JSON.stringify(cart));
  updateCart();
}

function removeFromCart(id) {
  cart = cart.filter(item => item.id !== id);
  localStorage.setItem("cart", JSON.stringify(cart));
  updateCart();
}

function showAlert(message, icon = 'info', redirect = false) {
  Swal.fire({
    title: message,
    icon: icon,
    confirmButtonColor: '#3085d6',
    confirmButtonText: 'OK'
  }).then(() => {
    if (redirect) {
      window.location.href = "makeOrder.php";
    }function proceedCheckout() {
  const totalAmount = parseFloat(document.getElementById('total-amount').innerText);
  const checkoutModal = bootstrap.Modal.getInstance(document.getElementById('checkoutModal'));
  checkoutModal.hide();

  if (totalAmount > walletBalance) {
    return showAlert("Insufficient wallet balance.", "warning");
  }

  fetch('checkout.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ cart, amount: totalAmount, payStatus: 'Paid' })
  })
  .then(res => res.json())
  .then(data => {
    localStorage.removeItem("cart");
    showAlert(data.message, "success", true); // redirect after success
  })
  .catch(() => showAlert("Checkout failed. Please try again.", "error"));
}
  });
}


function checkout() {
  if (!cart.length) return showAlert("Your cart is empty.");
  const modal = new bootstrap.Modal(document.getElementById('checkoutModal'));
  modal.show();
}

function proceedCheckout() {
  const totalAmount = parseFloat(document.getElementById('total-amount').innerText);
  const checkoutModal = bootstrap.Modal.getInstance(document.getElementById('checkoutModal'));
  checkoutModal.hide();

  if (totalAmount > walletBalance) {
    return showAlert("Insufficient wallet balance.", "warning");
  }

  fetch('checkout.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ cart, amount: totalAmount, payStatus: 'Paid' })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      localStorage.removeItem("cart");
      showAlert(data.message, "success", true); // redirect after success
    } else {
      // Show error with appropriate icon
      const icon = data.errorType === 'stock_insufficient' ? 'warning' : 'warning';
      showAlert(data.message, icon);
      
      // Optional: Update cart if stock changed
      if (data.errorType === 'stock_insufficient') {
        updateCartFromServer(); // You might want to implement this
      }
    }
  })
  .catch(() => showAlert("Insufficient stock.", "warning"));
}



updateCart();
</script>
