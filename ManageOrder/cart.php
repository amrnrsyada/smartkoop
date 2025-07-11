<?php
session_start();
include 'config.php';

// Prevent browser from caching the page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'customer') {
  header("Location: index.php");
  exit();
}

$email = $_SESSION['email'];
$sql = mysqli_query($conn, "SELECT wallet_balance FROM users WHERE email = '$email'");
$row = mysqli_fetch_assoc($sql);
$wallet_balance = $row['wallet_balance'];
$cart = $_SESSION['cart'] ?? [];

// Get available stock for all items in cart
$item_stocks = [];
if (!empty($cart)) {
    $item_ids = array_map(function($item) { return $item['id']; }, $cart);
    $ids_str = implode(",", $item_ids);
    $stock_query = mysqli_query($conn, "SELECT itemID, availableStock FROM items WHERE itemID IN ($ids_str)");
    while ($stock_row = mysqli_fetch_assoc($stock_query)) {
        $item_stocks[$stock_row['itemID']] = $stock_row['availableStock'];
    }
}

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
          <?php if (count($cart) > 0): ?>
            <?php $total = 0; ?>
            <?php foreach ($cart as $item): 
              $subtotal = $item['price'] * $item['quantity'];
              $total += $subtotal;
              $max_stock = $item_stocks[$item['id']] ?? 0;
            ?>
            <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-3">
             
                <img src="<?= htmlspecialchars($item['image']) ?>" width="80" height="80" class="rounded" style="object-fit: cover;">
                <div>
                  <h6 class="mb-1 fw-semibold"><?= htmlspecialchars($item['name']) ?></h6>
                  <form method="post" action="update_cart.php" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="action" value="update_quantity">
                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                    <input type="hidden" name="max_stock" value="<?= $max_stock ?>">
                    <button type="submit" name="change" value="decrease" class="btn btn-sm btn-outline-primary">-</button>
                    <span><?= $item['quantity'] ?></span>
                    <button type="submit" name="change" value="increase" class="btn btn-sm btn-outline-primary <?= $item['quantity'] >= $max_stock ? 'disabled' : '' ?>" 
                      <?= $item['quantity'] >= $max_stock ? 'disabled' : '' ?>>
                      +
                    </button>
                  </form>
                  <?php if ($item['quantity'] >= $max_stock): ?>
                    <small class="text-danger d-block mt-1">Max quantity reached</small>
                  <?php endif; ?>
                </div>
              
              <form method="post" action="update_cart.php" class="remove-form">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                <br><button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
              </form>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted">Your cart is empty.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Receipt Section -->
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Receipt</h5>
          <?php if (count($cart) > 0): ?>
            <table class="table table-bordered align-middle">
              <thead class="table-primary">
                <tr>
                  <th>Item</th>
                  <th>Qty</th>
                  <th>Price</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($cart as $item): ?>
                <tr>
                  <td><?= htmlspecialchars($item['name']) ?></td>
                  <td><?= $item['quantity'] ?></td>
                  <td>RM <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <p class="text-end fw-bold fs-5">Total: RM <?= number_format($total, 2) ?></p>
            <p class="text-end">Wallet Balance: RM <span class="fw-semibold"><?= number_format($wallet_balance, 2) ?></span></p>
            <div class="d-grid mt-4">
              <button id="checkoutBtn" class="btn btn-success btn-lg">Checkout</button>
            </div>

          <?php else: ?>
            <p class="text-muted">No items in cart to checkout.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php'); ?>

<script>
  window.addEventListener('pageshow', function(event) {
    if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
        window.location.reload();
    }
  });

  // Add confirmation for remove item
  document.querySelectorAll('.remove-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      const itemName = this.closest('.border').querySelector('h6').textContent;
      Swal.fire({
        title: 'Remove Item',
        html: `Are you sure you want to remove <strong>${itemName}</strong> from your cart?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, remove it',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          this.submit();
        }
      });
    });
  });

  document.getElementById('checkoutBtn')?.addEventListener('click', function () {
    const cart = <?= json_encode($cart) ?>;
    const amount = <?= json_encode($total) ?>;

    if (cart.length === 0) {
      Swal.fire("Cart is empty!", "", "info");
      return;
    }

    Swal.fire({
      title: 'Confirm Checkout',
      text: `You are about to pay RM ${amount.toFixed(2)} from your wallet.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Confirm',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch('checkout.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            cart: cart,
            amount: amount,
            payStatus: 'Paid'
          })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Success',
              text: data.message
            }).then(() => {
              window.location.href = 'order_history_customer.php';
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Checkout Failed',
              text: data.message
            });
          }
        })
        .catch(error => {
          console.error(error);
          Swal.fire('Error', 'Something went wrong.', 'error');
        });
      }
    });
  });
</script>