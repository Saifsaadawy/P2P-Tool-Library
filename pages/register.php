<?php
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) { header('Location: ' . BASE_PATH . '/pages/index.php'); exit; }
$bp = BASE_PATH;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Register — Tool Library</title>
    <link rel="stylesheet" href="<?= $bp ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $bp ?>/assets/css/auth.css">
    <script>window.BASE_PATH = <?= json_encode($bp) ?>;</script>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width:480px">
        <h2>Create Account</h2>
        <p class="subtitle">Join the Tool Library community</p>

        <div id="error-msg"   class="alert alert-danger"  style="display:none"></div>
        <div id="success-msg" class="alert alert-success" style="display:none">
            Registration successful! <a href="<?= $bp ?>/pages/login.php">Sign in</a>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" id="fname" class="form-control" placeholder="Ahmed">
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" id="lname" class="form-control" placeholder="Hassan">
            </div>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" id="email" class="form-control" placeholder="you@example.com">
        </div>
        <div class="form-group">
            <label>Password <small style="color:#888">(min 8 chars)</small></label>
            <input type="password" id="password" class="form-control" placeholder="••••••••">
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" id="phone" class="form-control" placeholder="01001000001">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem">
            <div class="form-group">
                <label>City</label>
                <input type="text" id="city" class="form-control" placeholder="Cairo">
            </div>
            <div class="form-group">
                <label>Street</label>
                <input type="text" id="street" class="form-control" placeholder="123 Main St">
            </div>
        </div>

        <button class="btn btn-primary" onclick="doRegister()" style="width:100%;padding:.65rem">Create Account</button>
        <div class="auth-footer">Already have an account? <a href="<?= $bp ?>/pages/login.php">Sign in</a></div>
    </div>
</div>

<script src="<?= $bp ?>/assets/js/api.js"></script>
<script>
async function doRegister() {
    const errEl  = document.getElementById('error-msg');
    const succEl = document.getElementById('success-msg');
    errEl.style.display = succEl.style.display = 'none';
    try {
        await apiFetch('/api/auth/register.php', 'POST', {
            fname:    document.getElementById('fname').value.trim(),
            lname:    document.getElementById('lname').value.trim(),
            email:    document.getElementById('email').value.trim(),
            password: document.getElementById('password').value,
            phone:    document.getElementById('phone').value.trim(),
            city:     document.getElementById('city').value.trim(),
            street:   document.getElementById('street').value.trim(),
        });
        succEl.style.display = 'block';
    } catch (err) {
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
    }
}
</script>
</body>
</html>
