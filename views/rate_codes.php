<?php
// views/rate_code_management.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../database/config.php';

$pdo = getDB();

$_SESSION['full_name'] = $_SESSION['full_name'] ?? 'System Admin';
$_SESSION['username']  = $_SESSION['username']  ?? 'admin';
$_SESSION['role']      = $_SESSION['role']      ?? 'Administrator';

if (!function_exists('e')) {
    function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}

// Fetch all rate codes
try {
    $stmt      = $pdo->query("SELECT * FROM rate_code ORDER BY rc_code ASC");
    $rateCodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    $rateCodes = [];
}

// Flash message
$flash = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

// CSRF token
$csrf = csrfToken();

// Helpers for active discount display
function discountBadge(array $rc): string {
    return match($rc['active_discount']) {
        'percent' => '<span class="badge badge-success">% Percent</span>',
        'value'   => '<span class="badge badge-info">₱ Fixed Value</span>',
        default   => '<span class="badge badge-inactive">None</span>',
    };
}

function activeDiscountLabel(array $rc): string {
    return match($rc['active_discount']) {
        'percent' => number_format((float)$rc['discount_percent'], 2) . '%',
        'value'   => '₱' . number_format((float)$rc['discount_value'], 2),
        default   => '—',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Code Management — COWASCO Waters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --white: #ffffff; --off-white: #f8f9fb; --border: #e8eaed; --border-focus: #1a1a2e;
            --text-primary: #0f0f1a; --text-secondary: #6b7280; --text-muted: #9ca3af;
            --accent: #1a1a2e; --accent-mid: #16213e; --accent-light: #e8f0fe;
            --success: #059669; --success-bg: #ecfdf5;
            --error: #dc2626;   --error-bg: #fef2f2;
            --warning: #d97706; --warning-bg: #fffbeb;
            --info: #2563eb;    --info-bg: #eff6ff;
            --radius-sm: 8px; --radius: 14px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow: 0 4px 16px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
        }
        html { height: 100%; font-size: 16px; }
        body { font-family: 'Sora', sans-serif; background: var(--off-white); color: var(--text-primary); min-height: 100vh; }
        .page { padding: 32px 32px 60px; animation: fadeIn .4s ease both; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        /* ---- Header ---- */
        .page-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .page-title { font-size: 1.5rem; font-weight: 700; letter-spacing: -.03em; }
        .page-subtitle { font-size: .875rem; color: var(--text-secondary); margin-top: 4px; }

        /* ---- Buttons ---- */
        .btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 18px; font-family: inherit; font-size: .8125rem; font-weight: 600; border-radius: var(--radius-sm); border: none; cursor: pointer; text-decoration: none; transition: transform .15s, box-shadow .15s, background .15s; }
        .btn svg { width: 15px; height: 15px; flex-shrink: 0; }
        .btn-primary { background: var(--accent); color: var(--white); }
        .btn-primary:hover { background: #0f0f1a; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(26,26,46,.2); }
        .btn-ghost { background: var(--white); color: var(--text-secondary); border: 1.5px solid var(--border); }
        .btn-ghost:hover { border-color: var(--border-focus); color: var(--text-primary); }
        .btn-sm { padding: 6px 12px; font-size: .75rem; }

        /* ---- Form controls ---- */
        .form-control { padding: 9px 12px; font-family: 'Sora', sans-serif; font-size: .8375rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); outline: none; transition: border-color .2s; width: 100%; }
        .form-control:focus { border-color: var(--border-focus); }
        .form-control[readonly] { background: var(--off-white); cursor: not-allowed; }
        .form-row { display: flex; gap: 12px; }
        .form-row .form-control { flex: 1; }

        /* ---- Card ---- */
        .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 24px; }
        .card-head { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 12px; }
        .card-head-title { font-size: 1rem; font-weight: 700; }

        /* ---- Table ---- */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .8rem; }
        thead tr { background: var(--off-white); }
        th { padding: 12px 18px; text-align: left; font-weight: 700; font-size: .72rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid var(--border); white-space: nowrap; }
        td { padding: 14px 18px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: var(--off-white); }

        /* ---- Badges ---- */
        .mono { font-family: 'JetBrains Mono', monospace; }
        .badge { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 20px; font-size: .68rem; font-weight: 700; letter-spacing: .02em; white-space: nowrap; }
        .badge-success  { background: var(--success-bg); color: var(--success); }
        .badge-info     { background: var(--info-bg);    color: var(--info); }
        .badge-warning  { background: var(--warning-bg); color: var(--warning); }
        .badge-inactive { background: var(--error-bg);   color: var(--error); }

        /* ---- Action buttons ---- */
        .action-btns { display: flex; gap: 8px; }
        .icon-btn { width: 28px; height: 28px; border-radius: 6px; border: 1.5px solid var(--border); background: var(--white); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-secondary); transition: all .2s; padding: 0; }
        .icon-btn:hover { border-color: var(--border-focus); color: var(--text-primary); }
        .icon-btn.danger:hover { border-color: var(--error); color: var(--error); background: var(--error-bg); }
        .icon-btn svg { width: 14px; height: 14px; }

        /* ---- Toggle group ---- */
        .toggle-group { display: inline-flex; border: 1.5px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; }
        .toggle-group button { padding: 5px 11px; font-family: 'Sora', sans-serif; font-size: .7rem; font-weight: 600; border: none; background: var(--white); color: var(--text-secondary); cursor: pointer; transition: background .15s, color .15s; border-right: 1px solid var(--border); }
        .toggle-group button:last-child { border-right: none; }
        .toggle-group button.active-pct { background: var(--success-bg); color: var(--success); }
        .toggle-group button.active-val { background: var(--info-bg);    color: var(--info); }
        .toggle-group button.active-none { background: var(--error-bg);  color: var(--error); }
        .toggle-group button:hover:not(.active-pct):not(.active-val):not(.active-none) { background: var(--off-white); color: var(--text-primary); }

        /* ---- Flash ---- */
        .flash { padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-weight: 600; font-size: .875rem; }
        .flash-success { background: var(--success-bg); color: var(--success); border: 1px solid #a7f3d0; }
        .flash-error   { background: var(--error-bg);   color: var(--error);   border: 1px solid #fecaca; }
        .flash-info    { background: var(--info-bg);    color: var(--info);    border: 1px solid #bfdbfe; }

        /* ---- Modal ---- */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,.5); display: none; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(3px); }
        .modal-overlay.active { display: flex; animation: fadeIn .2s; }
        .modal-content { background: var(--white); padding: 28px; border-radius: var(--radius); width: 100%; max-width: 460px; box-shadow: var(--shadow); position: relative; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
        .modal-title { font-size: 1.15rem; font-weight: 700; }
        .close-modal { background: none; border: none; cursor: pointer; color: var(--text-muted); }
        .close-modal:hover { color: var(--text-primary); }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: .8rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; }
        .form-group small { display: block; margin-top: 5px; font-size: .7rem; color: var(--text-muted); }
        .form-group select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 32px; }
        .divider { border: none; border-top: 1px solid var(--border); margin: 20px 0; }
        .discount-section-label { font-size: .75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 12px; }

        @media (max-width: 768px) {
            .page { padding: 20px 16px; }
            .card-head { flex-direction: column; align-items: flex-start; }
            .form-row { flex-direction: column; }
        }
    </style>
