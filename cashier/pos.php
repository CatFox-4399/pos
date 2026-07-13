<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$pageTitle = 'POS - ' . APP_NAME;
$taxRate = (float)(getSetting('tax_rate') ?? 6);
$storeName = getSetting('store_name') ?? APP_NAME;
$autoCashDrawer = getSetting('auto_cash_drawer') ?? '1';
$cashierCanManualDrawer = getSetting('cashier_manual_drawer') ?? '0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="pos-wrapper">
    <!-- LEFT: Product + Cart -->
    <div class="pos-left">
        <!-- Barcode Scanner -->
        <div class="p-3 border-bottom bg-light">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white"><i class="bi bi-upc-scan"></i></span>
                        <input type="text" id="barcodeInput" class="form-control form-control-lg barcode-input"
                               placeholder="Scan barcode or type product name..." autofocus autocomplete="off">
                        <button class="btn btn-primary" onclick="searchByBarcode()"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-warning" onclick="holdOrder()" title="Hold Order"><i class="bi bi-pause-circle"></i> Hold</button>
                    <button class="btn btn-outline-info" onclick="loadHoldOrders()" title="Resume Order"><i class="bi bi-play-circle"></i> Resume</button>
                    <button class="btn btn-outline-danger" onclick="cancelTransaction()" title="Cancel"><i class="bi bi-x-circle"></i> Cancel</button>
                </div>
            </div>
        </div>

        <!-- Product Search Results -->
        <div id="searchResults" class="p-2 border-bottom" style="display:none; background:#fff8e1;">
            <div class="d-flex align-items-center mb-2">
                <span class="fw-semibold small text-muted">Search Results:</span>
                <button class="btn btn-sm btn-link ms-auto text-muted" onclick="closeSearch()"><i class="bi bi-x"></i></button>
            </div>
            <div id="searchResultsGrid" class="product-grid"></div>
        </div>

        <!-- Cart -->
        <div class="cart-scroll flex-grow-1">
            <table class="table table-hover cart-table mb-0">
                <thead>
                    <tr>
                        <th style="width:40%">Product</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Disc</th>
                        <th class="text-end">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cartBody">
                    <tr id="emptyCartRow">
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-cart3 d-block mb-2" style="font-size:2rem"></i>
                            Cart is empty. Scan a product to start.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Cart Totals -->
        <div class="border-top p-3 bg-light">
            <div class="row g-1 text-end small">
                <div class="col-8 text-muted">Subtotal:</div>
                <div class="col-4 fw-semibold" id="displaySubtotal">RM 0.00</div>
                <div class="col-8 text-muted">Discount:</div>
                <div class="col-4 text-danger fw-semibold" id="displayDiscount">- RM 0.00</div>
                <div class="col-8 text-muted">Tax (<?= $taxRate ?>%):</div>
                <div class="col-4 fw-semibold" id="displayTax">RM 0.00</div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Payment Panel -->
    <div class="pos-right">

        <!-- Cashier Info Bar -->
        <div class="px-3 py-2 bg-primary text-white d-flex justify-content-between align-items-center">
            <div class="lh-1">
                <div class="fw-bold small"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div style="font-size:0.7rem;opacity:0.75"><?= date('d M Y, H:i') ?></div>
            </div>
            <?php if (isAdmin() || $cashierCanManualDrawer === '1'): ?>
            <button class="btn btn-sm btn-outline-light py-0 px-2" onclick="openDrawerManual()" title="Open Cash Drawer">
                <i class="bi bi-safe"></i>
            </button>
            <?php endif; ?>
        </div>

        <!-- Grand Total -->
        <div class="px-3 pt-2 pb-1">
            <div class="total-display">
                <div style="font-size:0.7rem;opacity:0.8;letter-spacing:1px">GRAND TOTAL</div>
                <div id="displayTotal" style="font-size:1.6rem;font-weight:700;line-height:1.2">RM 0.00</div>
            </div>
        </div>

        <!-- Totals breakdown -->
        <div class="px-3 py-1 border-bottom">
            <div class="d-flex justify-content-between" style="font-size:0.78rem">
                <span class="text-muted">Subtotal</span><span id="displaySubtotal2" class="fw-semibold">RM 0.00</span>
            </div>
            <div class="d-flex justify-content-between" style="font-size:0.78rem">
                <span class="text-muted">Discount</span><span id="displayDiscount2" class="text-danger">- RM 0.00</span>
            </div>
            <div class="d-flex justify-content-between" style="font-size:0.78rem">
                <span class="text-muted">Tax (<?= $taxRate ?>%)</span><span id="displayTax2">RM 0.00</span>
            </div>
        </div>

        <div class="px-3 py-2">

            <!-- Discount Row -->
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="small fw-semibold text-nowrap"><i class="bi bi-tag me-1 text-primary"></i>Disc</span>
                <select id="discountType" class="form-select form-select-sm" style="width:100px" <?= !isAdmin() ? 'disabled' : '' ?>>
                    <option value="percent">%</option>
                    <option value="amount">RM</option>
                </select>
                <input type="number" id="discountValue" class="form-control form-control-sm text-end" placeholder="0" min="0" step="0.01"
                       <?= !isAdmin() ? 'disabled' : '' ?> oninput="recalculate()">
                <?php if (isAdmin()): ?>
                <span class="badge bg-success text-nowrap" style="font-size:0.6rem">ADMIN</span>
                <?php endif; ?>
            </div>

            <!-- Payment Method -->
            <div class="mb-2">
                <div class="small fw-semibold mb-1"><i class="bi bi-credit-card me-1 text-primary"></i>Payment</div>
                <div class="payment-grid">
                    <?php
                    $methods = [
                        ['cash','Cash','bi-cash'],
                        ['credit_card','Credit','bi-credit-card'],
                        ['debit_card','Debit','bi-credit-card-2-front'],
                        ['duitnow_qr','DuitNow','bi-qr-code'],
                        ['tng_ewallet','TnG','bi-wallet2'],
                        ['grabpay','GrabPay','bi-phone'],
                        ['boost','Boost','bi-lightning-charge'],
                    ];
                    foreach ($methods as $m): ?>
                    <button class="payment-btn-sm <?= $m[0]==='cash'?'active':'' ?>"
                            data-method="<?= $m[0] ?>" onclick="selectPayment('<?= $m[0] ?>')">
                        <i class="bi <?= $m[2] ?>"></i><span><?= $m[1] ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cash Received -->
            <div id="cashPanel">
                <div class="small fw-semibold mb-1"><i class="bi bi-cash me-1 text-primary"></i>Cash Received</div>
                <input type="number" id="cashReceived" class="form-control form-control-sm text-end fw-bold mb-1"
                       placeholder="0.00" step="0.01" min="0" oninput="calcChange()" style="font-size:1.1rem">
                <div class="d-flex gap-1 mb-2 flex-wrap">
                    <?php foreach ([5,10,20,50,100] as $amt): ?>
                    <button class="btn btn-xs btn-outline-secondary flex-fill" onclick="setCash(<?= $amt ?>)" style="font-size:0.72rem;padding:3px 2px">RM<?= $amt ?></button>
                    <?php endforeach; ?>
                    <button class="btn btn-xs btn-outline-primary flex-fill" onclick="setCashExact()" style="font-size:0.72rem;padding:3px 2px">Exact</button>
                </div>
                <div class="d-flex justify-content-between align-items-center px-2 py-1 rounded" style="background:#d1fae5">
                    <span class="fw-semibold small">Change:</span>
                    <span id="changeDisplay" class="fw-bold text-success" style="font-size:1.1rem">RM 0.00</span>
                </div>
            </div>
        </div>

        <!-- Charge Button (sticky bottom) -->
        <div class="px-3 pb-3 pt-1 mt-auto">
            <button id="btnCharge" class="btn btn-success w-100 fw-bold" style="font-size:1rem;padding:12px" onclick="processPayment()">
                <i class="bi bi-check-circle me-2"></i>CHARGE
            </button>
        </div>
    </div>
