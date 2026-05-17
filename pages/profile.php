<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('member');
require_once '../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/profile.css">
<div class="profile-container">
    <div class="profile-card">
        <div class="profile-avatar" id="avatar">?</div>
        <h2 id="full-name">Loading…</h2>
        <p class="email" id="email"></p>

        <div class="kyc-badge" id="kyc-badge">Checking…</div>

        <div class="trust-score">
            <span class="score-label">Trust Score</span>
            <div class="trust-bar-wrap">
                <div class="trust-bar-fill" id="trust-bar-fill" style="width:0%"></div>
            </div>
            <span class="score-value" id="trust-value">0</span>
        </div>

        <div id="success-msg" class="alert alert-success" style="display:none">Profile updated successfully!</div>
        <div id="error-msg"   class="alert alert-danger"  style="display:none"></div>

        <form id="profile-form">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" id="fname" name="fname" class="form-control">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" id="lname" name="lname" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" id="phone" name="phone" class="form-control">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" id="city" name="city" class="form-control">
                </div>
                <div class="form-group">
                    <label>Street</label>
                    <input type="text" id="street" name="street" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Save Changes</button>
        </form>

        <hr style="margin:1.5rem 0;border:none;border-top:1px solid #eee">

        <h3 style="font-size:1rem;margin-bottom:1rem">Change Password</h3>
        <div id="pw-error" class="alert alert-danger"  style="display:none"></div>
        <div id="pw-ok"    class="alert alert-success" style="display:none">Password changed!</div>
        <div class="form-group">
            <label>Current Password</label>
            <input type="password" id="cur-pw" class="form-control">
        </div>
        <div class="form-group">
            <label>New Password</label>
            <input type="password" id="new-pw" class="form-control">
        </div>
        <button class="btn btn-outline" onclick="changePassword()" style="width:100%">Change Password</button>

        <hr style="margin:1.5rem 0;border:none;border-top:1px solid #eee">
        <h3 style="font-size:1rem;margin-bottom:.8rem">KYC Verification</h3>
        <form id="kyc-form" enctype="multipart/form-data">
            <div class="form-group">
                <label>Upload ID Document (JPG/PNG)</label>
                <input type="file" name="kyc_image" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-outline" style="width:100%">Upload Document</button>
        </form>
    </div>
</div>

<script src="<?= BASE_PATH ?>/assets/js/api.js"></script>
<script src="<?= BASE_PATH ?>/assets/js/profile.js"></script>
<script>
async function changePassword() {
    const errEl = document.getElementById('pw-error');
    const okEl  = document.getElementById('pw-ok');
    errEl.style.display = okEl.style.display = 'none';
    try {
        await apiFetch('/api/auth/change_password.php', 'POST', {
            current_password: document.getElementById('cur-pw').value,
            new_password:     document.getElementById('new-pw').value,
        });
        okEl.style.display = 'block';
        document.getElementById('cur-pw').value = '';
        document.getElementById('new-pw').value = '';
    } catch (err) {
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
    }
}

document.getElementById('kyc-form')?.addEventListener('submit', async e => {
    e.preventDefault();
    const form = new FormData(e.target);
    const res  = await fetch(window.BASE_PATH + '/api/auth/kyc_upload.php', { method: 'POST', body: form });
    const data = await res.json();
    alert(data.message);
});
</script>
<?php require_once '../includes/footer.php'; ?>