</head>
<body>

<?php require_once '../common/navbar.php'; ?>

<div class="main-content">
<main class="page">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Rate Code Management</h1>
            <p class="page-subtitle">Manage billing rate codes and configure per-code discounts</p>
        </div>
        <a href="configurations.php" class="btn btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Back to Configs
        </a>
    </div>

    <!-- FLASH MESSAGES -->
    <?php if (!empty($flash)): ?>
        <div class="flash flash-<?= e($flash['type'] ?? 'info') ?>">
            <?= e($flash['msg'] ?? '') ?>
        </div>
    <?php endif; ?>

    <!-- RATE CODES TABLE -->
    <div class="card">
        <div class="card-head">
            <div class="card-head-title">Rate Codes</div>
            <button class="btn btn-primary" onclick="openModal('createModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Rate Code
            </button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Discount %</th>
                        <th>Fixed Value (₱)</th>
                        <th>Active Discount</th>
                        <th>Effective Rate</th>
                        <th>Quick Toggle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rateCodes)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:24px; color:var(--text-muted);">
                                No rate codes found. Add one to get started.
                            </td>
                        </tr>
                    <?php else: foreach ($rateCodes as $rc): ?>
                    <tr>
                        <td><span class="badge badge-warning mono" style="font-size:.8rem;"><?= e($rc['rc_code']) ?></span></td>
                        <td style="font-weight:600; max-width:220px;"><?= e($rc['rc_name']) ?></td>
                        <td class="mono"><?= number_format((float)$rc['discount_percent'], 2) ?>%</td>
                        <td class="mono">₱<?= number_format((float)$rc['discount_value'], 2) ?></td>
                        <td><?= discountBadge($rc) ?></td>
                        <td class="mono" style="font-weight:600;"><?= activeDiscountLabel($rc) ?></td>

                        <!-- Quick Toggle -->
                        <td>
                            <div class="toggle-group">
                                <!-- % button -->
                                <form action="../process/applyDiscountToRateCode.php" method="POST" style="display:contents;">
                                    <input type="hidden" name="csrf_token"      value="<?= e($csrf) ?>">
                                    <input type="hidden" name="rc_id"           value="<?= $rc['rc_id'] ?>">
                                    <input type="hidden" name="active_discount" value="percent">
                                    <button type="submit"
                                            class="<?= $rc['active_discount'] === 'percent' ? 'active-pct' : '' ?>"
                                            title="Activate % discount"
                                            onclick="return confirm('Activate the percentage discount for this rate code?')">
                                        % Off
                                    </button>
                                </form>
                                <!-- ₱ button -->
                                <form action="../process/applyDiscountToRateCode.php" method="POST" style="display:contents;">
                                    <input type="hidden" name="csrf_token"      value="<?= e($csrf) ?>">
                                    <input type="hidden" name="rc_id"           value="<?= $rc['rc_id'] ?>">
                                    <input type="hidden" name="active_discount" value="value">
                                    <button type="submit"
                                            class="<?= $rc['active_discount'] === 'value' ? 'active-val' : '' ?>"
                                            title="Activate fixed-value discount"
                                            onclick="return confirm('Activate the fixed-value discount for this rate code?')">
                                        ₱ Off
                                    </button>
                                </form>
                                <!-- None button -->
                                <form action="../process/applyDiscountToRateCode.php" method="POST" style="display:contents;">
                                    <input type="hidden" name="csrf_token"      value="<?= e($csrf) ?>">
                                    <input type="hidden" name="rc_id"           value="<?= $rc['rc_id'] ?>">
                                    <input type="hidden" name="active_discount" value="none">
                                    <button type="submit"
                                            class="<?= $rc['active_discount'] === 'none' ? 'active-none' : '' ?>"
                                            title="Disable discount"
                                            onclick="return confirm('Disable the discount for this rate code?')">
                                        None
                                    </button>
                                </form>
                            </div>
                        </td>

                        <!-- Edit / Delete -->
                        <td>
                            <div class="action-btns">
                                <button class="icon-btn" title="Edit Rate Code"
                                        onclick="openEditModal(
                                            <?= $rc['rc_id'] ?>,
                                            '<?= addslashes(e($rc['rc_code'])) ?>',
                                            '<?= addslashes(e($rc['rc_name'])) ?>',
                                            <?= (float)$rc['discount_percent'] ?>,
                                            <?= (float)$rc['discount_value'] ?>,
                                            '<?= e($rc['active_discount']) ?>'
                                        )">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <form action="../process/deleteRateCode.php" method="POST" style="display:inline;"
                                      onsubmit="return confirm('Delete rate code <?= e($rc['rc_code']) ?>? This cannot be undone.');">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="rc_id"      value="<?= $rc['rc_id'] ?>">
                                    <button type="submit" class="icon-btn danger" title="Delete">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>