</div>

<!-- Admin Approval Modal (for item removal) -->
<div class="modal fade" id="adminApprovalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-shield-lock me-2"></i>Admin Approval Required</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Removing items from cart requires <strong>Admin approval</strong>.</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Admin Username</label>
                    <input type="text" id="adminUsername" class="form-control" placeholder="Enter admin username">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Admin Password</label>
                    <input type="password" id="adminPassword" class="form-control" placeholder="Enter admin password">
                </div>
                <div id="approvalError" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmAdminApproval()">
                    <i class="bi bi-check me-1"></i>Approve Removal
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hold Orders Modal -->
<div class="modal fade" id="holdModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-pause-circle me-2"></i>Held Orders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="holdOrdersList">Loading...</div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:340px">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title mb-0"><i class="bi bi-check-circle me-2"></i>Transaction Complete</h6>
            </div>
            <div class="modal-body p-0" style="background:#f5f5f5">
                <!-- Thermal receipt paper look -->
                <div style="margin:12px auto;width:260px;background:#fff;font-family:'Courier New',monospace;font-size:12px;padding:12px 14px;box-shadow:0 2px 8px rgba(0,0,0,0.12);border-radius:2px">
                    <div id="receiptContent"></div>
                </div>
            </div>
            <div class="modal-footer py-2 gap-2">
                <button class="btn btn-outline-secondary btn-sm" onclick="printReceipt()"><i class="bi bi-printer me-1"></i>Print Receipt</button>
                <button class="btn btn-success btn-sm" onclick="newTransaction()"><i class="bi bi-plus-circle me-1"></i>New Sale</button>
            </div>
        </div>
    </div>
