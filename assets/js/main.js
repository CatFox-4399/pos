// SuperMart POS - Main JavaScript

// ─── Toast Notifications ───────────────────────────────────────────────────
function showToast(message, type = 'success') {
    const id = 'toast_' + Date.now();
    const icons = { success: 'bi-check-circle-fill', danger: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
    const html = `
        <div id="${id}" class="toast align-items-center text-bg-${type} border-0 show" role="alert" style="min-width:280px">
            <div class="d-flex">
                <div class="toast-body"><i class="bi ${icons[type] || 'bi-info-circle-fill'} me-2"></i>${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="document.getElementById('${id}').remove()"></button>
            </div>
        </div>`;
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position:fixed;top:70px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(container);
    }
    container.insertAdjacentHTML('beforeend', html);
    setTimeout(() => { const el = document.getElementById(id); if (el) el.remove(); }, 4000);
}

// ─── Confirm Dialog ────────────────────────────────────────────────────────
function confirmAction(message) {
    return confirm(message);
}

// ─── AJAX Helper ───────────────────────────────────────────────────────────
function ajaxPost(url, data, callback) {
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(callback)
    .catch(err => { showToast('Network error: ' + err.message, 'danger'); });
}

function ajaxGet(url, callback) {
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(callback)
    .catch(err => { showToast('Network error: ' + err.message, 'danger'); });
}

// ─── Currency Format ───────────────────────────────────────────────────────
function formatCurrency(amount) {
    return 'RM ' + parseFloat(amount || 0).toFixed(2);
}

// ─── Admin Search Tables ───────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }
});
