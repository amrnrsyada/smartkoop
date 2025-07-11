<?php
session_start();
include '../config.php';

// Prevent browser from caching the page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

// ----- Handle Add Item -----
if (isset($_POST['add'])) {
    $itemName = trim($_POST['itemName']);
    $sellingPrice = $_POST['sellingPrice'];
    $costPrice = $_POST['costPrice'];
    $availableStock = $_POST['availableStock'];
    $expiryDate = $_POST['expiryDate'];
    $barcode = trim($_POST['barcode']);
    $category_id = $_POST['category_id'];
    $imagePath = '';

    // Check if item name or barcode already exists
    $check = $conn->prepare("SELECT * FROM items WHERE itemName = ? OR barcode = ?");
    $check->bind_param("ss", $itemName, $barcode);
    $check->execute();
    $checkResult = $check->get_result();
    if ($checkResult->num_rows > 0) {
        // If duplicate is found, use SweetAlert2 by storing the error in the session
        $_SESSION['swal'] = json_encode([
            'icon' => 'error',
            'title' => 'Duplicate Entry',
            'text' => 'Item name or Barcode already exists. Please use unique values.'
        ]);
        header("Location: manageItem.php");
        exit();
    }

    // Handle image upload if provided
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        $imagePath = $targetDir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);
    }

    $stmt = $conn->prepare("INSERT INTO items (itemName, sellingPrice, costPrice, availableStock, expiryDate, barcode, image, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sddisssi", $itemName, $sellingPrice, $costPrice, $availableStock, $expiryDate, $barcode, $imagePath, $category_id);
    $stmt->execute();

    $_SESSION['swal'] = json_encode([
        'icon' => 'success',
        'title' => 'Success',
        'text' => 'Item added successfully!'
    ]);
    header("Location: manageItem.php");
    exit();
}

// ----- Handle Update Item -----
if (isset($_POST['update'])) {
    $editID = $_POST['edit_id'];
    $itemName = trim($_POST['itemName']);
    $sellingPrice = $_POST['sellingPrice'];
    $costPrice = $_POST['costPrice'];
    $availableStock = $_POST['availableStock'];
    $expiryDate = $_POST['expiryDate'];
    $barcode = trim($_POST['barcode']);
    $category_id = $_POST['category_id'];
    $imagePath = '';

    // If new image uploaded, update it; otherwise retain current image
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        $imagePath = $targetDir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);
        $stmt = $conn->prepare("UPDATE items SET itemName=?, sellingPrice=?, costPrice=?, availableStock=?, expiryDate=?, barcode=?, image=?, category_id=? WHERE itemID=?");
        $stmt->bind_param("sddisssii", $itemName, $sellingPrice, $costPrice, $availableStock, $expiryDate, $barcode, $imagePath, $category_id, $editID);
    } else {
        $stmt = $conn->prepare("UPDATE items SET itemName=?, sellingPrice=?, costPrice=?, availableStock=?, expiryDate=?, barcode=?, category_id=? WHERE itemID=?");
        $stmt->bind_param("sddissii", $itemName, $sellingPrice, $costPrice, $availableStock, $expiryDate, $barcode, $category_id, $editID);
    }
    $stmt->execute();

    $_SESSION['swal'] = json_encode([
        'icon' => 'success',
        'title' => 'Success',
        'text' => 'Item updated successfully!'
    ]);
    header("Location: manageItem.php");
    exit();
}

// ----- Handle Delete Item via AJAX -----
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];

    // Get and delete image file if exists
    $stmt = $conn->prepare("SELECT image FROM items WHERE itemID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($imagePath);
    $stmt->fetch();
    $stmt->close();

    if ($imagePath && file_exists($imagePath)) {
        unlink($imagePath);
    }

    // Delete item record
    $stmt = $conn->prepare("DELETE FROM items WHERE itemID = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Item deleted successfully!'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete item!'
        ]);
    }
    exit;
}

// ----- Fetch All Items -----
$filterCategory = isset($_GET['category']) && $_GET['category'] !== '' ? intval($_GET['category']) : null;
if ($filterCategory) {
    $stmt = $conn->prepare("SELECT items.*, categories.name AS category_name FROM items LEFT JOIN categories ON items.category_id = categories.id WHERE items.category_id = ?");
    $stmt->bind_param("i", $filterCategory);
} else {
    $stmt = $conn->prepare("SELECT items.*, categories.name AS category_name FROM items LEFT JOIN categories ON items.category_id = categories.id");
}
$stmt->execute();
$result = $stmt->get_result();

// Pre-load the item to edit if specified
$editData = null;
if (isset($_GET['edit_id'])) {
    $editID = intval($_GET['edit_id']);
    $editStmt = $conn->prepare("SELECT * FROM items WHERE itemID = ?");
    $editStmt->bind_param("i", $editID);
    $editStmt->execute();
    $editData = $editStmt->get_result()->fetch_assoc();
}
?>

