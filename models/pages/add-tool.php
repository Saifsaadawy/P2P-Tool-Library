<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('member');
require_once '../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/tools.css">

<div class="container">
    <div class="add-tool-wrapper">
        <h2>List a Tool for Borrowing</h2>

        <div id="message" class="alert" style="display:none; margin-bottom: 20px;"></div>

        <form id="addToolForm" enctype="multipart/form-data">
            <div class="form-group">
                <label>Tool Name <span class="required">*</span></label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Category <span class="required">*</span></label>
                    <select name="category" class="form-control" required>
                        <option value="">Select Category...</option>
                        <option value="Power Tools">Power Tools</option>
                        <option value="Hand Tools">Hand Tools</option>
                        <option value="Garden">Garden</option>
                        <option value="Ladders">Ladders</option>
                        <option value="Cleaning">Cleaning</option>
                        <option value="Measurement">Measurement</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Condition <span class="required">*</span></label>
                    <select name="condition" class="form-control" required>
                        <option value="New">New</option>
                        <option value="Good" selected>Good</option>
                        <option value="Fair">Fair</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Daily Rate ($) <span class="required">*</span></label>
                    <input type="number" name="daily_rate" class="form-control" step="0.01" min="1" required>
                </div>

                <div class="form-group">
                    <label>Security Deposit ($)</label>
                    <input type="number" name="security_deposit" class="form-control" step="0.01" value="0">
                </div>
            </div>

            <div class="form-group">
                <label>Safety Certificate Expiry <span class="required">*</span></label>
                <input type="date" name="safety_expiry" class="form-control" min="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label>Tool Photos (Max 4)</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 20px; padding: 12px;">
                Submit for Approval
            </button>
        </form>
    </div>
</div>

<script src="<?= BASE_PATH ?>/assets/js/api.js"></script>
<script>
    document.getElementById('addToolForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const messageDiv = document.getElementById('message');

        // Disable button while submitting
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        try {
            const response = await fetch('<?= BASE_PATH ?>/api/tools/add_tool.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                messageDiv.className = 'alert alert-success';
                messageDiv.textContent = data.message || 'Tool submitted successfully for approval!';
                this.reset();

                // No redirect to my-tools.php since it doesn't exist yet
                // setTimeout(() => {
                //     messageDiv.innerHTML += '<br><small>You can add another tool or go to <a href="tools.php">Browse Tools</a></small>';
                // }, 1500);
            } else {
                messageDiv.className = 'alert alert-danger';
                messageDiv.textContent = data.message || 'Submission failed';
            }
        } catch (err) {
            messageDiv.className = 'alert alert-danger';
            messageDiv.textContent = 'Connection error. Please try again.';
        } finally {
            messageDiv.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>