</div>

<!-- ==================== CREATE MODAL ==================== -->
<div class="modal-overlay" id="createModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">New Rate Code</h2>
            <button class="close-modal" onclick="closeModal('createModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="../process/createRateCode.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

            <div class="form-group">
                <label>Rate Code Letter</label>
                <input type="text" name="rc_code" class="form-control" maxlength="1"
                       placeholder="e.g., E" style="text-transform:uppercase;" required>
                <small>Single letter (A–Z). Must be unique.</small>
            </div>

            <div class="form-group">
                <label>Rate Code Name</label>
                <input type="text" name="rc_name" class="form-control"
                       placeholder="e.g., RESIDENTIAL AND REGULAR" style="text-transform:uppercase;" required>
            </div>

            <hr class="divider">
            <p class="discount-section-label">Discount Configuration</p>

            <div class="form-row">
                <div class="form-group" style="flex:1;">
                    <label>Discount Percent (%)</label>
                    <input type="number" name="discount_percent" class="form-control"
                           placeholder="0.00" step="0.01" min="0" max="100" value="0">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Fixed Value (₱)</label>
                    <input type="number" name="discount_value" class="form-control"
                           placeholder="0.00" step="0.01" min="0" value="0">
                </div>
            </div>

            <div class="form-group">
                <label>Activate Which Discount?</label>
                <select name="active_discount" class="form-control">
                    <option value="none">None (no discount applied)</option>
                    <option value="percent">Percentage discount</option>
                    <option value="value">Fixed-value discount</option>
                </select>
                <small>You can change this anytime using the quick toggle in the table.</small>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:10px;">
                Create Rate Code
            </button>
        </form>
    </div>