</div>

<script>
const TAX_RATE    = <?= $taxRate ?> / 100;
const APP_URL     = '<?= APP_URL ?>';
const IS_ADMIN    = <?= isAdmin() ? 'true' : 'false' ?>;
const AUTO_DRAWER = <?= $autoCashDrawer === '1' ? 'true' : 'false' ?>;
// Store info – PHP json_encode ensures correct UTF-8 encoding regardless of
// what characters are in the store name (Malay, Chinese, symbols, etc.)
const STORE_NAME    = <?= json_encode((string)($storeName ?? '')) ?>;
const STORE_ADDRESS = <?= json_encode((string)(getSetting('store_address') ?? '')) ?>;
const STORE_PHONE   = <?= json_encode((string)(getSetting('store_phone') ?? '')) ?>;
const RECEIPT_FOOTER= <?= json_encode((string)(getSetting('receipt_footer') ?? 'Thank you for shopping with us!')) ?>;

let cart = [];
let currentPaymentMethod = 'cash';
let pendingRemoveIndex = null;
let lastTransaction = null;

// ─── Barcode / Search ───────────────────────────────────────────────────────
const barcodeInput = document.getElementById('barcodeInput');
let searchTimer;

barcodeInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') { searchByBarcode(); return; }
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        if (barcodeInput.value.length >= 2) searchProducts(barcodeInput.value);
    }, 400);
});

function searchByBarcode() {
    const q = barcodeInput.value.trim();
    if (!q) return;
    fetch(APP_URL + '/api/product_search.php?barcode=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            if (data.success && data.product) {
                addToCart(data.product);
                barcodeInput.value = '';
                closeSearch();
            } else {
                searchProducts(q);
            }
        });
}

function searchProducts(q) {
    fetch(APP_URL + '/api/product_search.php?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            if (data.success && data.products && data.products.length > 0) {
                showSearchResults(data.products);
            } else {
                closeSearch();
            }
        });
}

function showSearchResults(products) {
    const grid = document.getElementById('searchResultsGrid');
    grid.innerHTML = products.map(p => `
        <div class="product-card" onclick="addToCart(${JSON.stringify(p).replace(/"/g,'&quot;')}); closeSearch(); barcodeInput.value='';">
            <div class="price">RM ${parseFloat(p.selling_price).toFixed(2)}</div>
            <div class="fw-semibold" style="font-size:0.75rem">${escHtml(p.product_name)}</div>
            <div class="text-muted" style="font-size:0.7rem">${escHtml(p.barcode)}</div>
        </div>
    `).join('');
    document.getElementById('searchResults').style.display = 'block';
}

function closeSearch() {
    document.getElementById('searchResults').style.display = 'none';
}

// ─── Cart ───────────────────────────────────────────────────────────────────
function addToCart(product) {
    const idx = cart.findIndex(i => i.id == product.id);
    if (idx >= 0) {
        cart[idx].qty++;
    } else {
        cart.push({ id: product.id, name: product.product_name, price: parseFloat(product.selling_price), qty: 1, discount: 0 });
    }
    renderCart();
    barcodeInput.focus();
}

