<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../database/config.php';

$pdo = getDB();

$_SESSION['username'] = $_SESSION['username'] ?? 'admin';

if (!function_exists('e')) {
    function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}

// 1. Fetch Rate Codes for Dropdowns
try {
    $stmtRC = $pdo->query("SELECT * FROM rate_code ORDER BY rc_code ASC");
    $rateCodes = $stmtRC->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $rateCodes = []; }

// 2. Fetch Water Meter Members (Rates)
try {
    $stmt = $pdo->query("
        SELECT w.*, r.rc_code, r.rc_name 
        FROM water_meter_members w 
        JOIN rate_code r ON w.rc_id = r.rc_id 
        ORDER BY r.rc_code ASC, w.from_cb ASC
    ");
    $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $rates = []; }

// 3. Fetch Water Meter Discounts (Allowances)
try {
    $stmtD = $pdo->query("SELECT * FROM water_meter_discounts ORDER BY wmd_name ASC");
    $discounts = $stmtD->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $discounts = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water Meter Config — COWASCO Waters</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --white: #ffffff; --off-white: #f8f9fb; --border: #e8eaed; --border-focus: #1a1a2e;
            --text-primary: #0f0f1a; --text-secondary: #6b7280; --text-muted: #9ca3af;
            --accent: #1a1a2e; --success: #059669; --success-bg: #ecfdf5;
            --warning: #d97706; --warning-bg: #fffbeb; --danger: #dc2626; --danger-bg: #fef2f2;
            --info: #2563eb; --info-bg: #eff6ff; --radius: 12px; --radius-sm: 8px;
        }
        body { font-family: 'Sora', sans-serif; background: var(--off-white); color: var(--text-primary); min-height: 100vh;}
        .page { padding: 28px; max-width: 1200px; margin: 0 auto; }
        .page-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 4px; }
        .page-subtitle { font-size: .85rem; color: var(--text-muted); margin-bottom: 24px; }

        .tabs { display: flex; gap: 16px; border-bottom: 2px solid var(--border); margin-bottom: 24px; }
        .tab-btn { background: none; border: none; padding: 12px 16px; font-family: inherit; font-size: .9rem; font-weight: 600; color: var(--text-secondary); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; }
        .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; animation: fadeIn .3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 24px; }
        .card-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; align-items: end; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-label { font-size: .75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; }
        .form-input { padding: 10px 14px; font-family: inherit; font-size: .85rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); outline: none; width: 100%; }
        .form-input:focus { border-color: var(--border-focus); }
        .mono { font-family: 'JetBrains Mono', monospace; }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 18px; border-radius: var(--radius-sm); border: none; font-size: .85rem; font-weight: 600; cursor: pointer; }
        .btn-primary { background: var(--accent); color: var(--white); }
        .icon-btn { width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid var(--border); background: var(--white); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-secondary); }
        .icon-btn:hover { border-color: var(--border-focus); color: var(--text-primary); }

        table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        th { padding: 12px 16px; text-align: left; font-size: .75rem; color: var(--text-secondary); text-transform: uppercase; background: var(--off-white); border-bottom: 1.5px solid var(--border); }
        td { padding: 14px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .badge { display: inline-flex; padding: 4px 10px; border-radius: 20px; font-size: .7rem; font-weight: 700; text-transform: uppercase; }
        .badge-fixed { background: var(--info-bg); color: var(--info); }
        .badge-variable { background: var(--warning-bg); color: var(--warning); border: 1px solid #fcd34d; }

        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: var(--white); padding: 24px; border-radius: var(--radius); width: 100%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .close-modal { background: none; border: none; cursor: pointer; font-size: 1.2rem; }
    </style>
</head>
<body>

    <?php require_once '../common/navbar.php'; ?>

    <div class="main-content">
        <main class="page">
            <h1 class="page-title">Water Meter Configurations</h1>
            <p class="page-subtitle">Manage billing tiers (rates) and special privileges (discounts & allowances).</p>

            <?php if(isset($_SESSION['flash']['success'])): ?>
                <div style="background:var(--success-bg); color:var(--success); padding:12px 16px; border-radius:var(--radius-sm); border:1px solid #a7f3d0; margin-bottom:20px; font-weight:600; font-size:.85rem;">
                    <?= e($_SESSION['flash']['success']); unset($_SESSION['flash']['success']); ?>
                </div>
            <?php endif; ?>
            <?php if(isset($_SESSION['flash']['error'])): ?>
                <div style="background:var(--danger-bg); color:var(--danger); padding:12px 16px; border-radius:var(--radius-sm); border:1px solid #fecaca; margin-bottom:20px; font-weight:600; font-size:.85rem;">
                    <?= e($_SESSION['flash']['error']); unset($_SESSION['flash']['error']); ?>
                </div>
            <?php endif; ?>

            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('rates', this)">Water Rates (Tiers)</button>
                <button class="tab-btn" onclick="switchTab('discounts', this)">Discounts & Allowances</button>
            </div>

            <div id="tab-rates" class="tab-panel active">
                <div class="card">
                    <h2 class="card-title">Add New Rate Tier</h2>
                    <form action="../process/addWaterMeterMember.php" method="POST">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label class="form-label">Member Type / Rate Code</label>
                                <select name="rc_id" class="form-input" required>
                                    <?php foreach ($rateCodes as $rc): ?>
                                        <option value="<?= $rc['rc_id'] ?>"><?= e($rc['rc_code']) ?> - <?= e($rc['rc_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">From (m³)</label>
                                <input type="number" name="from_cb" class="form-input mono" placeholder="0" required min="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">To (m³)</label>
                                <input type="number" name="to_cb" class="form-input mono" placeholder="Leave blank if Infinity" min="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Amount (₱)</label>
                                <input type="number" name="amount" class="form-input mono" placeholder="100.00" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Bill Type</label>
                                <select name="bill_type" class="form-input" required>
                                    <option value="FIXED">FIXED</option>
                                    <option value="VARIABLE" selected>VARIABLE</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Rate Tier</button>
                    </form>
                </div>

                <div class="card" style="padding: 0;">
                    <h2 class="card-title" style="margin: 24px 24px 16px;">Active Water Rates</h2>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rate Code</th>
                                    <th>Range (m³)</th>
                                    <th>Amount Base</th>
                                    <th>Bill Type</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rates as $r): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 700;"><?= e($r['rc_code']) ?></div>
                                        <div style="font-size: .75rem; color: var(--text-muted);"><?= e($r['rc_name']) ?></div>
                                    </td>
                                    <td class="mono"><?= e($r['from_cb']) ?> — <?= is_null($r['to_cb']) ? '∞' : e($r['to_cb']) ?></td>
                                    <td class="mono">₱ <?= number_format($r['amount'], 2) ?></td>
                                    <td><span class="badge <?= $r['bill_type'] === 'FIXED' ? 'badge-fixed' : 'badge-variable' ?>"><?= $r['bill_type'] ?></span></td>
                                    <td>
                                        <div style="display:flex; gap:8px; justify-content: flex-end;">
                                            <button class="icon-btn" onclick="openEditRateModal(<?= $r['wmm_id'] ?>, <?= $r['rc_id'] ?>, <?= $r['from_cb'] ?>, <?= $r['to_cb'] ?? 'null' ?>, <?= $r['amount'] ?>, '<?= $r['bill_type'] ?>')">✎</button>
                                            <form action="../process/deleteWaterMeterMember.php" method="POST" onsubmit="return confirm('Delete this tier?');">
                                                <input type="hidden" name="wmm_id" value="<?= $r['wmm_id'] ?>">
                                                <button type="submit" class="icon-btn" style="color: var(--danger); border-color: #fca5a5;">✖</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="tab-discounts" class="tab-panel">
                <div class="card">
                    <h2 class="card-title">Add Discount / Allowance Group</h2>
                    <form action="../process/addWaterMeterDiscount.php" method="POST">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label class="form-label">Discount Name (e.g., Board of Directors)</label>
                                <input type="text" name="wmd_name" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Free Water (m³)</label>
                                <input type="number" name="free_water_m3" class="form-input mono" value="0" min="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Active Mode</label>
                                <select name="active_discount" class="form-input">
                                    <option value="none">None (Free m³ Only)</option>
                                    <option value="percent">Percentage Discount</option>
                                    <option value="fixed">Fixed Deduction</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Percent Rate (%)</label>
                                <input type="number" name="percent_discount" class="form-input mono" value="0.00" step="0.01">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fixed Rate (₱)</label>
                                <input type="number" name="fixed_discount" class="form-input mono" value="0.00" step="0.01">
                            </div>
                            <div class="form-group full">
                                <label class="form-label">Max m³ Limit for Discount</label>
                                <input type="number" name="max_m3_for_discount" class="form-input mono" placeholder="Leave blank if unlimited">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Discount Group</button>
                    </form>
                </div>

                <div class="card" style="padding: 0;">
                    <h2 class="card-title" style="margin: 24px 24px 16px;">Active Discounts & Allowances</h2>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Group Name</th>
                                    <th>Free m³</th>
                                    <th>Discount Applied</th>
                                    <th>Max Limit (m³)</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($discounts as $d): ?>
                                <tr>
                                    <td style="font-weight: 700;"><?= e($d['wmd_name']) ?></td>
                                    <td class="mono"><?= e($d['free_water_m3']) ?> m³</td>
                                    <td>
                                        <div style="font-size: .8rem;">Mode: <strong style="text-transform:uppercase"><?= $d['active_discount'] ?></strong></div>
                                        <div class="mono" style="font-size: .8rem; color: var(--text-secondary);">
                                            <?= $d['active_discount'] === 'percent' ? number_format($d['percent_discount'], 2) . '%' : '' ?>
                                            <?= $d['active_discount'] === 'fixed' ? '₱' . number_format($d['fixed_discount'], 2) : '' ?>
                                            <?= $d['active_discount'] === 'none' ? '—' : '' ?>
                                        </div>
                                    </td>
                                    <td class="mono"><?= is_null($d['max_m3_for_discount']) ? '∞' : e($d['max_m3_for_discount']).' m³' ?></td>
                                    <td>
                                        <div style="display:flex; gap:8px; justify-content: flex-end;">
                                            <button class="icon-btn" onclick="openEditDiscModal(<?= $d['wmdiscount_id'] ?>, '<?= addslashes(e($d['wmd_name'])) ?>', <?= $d['free_water_m3'] ?>, '<?= $d['active_discount'] ?>', <?= $d['percent_discount'] ?>, <?= $d['fixed_discount'] ?>, <?= $d['max_m3_for_discount'] ?? 'null' ?>)">✎</button>
                                            <form action="../process/deleteWaterMeterDiscount.php" method="POST" onsubmit="return confirm('Delete this discount group?');">
                                                <input type="hidden" name="wmdiscount_id" value="<?= $d['wmdiscount_id'] ?>">
                                                <button type="submit" class="icon-btn" style="color: var(--danger); border-color: #fca5a5;">✖</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <div class="modal-overlay" id="editRateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="font-size: 1.1rem; font-weight:700;">Edit Rate Tier</h2>
                <button class="close-modal" onclick="closeModal('editRateModal')">✖</button>
            </div>
            <form action="../process/editWaterMeterMember.php" method="POST">
                <input type="hidden" name="wmm_id" id="edit_wmm_id">
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Rate Code</label>
                        <select name="rc_id" id="edit_rc_id" class="form-input" required>
                            <?php foreach ($rateCodes as $rc): ?>
                                <option value="<?= $rc['rc_id'] ?>"><?= e($rc['rc_code']) ?> - <?= e($rc['rc_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label">From (m³)</label><input type="number" name="from_cb" id="edit_from_cb" class="form-input mono" required></div>
                    <div class="form-group"><label class="form-label">To (m³)</label><input type="number" name="to_cb" id="edit_to_cb" class="form-input mono"></div>
                    <div class="form-group"><label class="form-label">Amount (₱)</label><input type="number" name="amount" id="edit_amount" class="form-input mono" step="0.01" required></div>
                    <div class="form-group"><label class="form-label">Bill Type</label>
                        <select name="bill_type" id="edit_bill_type" class="form-input" required>
                            <option value="FIXED">FIXED</option><option value="VARIABLE">VARIABLE</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Update Tier</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="editDiscModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="font-size: 1.1rem; font-weight:700;">Edit Discount Group</h2>
                <button class="close-modal" onclick="closeModal('editDiscModal')">✖</button>
            </div>
            <form action="../process/editWaterMeterDiscount.php" method="POST">
                <input type="hidden" name="wmdiscount_id" id="edit_wmdiscount_id">
                <div class="form-grid">
                    <div class="form-group full"><label class="form-label">Discount Name</label><input type="text" name="wmd_name" id="edit_wmd_name" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Free Water (m³)</label><input type="number" name="free_water_m3" id="edit_free_water" class="form-input mono" required></div>
                    <div class="form-group"><label class="form-label">Active Mode</label>
                        <select name="active_discount" id="edit_active_discount" class="form-input">
                            <option value="none">None</option><option value="percent">Percent</option><option value="fixed">Fixed</option>
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label">Percent Rate (%)</label><input type="number" name="percent_discount" id="edit_percent_discount" class="form-input mono" step="0.01"></div>
                    <div class="form-group"><label class="form-label">Fixed Rate (₱)</label><input type="number" name="fixed_discount" id="edit_fixed_discount" class="form-input mono" step="0.01"></div>
                    <div class="form-group full"><label class="form-label">Max Limit (m³)</label><input type="number" name="max_m3_for_discount" id="edit_max_m3" class="form-input mono"></div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Update Discount</button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(id, el) {
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-'+id).classList.add('active');
            el.classList.add('active');
        }
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        function openEditRateModal(id, rc, from, to, amt, bill) {
            document.getElementById('edit_wmm_id').value = id;
            document.getElementById('edit_rc_id').value = rc;
            document.getElementById('edit_from_cb').value = from;
            document.getElementById('edit_to_cb').value = to !== null ? to : '';
            document.getElementById('edit_amount').value = amt;
            document.getElementById('edit_bill_type').value = bill;
            openModal('editRateModal');
        }

        function openEditDiscModal(id, name, free, active, pct, fix, max) {
            document.getElementById('edit_wmdiscount_id').value = id;
            document.getElementById('edit_wmd_name').value = name;
            document.getElementById('edit_free_water').value = free;
            document.getElementById('edit_active_discount').value = active;
            document.getElementById('edit_percent_discount').value = pct;
            document.getElementById('edit_fixed_discount').value = fix;
            document.getElementById('edit_max_m3').value = max !== null ? max : '';
            openModal('editDiscModal');
        }
    </script>
</body>
</html>