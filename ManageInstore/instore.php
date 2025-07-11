<?php  
session_start();

// Prevent browser from caching the page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'staff') {
  header("Location: index.php");
  exit();
}

include('header.php');
?>

<div class="container py-4">
  <h3 class="mb-4 fw-bold text-dark"><i class="fas fa-barcode me-2"></i>Instore Purchase</h3>
  <div class="row g-4">
    <!-- Camera Scanner -->
    <div class="col-lg-6">
      <div class="card border-success shadow-lg">
        <div class="card-header bg-success text-white">Camera Scanner</div>
        <div class="card-body">
          <div id="scanner" style="width: 100%; max-width:640px; height: 300px; background-color: #f8f9fa;"></div>
          <p class="mt-3 mb-0">Scanned Barcode:</p>
          <p id="barcodeResult" class="fw-bold text-success fs-5">None</p>
        </div>
      </div>
    </div>

    <!-- Item Information and Checkout -->
    <div class="col-lg-6">
      <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">Item Information & Checkout</div>
        <div class="card-body">
          <div id="itemDetails" class="mb-3"></div>

          <div id="summary" style="display:none;">
            <h5>Items Scanned:</h5>
            <ul id="itemsList" class="mb-3 ms-3" style="list-style-type: disc; padding-left: 20px;"></ul>

            <p class="fw-bold">Total: RM<span id="totalAmount">0.00</span></p>

            <div class="mb-3">
              <label for="paymentMethod" class="form-label">Payment Method:</label>
              <select id="paymentMethod" class="form-select">
                <option value="cash">Cash</option>
                <option value="online">Online</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="amountPaid" class="form-label">Amount Paid (RM):</label>
              <input type="number" id="amountPaid" class="form-control" min="0" step="0.01" placeholder="Enter amount paid" /> <!-- min bayar 0 -->
            </div>

            <p class="fw-bold">Balance: RM<span id="balanceAmount">0.00</span></p>
            <button id="checkoutBtn" class="btn btn-success w-100" disabled>Checkout</button>
          </div>
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