function renderCart() {
    const tbody = document.getElementById('cartBody');
    if (cart.length === 0) {
        tbody.innerHTML = '<tr id="emptyCartRow"><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-cart3 d-block mb-2" style="font-size:2rem"></i>Cart is empty. Scan a product to start.</td></tr>';
        recalculate();
        return;
    }
    tbody.innerHTML = cart.map((item, i) => {
        const lineTotal = (item.price * item.qty) - item.discount;
        return `<tr class="fade-in">
            <td><div class="fw-semibold small">${escHtml(item.name)}</div><div class="text-muted" style="font-size:0.7rem">RM ${item.price.toFixed(2)} each</div></td>
            <td class="text-center">
                <div class="d-flex align-items-center justify-content-center gap-1">
                    <button class="btn btn-outline-secondary qty-btn" onclick="changeQty(${i},-1)">-</button>
                    <span class="fw-bold px-1">${item.qty}</span>
                    <button class="btn btn-outline-secondary qty-btn" onclick="changeQty(${i},1)">+</button>
                </div>
            </td>
            <td class="text-end">RM ${item.price.toFixed(2)}</td>
            <td class="text-end text-danger small">- RM ${item.discount.toFixed(2)}</td>
            <td class="text-end fw-bold">RM ${lineTotal.toFixed(2)}</td>
            <td><button class="btn btn-sm btn-outline-danger" onclick="removeItem(${i})"><i class="bi bi-trash"></i></button></td>
        </tr>`;
    }).join('');
    recalculate();
}

function changeQty(idx, delta) {
    cart[idx].qty += delta;
    if (cart[idx].qty <= 0) {
        requestRemoveItem(idx);
        return;
    }
    renderCart();
}

function removeItem(idx) {
    requestRemoveItem(idx);
}

function requestRemoveItem(idx) {
    if (IS_ADMIN) {
        cart.splice(idx, 1);
        renderCart();
        showToast('Item removed', 'info');
        return;
    }
    pendingRemoveIndex = idx;
    document.getElementById('adminUsername').value = '';
    document.getElementById('adminPassword').value = '';
    document.getElementById('approvalError').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('adminApprovalModal')).show();
}

function confirmAdminApproval() {
    const username = document.getElementById('adminUsername').value.trim();
    const password = document.getElementById('adminPassword').value;
    fetch(APP_URL + '/api/admin_approve.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({username, password})
    }).then(r => r.json()).then(data => {
        if (data.success) {
            cart.splice(pendingRemoveIndex, 1);
            pendingRemoveIndex = null;
            bootstrap.Modal.getInstance(document.getElementById('adminApprovalModal')).hide();
            renderCart();
            showToast('Item removed with admin approval', 'success');
        } else {
            document.getElementById('approvalError').textContent = data.message || 'Invalid admin credentials';
            document.getElementById('approvalError').classList.remove('d-none');
        }
    });
}

// ─── Calculations ───────────────────────────────────────────────────────────
function recalculate() {
    let subtotal = cart.reduce((s, i) => s + (i.price * i.qty) - i.discount, 0);
    let discountVal = parseFloat(document.getElementById('discountValue')?.value || 0) || 0;
    let discountType = document.getElementById('discountType')?.value || 'percent';
    let overallDiscount = discountType === 'percent' ? subtotal * (discountVal / 100) : discountVal;
    if (overallDiscount > subtotal) overallDiscount = subtotal;
    let afterDiscount = subtotal - overallDiscount;
    let tax = afterDiscount * TAX_RATE;
    let total = afterDiscount + tax;

    // Left panel breakdown
    const elSub = document.getElementById('displaySubtotal');
    const elDisc = document.getElementById('displayDiscount');
    const elTax = document.getElementById('displayTax');
    if (elSub) elSub.textContent = 'RM ' + subtotal.toFixed(2);
    if (elDisc) elDisc.textContent = '- RM ' + overallDiscount.toFixed(2);
    if (elTax) elTax.textContent = 'RM ' + tax.toFixed(2);
    document.getElementById('displayTotal').textContent = 'RM ' + total.toFixed(2);
    // Right panel breakdown
    const elSub2 = document.getElementById('displaySubtotal2');
    const elDisc2 = document.getElementById('displayDiscount2');
    const elTax2 = document.getElementById('displayTax2');
    if (elSub2) elSub2.textContent = 'RM ' + subtotal.toFixed(2);
    if (elDisc2) elDisc2.textContent = '- RM ' + overallDiscount.toFixed(2);
    if (elTax2) elTax2.textContent = 'RM ' + tax.toFixed(2);
    calcChange();
    return { subtotal, overallDiscount, tax, total };
}

