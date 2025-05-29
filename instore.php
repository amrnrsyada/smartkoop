<?php  
session_start();
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
          <div id="scanner" style="width: 100%; height: 300px; background-color: #f8f9fa;"></div>
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
              <input type="number" id="amountPaid" class="form-control" min="0" step="0.01" placeholder="Enter amount paid" />
            </div>

            <p class="fw-bold">Balance: RM<span id="balanceAmount">0.00</span></p>
            <button id="checkoutBtn" class="btn btn-success w-100" disabled>Checkout</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="popupModal" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Message</h5>
        <button type="button" class="btn-close" onclick="closePopup()"></button>
      </div>
      <div class="modal-body">
        <p id="popupMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" onclick="closePopup()">OK</button>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php'); ?>

<script> 
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
                            scannedItems.push({
                                itemName: item.itemName,
                                sellingPrice: parseFloat(item.sellingPrice)
                            });
                            updateSummaryUI();
                            itemDetails.innerHTML = '';
                            resultBox.innerText = '';
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
                total += item.sellingPrice;
                itemsList.innerHTML += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        ${item.itemName} - RM${item.sellingPrice.toFixed(2)}
                        <button class="btn btn-sm btn-danger mb-3" onclick="removeItem(${index})">Remove</button>
                    </li>
                `;
            });

            totalAmountEl.innerText = total.toFixed(2);
            amountPaidEl.value = '';
            balanceAmountEl.innerText = '0.00';
            checkoutBtn.disabled = true;
        }

        // 👇 Make removeItem globally accessible
        window.removeItem = function(index) {
            scannedItems.splice(index, 1);
            updateSummaryUI();
        };

        function updateBalance() {
            const total = scannedItems.reduce((sum, i) => sum + i.sellingPrice, 0);
            const paid = parseFloat(amountPaidEl.value) || 0;
            const balance = paid - total;

            balanceAmountEl.innerText = balance.toFixed(2);
            checkoutBtn.disabled = scannedItems.length === 0 || balance < 0;
        }

        checkoutBtn.addEventListener('click', () => {
            const paymentMethod = paymentMethodEl.value;
            const totalAmount = scannedItems.reduce((sum, i) => sum + i.sellingPrice, 0);
            const amountPaid = parseFloat(amountPaidEl.value);
            const balance = amountPaid - totalAmount;

            if (scannedItems.length === 0) {
                Swal.fire('Error', 'No items scanned', 'error');
                return;
            }

            fetch('process_checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    items: scannedItems,
                    payMethod: paymentMethod,
                    totalAmount: totalAmount,
                    amountPaid: amountPaid,
                    balance: balance
                })
            })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        Swal.fire({
                            title: 'Success',
                            text: data.message,
                            icon: 'success'
                        }).then(() => {
                            scannedItems = [];
                            updateSummaryUI();
                            itemDetails.innerHTML = '';
                            resultBox.innerText = '';
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Unknown error', 'error');
                    }
                } catch (e) {
                    console.error('Invalid JSON:', text);
                    Swal.fire('Error', 'Invalid response from server', 'error');
                }
            })
            .catch(err => {
                console.error('Checkout failed:', err);
                Swal.fire('Error', 'Checkout failed: ' + err.message, 'error');
            });
        });

        amountPaidEl.addEventListener('input', updateBalance);
    });
</script>