document.addEventListener("DOMContentLoaded", function () {
    const resultBox = document.getElementById('barcodeResult');
    const itemDetails = document.getElementById('itemDetails');
    const summaryDiv = document.getElementById('summary');
    const itemsList = document.getElementById('itemsList');
    const totalAmountEl = document.getElementById('totalAmount');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const paymentMethodEl = document.getElementById('paymentMethod');
    const amountPaidEl = document.getElementById('amountPaid');
    const balanceAmountEl = document.getElementById('balanceAmount');

    let scannedItems = [];

    Quagga.init({
        inputStream: {
            type: "LiveStream",
            target: document.querySelector('#scanner'),
            constraints: {
                width: 400,
                height: 300,
                facingMode: "environment"
            }
        },
        decoder: {
            readers: [
                "code_128_reader", "ean_reader", "ean_8_reader",
                "upc_reader", "upc_e_reader", "code_39_reader",
                "code_39_vin_reader", "codabar_reader", "i2of5_reader",
                "2of5_reader", "code_93_reader"
            ]
        },
        locate: true
    }, function (err) {
        if (err) {
            console.error("Quagga init failed:", err.name, err.message);
            return;
        }
        Quagga.start();
    });

    Quagga.onDetected(function (data) {
        const barcode = data.codeResult.code;
        resultBox.innerText = barcode;

        fetch(`get_item.php?barcode=${barcode}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.item) {
                    const item = data.item;
                    itemDetails.innerHTML = `
                        <p><strong>Item:</strong> ${item.itemName}</p>
                        <p><strong>Price:</strong> RM${item.sellingPrice}</p>
                        <p><strong>Stock:</strong> ${item.availableStock}</p>
                        <button id="nextScanBtn" class="btn btn-primary mt-2">Next</button>
                    `;
                    checkoutBtn.disabled = true;

                    document.getElementById('nextScanBtn').onclick = () => {
                        const existingItemIndex = scannedItems.findIndex(i => i.itemName === item.itemName);
                        
                        if (existingItemIndex >= 0) {
                            if (scannedItems[existingItemIndex].quantity < item.availableStock) {
                                scannedItems[existingItemIndex].quantity += 1;
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Stock Limit',
                                    text: 'Cannot add more items. Exceeds available stock.'
                                });
                                return;
                            }
                        } else {
                            scannedItems.push({
                                itemName: item.itemName,
                                sellingPrice: parseFloat(item.sellingPrice),
                                quantity: 1,
                                availableStock: parseInt(item.availableStock),
                                barcode: item.barcode
                            });
                        }
                        
                        updateSummaryUI();
                        itemDetails.innerHTML = '';
                        resultBox.innerText = 'None';
                    };
                } else {
                    itemDetails.innerHTML = `<p class="text-danger">${data.message}</p>`;
                }
            });
    });

    function updateSummaryUI() {
        summaryDiv.style.display = scannedItems.length ? 'block' : 'none';
        itemsList.innerHTML = '';
        let total = 0;

        scannedItems.forEach((item, index) => {
            const itemTotal = item.sellingPrice * item.quantity;
            total += itemTotal;
            
            itemsList.innerHTML += `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        ${item.itemName} : RM${item.sellingPrice.toFixed(2)}
                    </div>
                    <div class="d-flex align-items-center">
                        <button class="btn btn-sm btn-outline-secondary" onclick="adjustQuantity(${index}, -1)">-</button>
                        <input type="number" id="quantity-${index}" class="form-control mx-2 text-center" 
                               value="${item.quantity}" min="1" max="${item.availableStock}" 
                               style="width: 60px;" onchange="validateQuantity(${index})">
                        <button class="btn btn-sm btn-outline-secondary" onclick="adjustQuantity(${index}, 1)">+</button>
                        <button class="btn btn-sm btn-danger ms-2" onclick="removeItem(${index})">Remove</button>
                    </div>
                </li>
            `;
        });

        totalAmountEl.innerText = total.toFixed(2);
        amountPaidEl.value = '';
        balanceAmountEl.innerText = '0.00';
        checkoutBtn.disabled = true;
    }

    window.adjustQuantity = function(index, change) {
        const input = document.getElementById(`quantity-${index}`);
        let newValue = parseInt(input.value) + change;
        
        newValue = Math.max(parseInt(input.min), Math.min(newValue, parseInt(input.max)));
        
        input.value = newValue;
        scannedItems[index].quantity = newValue;
        updateSummaryUI();
    };

    window.validateQuantity = function(index) {
        const input = document.getElementById(`quantity-${index}`);
        let value = parseInt(input.value);
        const max = parseInt(input.max);
        const min = parseInt(input.min);
        
        if (isNaN(value)) {
            value = min;
        } else if (value > max) {
            value = max;
            Swal.fire({
                icon: 'warning',
                title: 'Stock Limit',
                text: 'Quantity exceeds available stock. Adjusted to maximum available.'
            });
        } else if (value < min) {
            value = min;
        }
        
        input.value = value;
        scannedItems[index].quantity = value;
        updateSummaryUI();
    };

    window.removeItem = function(index) {
        Swal.fire({
            title: 'Remove Item',
            text: 'Are you sure you want to remove this item?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
            if (result.isConfirmed) {
                scannedItems.splice(index, 1);
                updateSummaryUI();
                Swal.fire(
                    'Removed!',
                    'Item has been removed.',
                    'success'
                );
            }
        });
    };

    function updateBalance() {
        const total = scannedItems.reduce((sum, i) => sum + (i.sellingPrice * i.quantity), 0);
        const paid = parseFloat(amountPaidEl.value) || 0;
        const balance = paid - total;

        balanceAmountEl.innerText = balance.toFixed(2);
        checkoutBtn.disabled = scannedItems.length === 0 || balance < 0;
    }

checkoutBtn.addEventListener('click', () => {
    const paymentMethod = paymentMethodEl.value;
    const totalAmount = scannedItems.reduce((sum, i) => sum + (i.sellingPrice * i.quantity), 0);
    const amountPaid = parseFloat(amountPaidEl.value);
    
    if (scannedItems.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No items scanned'
        });
        return;
    }

    if (isNaN(amountPaid) || amountPaid <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Amount',
            text: 'Please enter a valid amount paid.'
        });
        return;
    }

    const balance = amountPaid - totalAmount;

    if (balance < 0) {
        Swal.fire({
            icon: 'error',
            title: 'Insufficient Payment',
            text: `Amount paid (RM${amountPaid.toFixed(2)}) is less than total (RM${totalAmount.toFixed(2)}).`
        });
        return;
    }

    const itemsWithQuantities = scannedItems.map(item => ({
        itemName: item.itemName,
        sellingPrice: item.sellingPrice,
        quantity: item.quantity,
        barcode: item.barcode
    }));

    Swal.fire({
        title: 'Confirm Checkout',
        html: `
            <p><strong>Total Amount:</strong> RM${totalAmount.toFixed(2)}</p>
            <p><strong>Amount Paid:</strong> RM${amountPaid.toFixed(2)}</p>
            <p><strong>Balance:</strong> RM${balance.toFixed(2)}</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Confirm Checkout'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('process_checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    items: itemsWithQuantities,
                    payMethod: paymentMethod,
                    totalAmount: totalAmount,
                    amountPaid: amountPaid,
                    balance: balance
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message || 'Checkout successful'
                    });
                    scannedItems = [];
                    updateSummaryUI();
                    itemDetails.innerHTML = '';
                    resultBox.innerText = 'None';
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Checkout failed'
                    });
                }
            })
            .catch(err => {
                console.error('Checkout failed:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Checkout failed: ' + err.message
                });
            });
        }
    });
});


    amountPaidEl.addEventListener('input', updateBalance);
});
</script>