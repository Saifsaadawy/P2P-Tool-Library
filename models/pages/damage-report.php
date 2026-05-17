<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('member');
require_once '../includes/header.php';

$reservationId = (int)($_GET['reservation_id'] ?? 0);
?>
<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/dashboard.css">

<div class="container" style="margin-top:2rem">
    <div style="background:#fff;border:1px solid #e8e8e8;border-radius:12px;padding:1.8rem;max-width:560px;margin:0 auto">
        <h2 style="font-size:1.3rem;margin-bottom:1.2rem">📷 Report Tool Damage</h2>

        <div id="error-msg"   class="alert alert-danger"  style="display:none"></div>
        <div id="success-msg" class="alert alert-success" style="display:none"></div>

        <form id="damage-form" enctype="multipart/form-data">
            <input type="hidden" name="reservation_id" value="<?= $reservationId ?>">

            <div class="form-group">
                <label>Reservation ID</label>
                <input type="number" name="reservation_id_display" class="form-control"
                       value="<?= $reservationId ?>" <?= $reservationId ? 'readonly' : '' ?>>
            </div>
            <div class="form-group">
                <label>Damage Description *</label>
                <textarea name="description" class="form-control" rows="4"
                          placeholder="Describe the damage in detail…" required></textarea>
            </div>
            <div class="form-group">
                <label>Severity *</label>
                <select name="severity" class="form-control">
                    <option value="low">Low — minor scratch / cosmetic</option>
                    <option value="medium">Medium — affects functionality</option>
                    <option value="high">High — tool not usable</option>
                </select>
            </div>
            <div class="form-group">
                <label>Upload Photos (optional)</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
            </div>
            <button type="submit" class="btn btn-danger" style="width:100%;padding:.65rem">Submit Damage Report</button>
        </form>
    </div>
</div>

<script src="<?= BASE_PATH ?>/assets/js/api.js"></script>
<script>
document.getElementById('damage-form')?.addEventListener('submit', async e => {
    e.preventDefault();
    const errEl  = document.getElementById('error-msg');
    const succEl = document.getElementById('success-msg');
    errEl.style.display = succEl.style.display = 'none';

    const form = new FormData(e.target);
    // Override reservation_id with hidden field
    form.set('reservation_id', form.get('reservation_id'));

    try {
        const res  = await fetch(window.BASE_PATH + '/api/damage/create.php', { method: 'POST', body: form });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        succEl.textContent   = 'Damage report submitted. Our team will be in touch.';
        succEl.style.display = 'block';
        e.target.reset();
    } catch (err) {
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
    }
});
</script>
<?php require_once '../includes/footer.php'; ?>
