
<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('member');
require_once '../includes/header.php';
?>

<style>
.wallet-wrap {
    max-width: 480px;
    margin: 2.5rem auto;
    padding: 0 1rem;
}
.balance-card {
    background: linear-gradient(135deg, #3b5bdb, #5c7cfa);
    color: #fff;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(59,91,219,.25);
}
.balance-card .label { font-size: 0.9rem; opacity: 0.85; margin-bottom: 0.4rem; }
.balance-card .amount { font-size: 2.8rem; font-weight: 700; letter-spacing: -1px; }
.topup-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.8rem;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    margin-bottom: 1.5rem;
}
.topup-card h3 { font-size: 1rem; font-weight: 600; margin-bottom: 1.2rem; color: #333; }
.quick-amounts { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem; margin-bottom: 1rem; }
.quick-btn {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f8f9fa;
    cursor: pointer;
    font-size: 0.85rem;
    color: #444;
    text-align: center;
    transition: all 0.15s;
}
.quick-btn:hover, .quick-btn.active {
    border-color: #3b5bdb;
    background: #eef2ff;
    color: #3b5bdb;
    font-weight: 600;
}
#success-msg { display:none; }
#error-msg   { display:none; }
</style>

<div class="wallet-wrap">
    <div class="balance-card">
        <div class="label">Available Balance</div>
        <div class="amount" id="balance-display">$0.00</div>
    </div>

    <div class="topup-card">
        <h3>💳 Add Money to Wallet</h3>
        <div id="success-msg" class="alert alert-success"></div>
        <div id="error-msg"   class="alert alert-danger"></div>

        <div class="form-group">
            <label>Quick Select</label>
            <div class="quick-amounts">
                <div class="quick-btn" onclick="setAmount(10)">$10</div>
                <div class="quick-btn" onclick="setAmount(25)">$25</div>
                <div class="quick-btn" onclick="setAmount(50)">$50</div>
                <div class="quick-btn" onclick="setAmount(100)">$100</div>
            </div>
        </div>

        <div class="form-group">
            <label>Or enter custom amount</label>
            <input type="number" id="amount-input" class="form-control"
                   placeholder="e.g. 30" min="1" max="10000" step="0.01">
        </div>

        <button class="btn btn-primary" style="width:100%;padding:.7rem" onclick="addBalance()">
            Add to Wallet
        </button>
    </div>
</div>

<script>
async function loadBalance() {
    try {
        const data = await apiFetch('/api/auth/get_profile.php', 'GET');
        const bal  = parseFloat(data.data?.member?.Balance ?? 0);
        document.getElementById('balance-display').textContent = '$' + bal.toFixed(2);
    } catch (e) {}
}

function setAmount(val) {
    document.getElementById('amount-input').value = val;
    document.querySelectorAll('.quick-btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
}

async function addBalance() {
    const successEl = document.getElementById('success-msg');
    const errorEl   = document.getElementById('error-msg');
    successEl.style.display = errorEl.style.display = 'none';

    const amount = parseFloat(document.getElementById('amount-input').value);
    if (!amount || amount <= 0) {
        errorEl.textContent   = 'Please enter a valid amount.';
        errorEl.style.display = 'block';
        return;
    }

    try {
        const data = await apiFetch('/api/payments/add_balance.php', 'POST', { amount });
        const newBal = parseFloat(data.data?.new_balance ?? 0);
        document.getElementById('balance-display').textContent = '$' + newBal.toFixed(2);
        document.getElementById('amount-input').value = '';
        document.querySelectorAll('.quick-btn').forEach(b => b.classList.remove('active'));
        successEl.textContent   = '✅ $' + amount.toFixed(2) + ' added successfully!';
        successEl.style.display = 'block';
        setTimeout(() => successEl.style.display = 'none', 4000);
    } catch (err) {
        errorEl.textContent   = err.message;
        errorEl.style.display = 'block';
    }
}

loadBalance();
</script>

<?php require_once '../includes/footer.php'; ?>