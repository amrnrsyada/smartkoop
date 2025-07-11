<?php 
session_start();
include '../config.php';  

// Prevent browser from caching the page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is logged in and has the correct role
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'customer') {
  header("Location: ../index.php");
  exit();
}

$email = mysqli_real_escape_string($conn, $_SESSION['email']);

// Get current cart quantities for each item
$cartQuantities = [];
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartQuantities[$item['id']] = $item['quantity'];
    }
}

include('../header.php');
?>

<style>
    .hover-effect {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-effect:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    }
    .quantity-btn {
      width: 30px;
      height: 30px;
      padding: 0;
      font-size: 18px;
      text-align: center;
      line-height: 1;
      border: 1px solid #ccc;
      background-color: #f8f9fa;
    }
    .quantity-input {
      width: 50px;
      text-align: center;
    }
    .quantity-btn:hover {
      background: #e9ecef;
    }
    .quantity-input.text-danger {
      color: #dc3545 !important;
      border-color: #dc3545 !important;
    }
    .stock-warning {
      font-size: 0.8rem;
      color: #dc3545;
      display: none;
    }
    .btn-disabled {
      opacity: 0.65;
      cursor: not-allowed;
    }
</style>

<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-4 gap-3">
    <h3 class="fw-bold text-dark d-flex align-items-center gap-2">
      <i class="fas fa-list"></i> Available Items
    </h3>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <form method="get">
        <select name="category" id="category" class="form-select form-select-sm" onchange="this.form.submit()" style="border: 2px solid black; min-width: 180px; min-height: 40px;">
          <option value="">All Categories</option>
          <?php
          $catResult = $conn->query("SELECT * FROM categories");
          while ($cat = $catResult->fetch_assoc()) {
            $selected = (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? "selected" : "";
            echo "<option value='{$cat['id']}' $selected>{$cat['name']}</option>";
          }
          ?>
        </select>
      </form>

      <form action="cart.php" method="get">
        <button type="submit" class="btn btn-success btn-sm position-relative" style="min-width: 40px; min-height: 40px;" title="Go to Cart">
          <i class="fas fa-shopping-cart"></i>
          <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?= array_sum($cartQuantities) ?? 0 ?>
          </span>
        </button>
      </form>
    </div>
  </div>

  <?php
  $categoryFilter = isset($_GET['category']) && $_GET['category'] != '' ? intval($_GET['category']) : '';
  $sql = "SELECT * FROM items";
  if ($categoryFilter != '') {
      $sql .= " WHERE category_id = $categoryFilter";
  }
  $result = $conn->query($sql);
  
  if ($result->num_rows > 0) {
    echo '<div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-4">';
    while($row = $result->fetch_assoc()) {
      $itemID = $row['itemID'];
      $availableStock = $row['availableStock'];
      $inCart = isset($cartQuantities[$itemID]) ? $cartQuantities[$itemID] : 0;
      $remainingStock = $availableStock - $inCart;
      $isOutOfStock = $remainingStock <= 0;
      
      echo '
      <div class="col">
        <div class="card shadow-sm hover-effect h-100 border-0">
          <img src="'.$row['image'].'" class="card-img-top object-fit-cover" style="height: 200px;" alt="'.$row['itemName'].'">
          <div class="card-body d-flex flex-column">
            <h6 class="card-title fw-semibold text-dark">'.$row['itemName'].'</h6>
            <p class="card-text text-success fw-bold">RM '.number_format($row['sellingPrice'], 2).'</p>
            <p class="text-muted small mb-2">Available: '.$remainingStock.'</p>
            
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="d-flex align-items-center">
                <button class="quantity-btn minus" onclick="updateQuantity(this, -1)" data-max="'.$remainingStock.'">-</button>
                <input type="number" class="quantity-input mx-2" value="1" min="1" max="'.$remainingStock.'" 
                       id="quantity-'.$row['itemID'].'" data-item-id="'.$row['itemID'].'"
                       onchange="validateQuantity(this, '.$remainingStock.')">
                <button class="quantity-btn plus" onclick="updateQuantity(this, 1)" data-max="'.$remainingStock.'">+</button>
              </div>
            </div>
            <p id="warning-'.$row['itemID'].'" class="stock-warning mb-2">Exceeds available stock!</p>
            
            <button class="btn btn-success mt-auto w-100 '.($isOutOfStock ? 'btn-disabled' : '').'" 
              onclick="addToCart('.$row['itemID'].', \''.$row['itemName'].'\', '.$row['sellingPrice'].', \''.$row['image'].'\', '.$remainingStock.')"
              '.($isOutOfStock ? 'disabled' : '').'>
              <i class="fas fa-cart-plus me-2"></i>'.($isOutOfStock ? 'Out of Stock' : 'Add to Cart').'
            </button>
          </div>
        </div>
      </div>';
    }
    echo '</div>';
  } else {
    echo '<div class="text-center text-muted py-5">
            <i class="fas fa-box-open fa-3x mb-3"></i>
            <p class="fs-5">No items available in this category.</p>
          </div>';
  }
  ?>
</div>

<?php include('../footer.php'); ?>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Initialize any needed elements
  });

  // Function to validate quantity when manually changed
  function validateQuantity(input, maxStock) {
    const quantity = parseInt(input.value);
    const warning = document.getElementById(`warning-${input.dataset.itemId}`);
    const addToCartBtn = input.closest('.card-body').querySelector('.btn-success');
    
    if (isNaN(quantity) || quantity < 1) {
      input.value = 1;
      warning.style.display = 'none';
      return;
    }
    
    if (quantity > maxStock) {
      input.value = maxStock;
      warning.style.display = 'block';
    } else {
      warning.style.display = 'none';
    }
    
    // Disable add to cart button if no stock
    if (maxStock <= 0) {
      addToCartBtn.disabled = true;
      addToCartBtn.classList.add('btn-disabled');
    }
  }

  // Function to update quantity with buttons
  function updateQuantity(button, change) {
    const input = button.parentElement.querySelector('.quantity-input');
    const maxStock = parseInt(button.getAttribute('data-max'));
    const warning = document.getElementById(`warning-${input.dataset.itemId}`);
    let newValue = parseInt(input.value) + change;
    
    // Validate and constrain the value
    if (isNaN(newValue)) newValue = 1;
    if (newValue < 1) newValue = 1;
    if (newValue > maxStock) newValue = maxStock;
    
    input.value = newValue;
    
    // Show/hide warning
    if (newValue >= maxStock) {
      input.classList.add('text-danger');
      input.classList.add('fw-bold');
    } else {
      input.classList.remove('text-danger');
      input.classList.remove('fw-bold');
      warning.style.display = 'none';
    }
  }

  // Add to cart with strict stock validation
  function addToCart(id, name, price, imagePath, remainingStock) {
    const quantityInput = document.getElementById('quantity-' + id);
    let quantity = parseInt(quantityInput.value);
    const warning = document.getElementById(`warning-${id}`);
    
    // Validate quantity is a positive number
    if (isNaN(quantity) || quantity < 1) {
      Swal.fire({
        icon: 'error',
        title: 'Invalid Quantity',
        text: 'Please enter a valid quantity (minimum 1)',
        confirmButtonColor: '#dc3545'
      });
      quantityInput.value = 1;
      return;
    }
    
    // Check if item is out of stock
    if (remainingStock <= 0) {
      Swal.fire({
        icon: 'error',
        title: 'Stock Unavailable',
        text: `${name} is currently out of stock!`,
        confirmButtonColor: '#dc3545'
      });
      return;
    }
    
    // Check if quantity exceeds available stock
    if (quantity > remainingStock) {
      warning.style.display = 'block';
      quantityInput.classList.add('text-danger');
      quantityInput.classList.add('fw-bold');
      
      Swal.fire({
        icon: 'error',
        title: 'Exceeds Available Stock',
        html: `You cannot add ${quantity} ${name}(s) to cart.<br>Only <strong>${remainingStock}</strong> available in stock.`,
        confirmButtonColor: '#dc3545'
      });
      quantityInput.value = remainingStock;
      return;
    }

    // If validation passes, proceed to add to cart
    fetch('update_cart.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        item_id: id,
        quantity: quantity,
        name: name,
        price: price,
        image: imagePath
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Added to Cart!',
          html: `<strong>${quantity} × ${name}</strong><br>Successfully added to your cart.`,
          timer: 1500,
          showConfirmButton: false
        });
        // Update cart count
        document.getElementById('cart-count').textContent = data.cart_count || 0;
        // Reload to update stock display
        setTimeout(() => window.location.reload(), 1600);
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Failed to Add',
          text: data.message || 'Could not add item to cart. Please try again.'
        });
      }
    })
    .catch(error => {
      console.error('Error:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'An error occurred while adding to cart.'
      });
    });
  }
</script>