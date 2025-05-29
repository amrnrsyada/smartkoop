<?php include 'config.php';  
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'customer') {
  header("Location: index.php");
  exit();
}

$email = mysqli_real_escape_string($conn, $_SESSION['email']);

include('header.php');
?>

<style>
    .hover-effect {transition: transform 0.2s ease, box-shadow 0.2s ease;}
    .hover-effect:hover {transform: translateY(-5px);box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);}
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
        <button type="submit" class="btn btn-success btn-sm" style="min-width: 40px; min-height: 40px;" title="Go to Cart">
          <i class="fas fa-shopping-cart"></i>
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
      echo '
      <div class="col">
        <div class="card shadow-sm hover-effect h-100 border-0">
          <img src="'.$row['image'].'" class="card-img-top object-fit-cover" style="height: 200px;" alt="'.$row['itemName'].'">
          <div class="card-body d-flex flex-column">
            <h6 class="card-title fw-semibold text-dark">'.$row['itemName'].'</h6>
            <p class="card-text text-success fw-bold">RM '.number_format($row['sellingPrice'], 2).'</p>
            <p class="text-muted small mb-2">Available: '.$row['availableStock'].'</p>
            <button class="btn btn-success mt-auto w-100" 
              onclick="addToCart('.$row['itemID'].', \''.$row['itemName'].'\', '.$row['sellingPrice'].', \''.$row['image'].'\', '.$row['availableStock'].')">
              <i class="fas fa-cart-plus me-2"></i>Add to Cart
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

<?php include('footer.php'); ?>

<script>
  function addToCart(id, name, price, imagePath, stock) {
    if (stock <= 0) {
      Swal.fire({
        icon: 'error',
        title: 'Stock Unavailable',
        text: `${name} is out of stock!`,
        confirmButtonColor: '#dc3545'
      });
      return;
    }

    const imageName = imagePath.split('/').pop();
    let cart = JSON.parse(localStorage.getItem("cart")) || [];
    let found = cart.find(item => item.id === id);

    if (found) {
      found.quantity += 1;
    } else {
      cart.push({ id, name, price, quantity: 1, image: imageName });
    }

    localStorage.setItem("cart", JSON.stringify(cart));

    Swal.fire({
      icon: 'success',
      title: 'Item Added!',
      text: `${name} has been added to your cart.`,
      timer: 1500,
      showConfirmButton: false
    });
  }
</script>