function calcChange() {
    const totalText = document.getElementById('displayTotal').textContent.replace('RM ','');
    const total = parseFloat(totalText) || 0;
    const received = parseFloat(document.getElementById('cashReceived').value) || 0;
    const change = received - total;
    document.getElementById('changeDisplay').textContent = 'RM ' + Math.max(0, change).toFixed(2);
    document.getElementById('changeDisplay').className = change >= 0 ? 'fw-bold text-success fs-5' : 'fw-bold text-danger fs-5';
}

// ─── Payment ─────────────────────────────────────────────────────────────────
function selectPayment(method) {
    currentPaymentMethod = method;
    document.querySelectorAll('.payment-btn-sm,.payment-btn').forEach(b => b.classList.toggle('active', b.dataset.method === method));
    document.getElementById('cashPanel').style.display = method === 'cash' ? 'block' : 'none';
}

function setCash(amount) {
    document.getElementById('cashReceived').value = amount.toFixed(2);
    calcChange();
}

function setCashExact() {
    const totalText = document.getElementById('displayTotal').textContent.replace('RM ','');
    document.getElementById('cashReceived').value = parseFloat(totalText).toFixed(2);
    calcChange();
}

function processPayment() {
    if (cart.length === 0) { showToast('Cart is empty!', 'warning'); return; }
    const totals = recalculate();
    if (totals.total <= 0) { showToast('Total must be greater than 0', 'warning'); return; }

    if (currentPaymentMethod === 'cash') {
        const received = parseFloat(document.getElementById('cashReceived').value) || 0;
        if (received < totals.total) { showToast('Insufficient cash received!', 'danger'); return; }
    }

    const payload = {
        cart,
        payment_method: currentPaymentMethod,
        subtotal: totals.subtotal,
        discount: totals.overallDiscount,
        tax: totals.tax,
        total: totals.total,
        cash_received: parseFloat(document.getElementById('cashReceived').value) || 0,
        change_amount: Math.max(0, (parseFloat(document.getElementById('cashReceived').value) || 0) - totals.total)
    };

    document.getElementById('btnCharge').disabled = true;

    fetch(APP_URL + '/api/process_payment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    }).then(r => r.json()).then(data => {
        if (data.success) {
            lastTransaction = data.transaction;
            showReceipt(data.transaction);
            if (AUTO_DRAWER && currentPaymentMethod === 'cash') openDrawerAuto(data.transaction.id);
        } else {
            showToast(data.message || 'Payment failed', 'danger');
            document.getElementById('btnCharge').disabled = false;
        }
    }).catch(() => {
        showToast('Network error', 'danger');
        document.getElementById('btnCharge').disabled = false;
    });
}