</div>

<!-- ==================== EDIT MODAL ==================== -->
<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Edit Rate Code</h2>
            <button class="close-modal" onclick="closeModal('editModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="../process/editRateCode.php" method="POST">
            <input type="hidden" name="csrf_token" id="edit_csrf"   value="<?= e($csrf) ?>">
            <input type="hidden" name="rc_id"       id="edit_rc_id">

            <div class="form-group">
                <label>Rate Code Letter</label>
                <input type="text" id="edit_rc_code" class="form-control" readonly>
                <small>The rate code letter cannot be changed.</small>
            </div>

            <div class="form-group">
                <label>Rate Code Name</label>
                <input type="text" name="rc_name" id="edit_rc_name" class="form-control"
                       style="text-transform:uppercase;" required>
            </div>

            <hr class="divider">
            <p class="discount-section-label">Discount Configuration</p>

            <div class="form-row">
                <div class="form-group" style="flex:1;">
                    <label>Discount Percent (%)</label>
                    <input type="number" name="discount_percent" id="edit_discount_percent"
                           class="form-control" step="0.01" min="0" max="100">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Fixed Value (₱)</label>
                    <input type="number" name="discount_value" id="edit_discount_value"
                           class="form-control" step="0.01" min="0">
                </div>
            </div>

            <div class="form-group">
                <label>Activate Which Discount?</label>
                <select name="active_discount" id="edit_active_discount" class="form-control">
                    <option value="none">None (no discount applied)</option>
                    <option value="percent">Percentage discount</option>
                    <option value="value">Fixed-value discount</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:10px;">
                Save Changes
            </button>
        </form>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }

    function openEditModal(rcId, rcCode, rcName, pct, val, activeDiscount) {
        document.getElementById('edit_rc_id').value              = rcId;
        document.getElementById('edit_rc_code').value            = rcCode;
        document.getElementById('edit_rc_name').value            = rcName;
        document.getElementById('edit_discount_percent').value   = pct;
        document.getElementById('edit_discount_value').value     = val;
        document.getElementById('edit_active_discount').value    = activeDiscount;
        openModal('editModal');
    }

    // Close modal on overlay click
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            e.target.classList.remove('active');
        }
    });

    // Auto-uppercase rc_code input in create form
    document.querySelector('input[name="rc_code"]').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
</script>

</body>
</html>