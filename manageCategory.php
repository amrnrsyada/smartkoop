<?php
session_start();
include 'config.php'; 

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'staff') {
  header("Location: index.php");
  exit();
}

// Handle Add Category
if (isset($_POST['add_category'])) {
    $catName = trim($_POST['cat_name']);
    
    $checkStmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $checkStmt->bind_param("s", $catName);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows > 0) {
        $_SESSION['alert'] = ['type' => 'warning', 'message' => 'Category already exists!'];
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $catName);
        
        if ($stmt->execute()) {
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Category added successfully!'];
        } else {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error adding category!'];
        }
    }
    $checkStmt->close();
    header("Location: manageCategory.php");
    exit();
}

// Handle Delete Category
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    
    $check = $conn->prepare("SELECT COUNT(*) FROM items WHERE category_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();
    
    if ($count > 0) {
        $_SESSION['alert'] = ['type' => 'warning', 'message' => 'Cannot delete category - it is being used by items.'];
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Category deleted successfully!'];
    }
    header("Location: manageCategory.php");
    exit();
}

include('header.php');
?>

<style>
    body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
    .card { border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
    .table th { background-color: #f8f9fa; }
    .action-btn-group .btn { padding: 0.375rem 0.75rem; }
    .img-thumbnail { width: 80px; height: 80px; object-fit: cover; }
    .modal-header, .modal-footer { border: none; }
</style>

<div class="container py-5">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h3 class="fw-bold text-dark d-flex align-items-center gap-2"><i class="fas fa-tags"></i> Category Lists</h3>
            </div>

            <div class="row g-4">
                <!-- Add Category -->
                <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header fw-semibold">Add New Category</div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="mb-3">
                                        <input type="text" name="cat_name" class="form-control" placeholder="Enter category name" required>
                                        </div>
                                        <button type="submit" name="add_category" class="btn btn-success w-100">Add Category</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- List Categories -->
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header fw-semibold">Existing Categories</div>
                                <ul class="list-group list-group-flush">
                                <?php
                                $result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
                                while ($row = $result->fetch_assoc()):
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= htmlspecialchars($row['name']) ?></span>
                                    <form method="post" class="mb-0">
                                    <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                    <button type="button" class="btn btn-sm btn-danger delete-category-btn"><i class="fas fa-trash-alt"></i> Delete</button>
                                    </form>
                                </li>
                                <?php endwhile; ?>
                                
                                <?php if($result->num_rows === 0): ?>
                                <li class="list-group-item text-center text-muted">
                                    No categories found
                                </li>
                                <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
         

<?php include('footer.php'); ?>

<?php if (isset($_SESSION['alert'])): ?>
<script>
  Swal.fire({
    icon: '<?= $_SESSION['alert']['type'] ?>',
    title: '<?= $_SESSION['alert']['message'] ?>',
    showConfirmButton: false,
    timer: 2000
  });
</script>
<?php unset($_SESSION['alert']); endif; ?>

<!-- Move this script OUTSIDE the if block -->
<script>
  document.querySelectorAll('.delete-category-btn').forEach(button => {
    button.addEventListener('click', function () {
      const form = this.closest('form');

      Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete the category.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
</script>





