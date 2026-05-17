<?php
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) {
    $dest = match($_SESSION['role']) {
        'librarian'        => '/pages/dashboard.php',
        'maintenance_staff'=> '/pages/maintenance_staff.php',
        default            => '/pages/index.php',
    };
    header('Location: ' . BASE_PATH . $dest);
    exit;
}
$bp = BASE_PATH;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login — Tool Library</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $bp ?>/assets/css/style.css">
    <script>window.BASE_PATH = <?= json_encode($bp) ?>;</script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f0f0f;
            overflow: hidden;
        }

        /* Animated background */
        .bg {
            position: fixed; inset: 0; z-index: 0;
            background: #0f0f0f;
        }
        .bg::before {
            content: '';
            position: absolute; inset: -50%;
            background: conic-gradient(from 0deg at 50% 50%,
                #1a1a2e 0deg, #16213e 90deg, #0f3460 180deg, #1a1a2e 270deg, #16213e 360deg);
            animation: spin 20s linear infinite;
            opacity: 0.6;
        }
        .bg::after {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse 80% 60% at 50% 50%, transparent 40%, #0f0f0f 80%);
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Floating orbs */
        .orb {
            position: fixed; border-radius: 50%; filter: blur(80px); pointer-events: none; z-index: 0;
        }
        .orb-1 { width: 400px; height: 400px; background: #e8431a33; top: -100px; right: -100px; animation: float1 8s ease-in-out infinite; }
        .orb-2 { width: 300px; height: 300px; background: #3b82f620; bottom: -80px; left: -80px; animation: float2 10s ease-in-out infinite; }
        @keyframes float1 { 0%,100%{transform:translate(0,0)} 50%{transform:translate(-30px,30px)} }
        @keyframes float2 { 0%,100%{transform:translate(0,0)} 50%{transform:translate(20px,-20px)} }

        .auth-wrapper {
            position: relative; z-index: 10;
            width: 100%; max-width: 460px;
            padding: 1rem;
        }

        .auth-card {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            padding: 2.5rem 2.5rem 2rem;
            box-shadow: 0 32px 80px rgba(0,0,0,0.6);
            animation: slideUp 0.5s cubic-bezier(0.16,1,0.3,1);
        }
        @keyframes slideUp {
            from { opacity:0; transform:translateY(24px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .logo {
            font-family: 'Instrument Serif', serif;
            font-size: 1.9rem;
            color: #fff;
            margin-bottom: 0.25rem;
            letter-spacing: -0.02em;
        }
        .logo span { color: #e8431a; font-style: italic; }

        .subtitle {
            color: rgba(255,255,255,0.4);
            font-size: 0.88rem;
            margin-bottom: 2rem;
        }

        /* Role selector */
        .role-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1.75rem;
            background: rgba(255,255,255,0.04);
            border-radius: 14px;
            padding: 0.35rem;
        }

        .role-tab {
            display: flex; flex-direction: column; align-items: center;
            gap: 0.3rem;
            padding: 0.7rem 0.4rem;
            border-radius: 10px;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            background: transparent;
            color: rgba(255,255,255,0.4);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .role-tab .icon { font-size: 1.3rem; }
        .role-tab:hover { background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.7); }
        .role-tab.active {
            background: rgba(232,67,26,0.15);
            border-color: rgba(232,67,26,0.4);
            color: #fff;
            box-shadow: 0 0 20px rgba(232,67,26,0.15);
        }
        .role-tab.active .icon { filter: drop-shadow(0 0 6px rgba(232,67,26,0.8)); }

        /* Form */
        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: rgba(255,255,255,0.5);
            margin-bottom: 0.45rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .form-control {
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            color: #fff;
            font-size: 0.95rem;
            font-family: 'DM Sans', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-control::placeholder { color: rgba(255,255,255,0.2); }
        .form-control:focus {
            border-color: rgba(232,67,26,0.5);
            box-shadow: 0 0 0 3px rgba(232,67,26,0.1);
            background: rgba(255,255,255,0.08);
        }

        .btn-signin {
            width: 100%; padding: 0.8rem;
            background: linear-gradient(135deg, #e8431a, #c93510);
            border: none; border-radius: 12px;
            color: #fff; font-size: 0.95rem;
            font-weight: 600; font-family: 'DM Sans', sans-serif;
            cursor: pointer; margin-top: 0.5rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 20px rgba(232,67,26,0.3);
            position: relative; overflow: hidden;
        }
        .btn-signin:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 28px rgba(232,67,26,0.45);
        }
        .btn-signin:active { transform: translateY(0); }
        .btn-signin.loading { pointer-events: none; opacity: 0.7; }

        .alert-danger {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 10px;
            padding: 0.7rem 1rem;
            color: #fca5a5;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: rgba(255,255,255,0.3);
            font-size: 0.85rem;
        }
        .auth-footer a { color: rgba(232,67,26,0.9); text-decoration: none; }
        .auth-footer a:hover { color: #e8431a; }

        .role-badge {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: rgba(232,67,26,0.12);
            border: 1px solid rgba(232,67,26,0.25);
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            font-size: 0.78rem;
            color: rgba(255,255,255,0.6);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        .role-badge strong { color: #e8431a; }
    </style>
</head>
<body>
<div class="bg"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="logo">🔧 Tool <span>Library</span></div>
        <p class="subtitle">Sign in to your account</p>

        <!-- Role Tabs -->
        <div class="role-tabs">
            <button class="role-tab active" data-role="member" onclick="selectRole('member', this)">
                <span class="icon">👤</span>
                Member
            </button>
            <button class="role-tab" data-role="librarian" onclick="selectRole('librarian', this)">
                <span class="icon">📚</span>
                Librarian
            </button>
            <button class="role-tab" data-role="maintenance_staff" onclick="selectRole('maintenance_staff', this)">
                <span class="icon">🔩</span>
                Staff
            </button>
        </div>

        <div class="role-badge">
            Signing in as: <strong id="role-label">Member</strong>
        </div>

        <div id="error-msg" class="alert-danger" style="display:none"></div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" id="email" class="form-control" placeholder="you@example.com">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" id="password" class="form-control" placeholder="••••••••">
        </div>

        <button class="btn-signin" id="signin-btn" onclick="doLogin()">Sign In</button>

        <div class="auth-footer">
            Don't have an account? <a href="<?= $bp ?>/pages/register.php">Register here</a>
        </div>
    </div>
</div>

<script src="<?= $bp ?>/assets/js/api.js"></script>
<script>
let selectedRole = 'member';

const roleLabels = {
    member:             'Member',
    librarian:          'Librarian',
    maintenance_staff:  'Maintenance Staff',
};

function selectRole(role, el) {
    selectedRole = role;
    document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('role-label').textContent = roleLabels[role];
    document.getElementById('error-msg').style.display = 'none';
}

async function doLogin() {
    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const errEl    = document.getElementById('error-msg');
    const btn      = document.getElementById('signin-btn');
    errEl.style.display = 'none';

    if (!email || !password) {
        errEl.textContent   = 'Please enter your email and password.';
        errEl.style.display = 'block';
        return;
    }

    btn.classList.add('loading');
    btn.textContent = 'Signing in...';

    try {
        const data = await apiFetch('/api/auth/login.php', 'POST', {
            email,
            password,
            role: selectedRole
        });
        location.href = data.data?.redirect ?? (window.BASE_PATH + '/pages/index.php');
    } catch (err) {
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
        btn.classList.remove('loading');
        btn.textContent = 'Sign In';
    }
}

document.addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });
</script>
</body>
</html>