<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('member');
require_once '../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/tools.css">

<div class="container">
    <div class="tools-header">
        <h2>Browse Tools</h2>
        <div class="search-bar">
            <input type="text" id="search-input" class="form-control" placeholder="Search tools…">
            <select id="category-select" class="form-control" style="width:160px">
                <option value="">All Categories</option>
                <option>Power Tools</option>
                <option>Ladders</option>
                <option>Cleaning</option>
                <option>Garden</option>
                <option>Measurement</option>
                <option>Hand Tools</option>
                <option>Other</option>
            </select>
        </div>
    </div>

    <div class="tools-grid" id="tools-grid">
        <p style="color:#888">Loading…</p>
    </div>
</div>

<script src="<?= BASE_PATH ?>/assets/js/api.js"></script>
<script src="<?= BASE_PATH ?>/assets/js/tools.js"></script>
<?php require_once '../includes/footer.php'; ?>