// ─── Receipt ─────────────────────────────────────────────────────────────────
function showReceipt(txn) {
    // STORE_NAME / STORE_ADDRESS / STORE_PHONE / RECEIPT_FOOTER are
    // defined at the top of the script via PHP json_encode – fully UTF-8 safe.
    let rows = '';
    txn.items.forEach(item => {
        const name      = escHtml(item.name);
        const qty       = item.qty;
        const tot       = 'RM ' + parseFloat(item.total).toFixed(2);
        const nameShort = name.length > 18 ? name.substring(0,17)+'.' : name;
        rows += `<div style="display:flex;justify-content:space-between"><span>${nameShort} x${qty}</span><span>${tot}</span></div>`;
    });

    const payMethod = txn.payment_method.toUpperCase().replace(/_/g,' ');
    let cashRows = '';
    if (txn.cash_received > 0) {
        cashRows = `
        <div style="display:flex;justify-content:space-between"><span>Cash:</span><span>RM ${parseFloat(txn.cash_received).toFixed(2)}</span></div>
        <div style="display:flex;justify-content:space-between"><span>Change:</span><span>RM ${parseFloat(txn.change_amount).toFixed(2)}</span></div>`;
    }

    const html = `
    <div style="text-align:center;margin-bottom:8px">
        <div style="font-size:15px;font-weight:700;letter-spacing:0.5px">${escHtml(STORE_NAME)}</div>
        <div style="font-size:11px">${escHtml(STORE_ADDRESS)}</div>
        <div style="font-size:11px">${escHtml(STORE_PHONE)}</div>
    </div>
    <div style="border-top:1px dashed #000;margin:5px 0"></div>
    <div style="font-size:11px">
        <div style="display:flex;justify-content:space-between"><span>TXN:</span><span><b>${txn.transaction_no}</b></span></div>
        <div style="display:flex;justify-content:space-between"><span>Date:</span><span>${txn.created_at}</span></div>
        <div style="display:flex;justify-content:space-between"><span>Cashier:</span><span><?= htmlspecialchars($_SESSION['username']) ?></span></div>
    </div>
    <div style="border-top:1px dashed #000;margin:5px 0"></div>
    <div style="font-size:11px">${rows}</div>
    <div style="border-top:1px dashed #000;margin:5px 0"></div>
    <div style="font-size:11px">
        <div style="display:flex;justify-content:space-between"><span>Subtotal:</span><span>RM ${parseFloat(txn.subtotal).toFixed(2)}</span></div>
        <div style="display:flex;justify-content:space-between"><span>Discount:</span><span>- RM ${parseFloat(txn.discount).toFixed(2)}</span></div>
        <div style="display:flex;justify-content:space-between"><span>Tax:</span><span>RM ${parseFloat(txn.tax).toFixed(2)}</span></div>
    </div>
    <div style="border-top:1px dashed #000;margin:5px 0"></div>
    <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:700"><span>TOTAL:</span><span>RM ${parseFloat(txn.total).toFixed(2)}</span></div>
    <div style="font-size:11px;margin-top:3px">
        <div style="display:flex;justify-content:space-between"><span>Payment:</span><span>${payMethod}</span></div>
        ${cashRows}
    </div>
    <div style="border-top:1px dashed #000;margin:8px 0"></div>
    <div style="text-align:center;font-size:11px">${escHtml(RECEIPT_FOOTER)}</div>
    <div style="text-align:center;font-size:10px;margin-top:4px;color:#555">*** Thank You ***</div>`;

    document.getElementById('receiptContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('receiptModal')).show();
}

function printReceipt() {
    const receiptHtml = document.getElementById('receiptContent').innerHTML;
    const htmlContent = `<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Receipt</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Courier New',monospace; font-size:12px; width:280px; padding:8px; background:#fff; color:#000; }
  table { width:100%; border-collapse:collapse; font-size:11px; }
  th, td { padding:2px 0; }
  .text-center { text-align:center; }
  .text-end { text-align:right; }
  .fw-bold { font-weight:700; }
  .fw-semibold { font-weight:600; }
  .text-muted { color:#555; }
  .text-danger { color:#000; }
  .text-success { color:#000; }
  .receipt-header { text-align:center; margin-bottom:6px; }
  .receipt-line { border-top:1px dashed #000; margin:5px 0; }
  .d-flex { display:flex; }
  .justify-content-between { justify-content:space-between; }
  .small { font-size:11px; }
  .fs-5 { font-size:13px; }
  .mb-1 { margin-bottom:3px; }
  .table { width:100%; }
  .table th { border-bottom:1px solid #000; }
  .table-sm td, .table-sm th { padding:2px 0; }
  @media print {
    @page { margin:0; size:80mm auto; }
    body { width:72mm; }
  }
</style>
</head>
<body>
${receiptHtml}
</body>
</html>`;
    // Use a Blob with explicit UTF-8 MIME type so the browser
    // correctly decodes all characters (including non-ASCII / Malay / Chinese names)
    const blob = new Blob([htmlContent], { type: 'text/html;charset=utf-8' });
    const url  = URL.createObjectURL(blob);
    const win  = window.open(url, '_blank', 'width=320,height=600,scrollbars=no,toolbar=no,menubar=no');
    if (!win) { alert('Please allow pop-ups to print the receipt.'); URL.revokeObjectURL(url); return; }
    win.onload = function() {
        win.focus();
        setTimeout(() => {
            win.print();
            win.onafterprint = function() { win.close(); URL.revokeObjectURL(url); };
            // Fallback: close after 5 s if onafterprint doesn't fire
            setTimeout(() => { try { win.close(); } catch(e){} URL.revokeObjectURL(url); }, 5000);
        }, 300);
    };
}

function newTransaction() {
    cart = [];
    renderCart();
    document.getElementById('cashReceived').value = '';
    document.getElementById('changeDisplay').textContent = 'RM 0.00';
    if (document.getElementById('discountValue')) document.getElementById('discountValue').value = '';
    selectPayment('cash');
    bootstrap.Modal.getInstance(document.getElementById('receiptModal')).hide();
    document.getElementById('btnCharge').disabled = false;
    barcodeInput.focus();
    showToast('Ready for new transaction', 'success');
}

// ─── Hold Orders ─────────────────────────────────────────────────────────────
function holdOrder() {
    if (cart.length === 0) { showToast('Cart is empty!', 'warning'); return; }
    const label = prompt('Hold order label (optional):', 'Order ' + Date.now());
    if (label === null) return;
    fetch(APP_URL + '/api/hold_order.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'hold', cart, label})
    }).then(r=>r.json()).then(d => {
        if (d.success) { cart=[]; renderCart(); showToast('Order held: ' + label, 'info'); }
        else showToast(d.message, 'danger');
    });
}