<?php include('../header.php'); ?>

<style>
    body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
    .card { border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
    .table th { background-color: #f8f9fa; }
    .action-btn-group .btn { padding: 0.375rem 0.75rem; }
    .img-thumbnail { width: 80px; height: 80px; object-fit: cover; }
    .modal-header, .modal-footer { border: none; }
        #interactive {
        width: 100%;
        height: 200px;
        position: relative;
        background: black;
    }
    #interactive video {
        width: 100%;
        height: 100%;
    }
    #interactive canvas {
        display: none;
    }
</style>

<div class="container-fluid py-5">
    <div class="card">
        <div class="card-body">
            <!-- Header and Category Filter -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
              <h3 class="fw-bold text-dark d-flex align-items-center gap-2"><i class="fas fa-shopping-basket"></i> Item Lists</h3>
                <div class="d-flex align-items-center gap-2">
                    <form method="get" class="mb-0">
                        <select name="category" id="category" class="form-select" onchange="this.form.submit()">
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
                    <button id="openModalBtn" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i> Item
                    </button>
                </div>
            </div>
            
            <!-- Items Table -->
            <div class="table-responsive">
                <table id="itemsTable" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td><img src="<?= $row['image'] ?>" class="img-thumbnail"></td>
                                <td><?= $row['itemName'] ?></td>
                                <td>RM <?= $row['sellingPrice'] ?></td>
                                <td><?= $row['availableStock'] ?></td>
                                <td><?= $row['category_name'] ?></td>
                                <td class="action-btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary openViewModal"
                                        data-name="<?= $row['itemName'] ?>"
                                        data-price="<?= $row['sellingPrice'] ?>"
                                        data-cost="<?= $row['costPrice'] ?>"
                                        data-stock="<?= $row['availableStock'] ?>"
                                        data-barcode="<?= $row['barcode'] ?>"
                                        data-expiry="<?= $row['expiryDate'] ?>"
                                        data-category="<?= $row['category_name'] ?>"
                                        data-image="<?= $row['image'] ?>"
                                        title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger openDeleteModal"
                                        data-id="<?= $row['itemID'] ?>"
                                        data-name="<?= $row['itemName'] ?>"
                                        data-stock="<?= $row['availableStock'] ?>"
                                        title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <a href="manageItem.php?edit_id=<?= $row['itemID'] ?>" class="btn btn-sm btn-outline-success" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php 
                        $i++;
                        endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- Lebarkan modal -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="manageItem.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="itemName" placeholder="Item Name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barcode</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="barcode" id="barcodeInput" placeholder="Barcode Number">
                                <button type="button" class="btn btn-primary" id="startScannerBtn">
                                    <i class="fas fa-barcode"></i> Scan
                                </button>
                            </div>
                            <div id="scanner-container" style="display:none; margin-top:10px;">
                                <div id="interactive" class="viewport"></div>
                                <button type="button" class="btn btn-danger mt-2 w-100" id="stopScannerBtn">
                                    <i class="fas fa-stop"></i> Stop Scanner
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sell Price (RM)</label>
                            <input type="number" class="form-control" name="sellingPrice" step="0.01" placeholder="Sell Price" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cost Price (RM)</label>
                            <input type="number" class="form-control" name="costPrice" step="0.01" placeholder="Cost Price">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control" name="availableStock" placeholder="Stock" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" name="expiryDate" placeholder="Expiry Date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php
                                $catResult = $conn->query("SELECT * FROM categories");
                                while ($cat = $catResult->fetch_assoc()) {
                                    echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" name="image">
                        </div>
                    </div>
                </div>
                <div class="modal-footer mt-3">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-success">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Edit Item Modal --> 
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" class="edit-form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="itemName" id="edit_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barcode</label>
                            <input type="text" class="form-control" name="barcode" id="edit_barcode">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sell Price (RM)</label>
                            <input type="number" class="form-control" name="sellingPrice" step="0.01" id="edit_price" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cost Price (RM)</label>
                            <input type="number" class="form-control" name="costPrice" step="0.01" id="edit_cost_price" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control" name="availableStock" id="edit_stock" required min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expiry Date</label>
                            <input type="text" class="form-control" name="expiryDate" id="edit_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="edit_category_id" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php
                                $catResult = $conn->query("SELECT * FROM categories");
                                while ($cat = $catResult->fetch_assoc()) {
                                    echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" name="image">
                        </div>
                    </div>
                </div>
                <div class="modal-footer mt-3">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" class="btn btn-success">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- View Item Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Item Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <img id="view_image" src="" class="img-thumbnail mb-3" style="width: 150px; height: 150px;">
                </div>
                <div class="row mb-2">
                    <div class="col-6"><strong>Name:</strong></div>
                    <div class="col-6" id="view_name"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6"><strong>Sell Price:</strong></div>
                    <div class="col-6" id="view_price"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6"><strong>Cost Price:</strong></div>
                    <div class="col-6" id="view_cost"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6"><strong>Stock:</strong></div>
                    <div class="col-6" id="view_stock"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6"><strong>Barcode:</strong></div>
                    <div class="col-6" id="view_barcode"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6"><strong>Expiry:</strong></div>
                    <div class="col-6" id="view_expiry"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6"><strong>Category:</strong></div>
                    <div class="col-6" id="view_category"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>

<script>
    window.addEventListener('pageshow', function(event) {
        if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
            window.location.reload();
        }
    });

    $(document).ready(function() {
        // DataTable Initialization
        $('#itemsTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "destroy": true // Allows reinitialization
        });

        // Trigger Add Item Modal
        $('#openModalBtn').click(function() {
            $('#productModal').modal('show');
        });

        // If edit data exists, auto-open edit modal and fill in details
        <?php if($editData): ?>
            var editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
            $('#edit_id').val("<?= $editData['itemID'] ?>");
            $('#edit_name').val("<?= $editData['itemName'] ?>");
            $('#edit_price').val("<?= $editData['sellingPrice'] ?>");
            $('#edit_cost_price').val("<?= $editData['costPrice'] ?>");
            $('#edit_stock').val("<?= $editData['availableStock'] ?>");
            $('#edit_barcode').val("<?= $editData['barcode'] ?>");
            $('#edit_date').val("<?= $editData['expiryDate'] ?>");
            $('#edit_category_id').val("<?= $editData['category_id'] ?>");
        <?php endif; ?>

        // View Item Modal - Using event delegation
        $(document).on('click', '.openViewModal', function() {
            const btn = $(this);
            $('#view_name').text(btn.data('name'));
            $('#view_price').text("RM " + btn.data('price'));
            $('#view_cost').text("RM " + btn.data('cost'));
            $('#view_stock').text(btn.data('stock'));
            $('#view_barcode').text(btn.data('barcode'));
            $('#view_expiry').text(btn.data('expiry'));
            $('#view_category').text(btn.data('category'));
            $('#view_image').attr('src', btn.data('image'));
            new bootstrap.Modal(document.getElementById('viewModal')).show();
        });

        // Delete Item - Using event delegation
        $(document).on('click', '.openDeleteModal', function() {
            const itemId = $(this).data('id');
            const itemName = $(this).data('name');
            const itemStock = $(this).data('stock');

            if (itemStock > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Cannot Delete Item',
                    text: `Cannot delete "${itemName}". ${itemStock} units left in stock.`,
                    confirmButtonColor: '#3085d6',
                });
                return;
            }

            Swal.fire({
                title: 'Delete Item',
                text: `Are you sure you want to delete "${itemName}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'manageItem.php',
                        type: 'POST',
                        data: { delete_id: itemId },
                        dataType: 'json',
                        success: function(response) {
                            if(response.status === 'success'){
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message,
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'AJAX request failed!'
                            });
                        }
                    });
                }
            });
        });

        // Check if there is a SweetAlert message in session and display it
        <?php if (isset($_SESSION['swal'])): ?>
            let swalData = <?php echo $_SESSION['swal']; unset($_SESSION['swal']); ?>;
            Swal.fire(swalData);
        <?php endif; ?>

        let scannerActive = false;
        
        // Start barcode scanner
        $('#startScannerBtn').click(function() {
            $('#scanner-container').show();
            $('#barcodeInput').focus().val('');
            
            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector('#interactive'),
                    constraints: {
                        width: 480,
                        height: 320,
                        facingMode: "environment"
                    },
                },
                decoder: {
                    readers: [
                        "code_128_reader",
                        "ean_reader",
                        "ean_8_reader",
                        "code_39_reader",
                        "code_39_vin_reader",
                        "codabar_reader",
                        "upc_reader",
                        "upc_e_reader",
                        "i2of5_reader"
                    ]
                },
            }, function(err) {
                if (err) {
                    console.log(err);
                    $('#scanner-container').hide();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to initialize barcode scanner: ' + err.message
                    });
                    return;
                }
                Quagga.start();
                scannerActive = true;
            });

            Quagga.onDetected(function(result) {
                if (result && result.codeResult) {
                    const code = result.codeResult.code;
                    $('#barcodeInput').val(code);
                    stopScanner();
                    $('input[name="sellingPrice"]').focus();
                }
            });
        });

        // Stop barcode scanner
        $('#stopScannerBtn').click(function() {
            stopScanner();
        });

        function stopScanner() {
            if (scannerActive) {
                Quagga.stop();
                scannerActive = false;
            }
            $('#scanner-container').hide();
        }

        // Stop scanner when modal is closed
        $('#productModal').on('hidden.bs.modal', function () {
            stopScanner();
        });
    });
</script>