function loadHoldOrders() {
    document.getElementById('holdOrdersList').innerHTML = 'Loading...';
    new bootstrap.Modal(document.getElementById('holdModal')).show();
    fetch(APP_URL + '/api/hold_order.php?action=list')
        .then(r=>r.json()).then(d => {
            if (d.success && d.orders.length > 0) {
                document.getElementById('holdOrdersList').innerHTML = d.orders.map(o => `
                    <div class="hold-card mb-2" onclick="resumeOrder(${o.id})">
                        <div class="fw-semibold">${escHtml(o.label||'Unnamed Order')}</div>
                        <div class="text-muted small">${new Date(o.created_at).toLocaleString()} · ${JSON.parse(o.cart_data).length} items</div>
                    </div>
                `).join('');
            } else {
                document.getElementById('holdOrdersList').innerHTML = '<p class="text-muted text-center py-3">No held orders</p>';
            }
        });
}

function resumeOrder(id) {
    fetch(APP_URL + '/api/hold_order.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'resume', id})
    }).then(r=>r.json()).then(d => {
        if (d.success) {
            cart = d.cart;
            renderCart();
            bootstrap.Modal.getInstance(document.getElementById('holdModal')).hide();
            showToast('Order resumed', 'success');
        }
    });
}

// ─── Cancel ──────────────────────────────────────────────────────────────────
function cancelTransaction() {
    if (cart.length === 0) { showToast('Cart is already empty', 'info'); return; }
    if (confirm('Cancel current transaction?\n\nAll items will be cleared.')) {
        cart = [];
        renderCart();
        if (document.getElementById('discountValue')) document.getElementById('discountValue').value = '';
        document.getElementById('cashReceived').value = '';
        showToast('Transaction cancelled', 'warning');
        barcodeInput.focus();
    }
}

// ─── Cash Drawer ─────────────────────────────────────────────────────────────
function openDrawerAuto(txnId) {
    fetch(APP_URL + '/api/cash_drawer.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({method:'AUTO', transaction_id: txnId})
    });
}

function openDrawerManual() {
    fetch(APP_URL + '/api/cash_drawer.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({method:'MANUAL'})
    }).then(r=>r.json()).then(d => {
        showToast(d.message || 'Cash drawer opened', d.success ? 'success' : 'danger');
    });
}

// ─── Utilities ────────────────────────────────────────────────────────────────
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function showToast(message, type = 'success') {
    const id = 'toast_' + Date.now();
    const icons = { success:'bi-check-circle-fill', danger:'bi-x-circle-fill', warning:'bi-exclamation-triangle-fill', info:'bi-info-circle-fill' };
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position:fixed;top:70px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(container);
    }
    container.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast align-items-center text-bg-${type} border-0 show" style="min-width:280px">
            <div class="d-flex">
                <div class="toast-body"><i class="bi ${icons[type]||'bi-info-circle-fill'} me-2"></i>${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="document.getElementById('${id}').remove()"></button>
            </div>
        </div>`);
    setTimeout(() => { const el=document.getElementById(id); if(el) el.remove(); }, 4000);
}

// Init
selectPayment('cash');
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
