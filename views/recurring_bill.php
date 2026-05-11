<?php

require_once '../database/config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['full_name'] = $_SESSION['full_name'] ?? 'System Admin';
$_SESSION['username']  = $_SESSION['username']  ?? 'admin';
$_SESSION['role']      = $_SESSION['role']      ?? 'Administrator';

if (!function_exists('e')) {
    function e($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');

$pdo = getDB();

// Auto-run on every page load — safe, duplicate guard inside procedure
try {
    $pdo->exec("CALL Process_Recurring_Bills()");
} catch (PDOException $e) {
    error_log("Recurring Bill Error: " . $e->getMessage());
}

$plans         = [];
$subscriptions = [];
$members       = [];
$bill_codes    = [];

if (isset($pdo)) {
    try {
        // Fetch Active Members
        $mem_stmt = $pdo->query("
            SELECT pkey, CONCAT(lastname, ', ', firstname) AS full_name 
            FROM members 
            WHERE status = 'A' 
            ORDER BY lastname ASC
        ");
        $members = $mem_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Bill Codes
        $code_stmt = $pdo->query("
            SELECT code_id, code 
            FROM bill_codes 
            ORDER BY code ASC
        ");
        $bill_codes = $code_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all active plan rows — each row = one billing cycle
        // Ordered by bill_code then date so current shows before next
        $plan_stmt = $pdo->query("
            SELECT p.*, c.code
            FROM recurring_plans p
            JOIN bill_codes c ON p.bill_code_id = c.code_id
            WHERE LOWER(p.status) = 'active'
            ORDER BY p.bill_code_id ASC, p.start_date ASC
        ");
        $plans = $plan_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group plans by bill_code_id + frequency so we can label current vs next in the view
        $grouped_plans = [];
        foreach ($plans as $plan) {
            $key = $plan['bill_code_id'] . '_' . $plan['frequency'];
            $grouped_plans[$key][] = $plan;
        }

        // Fetch Account Subscriptions
        $sub_stmt = $pdo->query("
            SELECT 
                s.*,
                m.pkey AS accnt_no,
                CONCAT(m.lastname, ', ', m.firstname) AS member_name,
                p.amount AS plan_amount,
                p.frequency,
                c.code,
                current_p.start_date  AS current_billing_date,
                next_p.start_date     AS next_billing_date
            FROM recurring_subscriptions s
            JOIN members m ON s.member_id = m.pkey
            JOIN recurring_plans p ON s.plan_id = p.plan_id
            JOIN bill_codes c ON p.bill_code_id = c.code_id
            LEFT JOIN (
                SELECT bill_code_id, frequency, MAX(start_date) AS start_date
                FROM recurring_plans
                WHERE LOWER(status) = 'active' AND start_date <= CURDATE()
                GROUP BY bill_code_id, frequency
            ) current_p ON current_p.bill_code_id = p.bill_code_id
                       AND current_p.frequency    = p.frequency
            LEFT JOIN (
                SELECT bill_code_id, frequency, MIN(start_date) AS start_date
                FROM recurring_plans
                WHERE LOWER(status) = 'active' AND start_date > CURDATE()
                GROUP BY bill_code_id, frequency
            ) next_p ON next_p.bill_code_id = p.bill_code_id
                    AND next_p.frequency    = p.frequency
            ORDER BY s.created_at DESC
        ");
        $subscriptions = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Fetch Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recurring Billing — COWASCO Waters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --white: #ffffff;
            --off-white: #f8f9fb;
            --border: #e8eaed;
            --border-focus: #1a1a2e;
            --text-primary: #0f0f1a;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --accent: #1a1a2e;
            --success: #059669;
            --success-bg: #ecfdf5;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
            --warning: #d97706;
            --warning-bg: #fffbeb;
            --info: #0369a1;
            --info-bg: #f0f9ff;
            --radius-sm: 8px;
            --radius: 14px;
            --radius-lg: 20px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow-lg: 0 20px 60px rgba(0,0,0,.18);
        }
        body { font-family: 'Sora', sans-serif; background: var(--off-white); color: var(--text-primary); }

        .page { padding: 32px 32px 60px; max-width: 1400px; margin: 0 auto; animation: fadeIn .4s ease both; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .title { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
        .subtitle { font-size: .875rem; color: var(--text-secondary); margin-top: 4px; }

        .alert { padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: .875rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: var(--success-bg); border: 1px solid var(--success); color: var(--success); }
        .alert-danger  { background: var(--danger-bg);  border: 1px solid var(--danger);  color: var(--danger); }

        .filter-bar { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 16px 20px; display: flex; gap: 16px; align-items: center; margin-bottom: 24px; flex-wrap: wrap; box-shadow: var(--shadow-sm); }
        .filter-label { font-size: .75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .05em; }

        .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
        .card-title { font-size: 1.1rem; font-weight: 600; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full-width { grid-column: 1 / -1; }
        label { font-size: .75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .05em; }

        input, select { padding: 9px 12px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: inherit; font-size: .875rem; outline: none; background: var(--white); transition: border-color .2s; }
        input:focus, select:focus { border-color: var(--border-focus); }
        select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; appearance: none; }
        input[disabled] { background: var(--off-white); color: var(--text-secondary); cursor: not-allowed; }

        .btn-group { display: flex; gap: 12px; margin-top: 16px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 0 16px; height: 38px; border: none; border-radius: var(--radius-sm); font-family: inherit; font-size: .8rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: background .2s, border-color .2s, transform .1s; white-space: nowrap; }
        .btn:active { transform: scale(0.98); }
        .btn-primary   { background: var(--accent); color: var(--white); }
        .btn-primary:hover { background: #0f0f1a; box-shadow: 0 4px 12px rgba(26,26,46,.2); }
        .btn-secondary { background: var(--white); color: var(--text-primary); border: 1.5px solid var(--border); }
        .btn-secondary:hover { border-color: var(--border-focus); background: var(--off-white); }
        .icon-btn { width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid var(--border); background: var(--white); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-secondary); transition: all .2s; text-decoration: none; flex-shrink: 0; }
        .icon-btn:hover { border-color: var(--border-focus); color: var(--text-primary); background: var(--off-white); transform: translateY(-1px); }

        .toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; margin-top: 2px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--border); transition: .3s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,.2); }
        input:checked + .slider { background-color: var(--success); }
        input:checked + .slider:before { transform: translateX(20px); }

        .card-table-wrap { border: 1px solid var(--border); border-radius: var(--radius-sm); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .85rem; white-space: nowrap; }
        th { text-align: left; padding: 12px 16px; color: var(--text-secondary); border-bottom: 1.5px solid var(--border); font-weight: 600; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; }
        td { padding: 14px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: var(--off-white); }
        .mono { font-family: 'JetBrains Mono', monospace; font-size: .85rem; }

        /* Plan preview card inside modal */
        .plan-preview { background: var(--info-bg); border: 1.5px solid #bae6fd; border-radius: var(--radius-sm); padding: 14px 16px; margin-top: 4px; display: none; }
        .plan-preview.visible { display: flex; gap: 24px; flex-wrap: wrap; }
        .plan-preview-item { display: flex; flex-direction: column; gap: 2px; }
        .plan-preview-label { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--info); }
        .plan-preview-value { font-size: .9rem; font-weight: 700; color: var(--text-primary); font-family: 'JetBrains Mono', monospace; }

        .modal-overlay { position: fixed; inset: 0; background: rgba(10,10,20,0.55); z-index: 1000; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity .3s ease; backdrop-filter: blur(4px); padding: 16px; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-box { background: var(--white); border-radius: var(--radius-lg); width: 100%; max-width: 600px; box-shadow: var(--shadow-lg); transform: translateY(24px) scale(0.97); opacity: 0; transition: transform 0.35s, opacity 0.35s; display: flex; flex-direction: column; }
        .modal-overlay.active .modal-box { transform: translateY(0) scale(1); opacity: 1; }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 24px; }
        .modal-close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); padding: 0; line-height: 1; }
        .modal-close-btn:hover { color: var(--text-primary); }
    </style>
</head>
<body>

    <?php require_once '../common/navbar.php'; ?>

    <div class="main-content">
        <main class="page">
            <div class="header">
                <div>
                    <h1 class="title">Recurring Billing</h1>
                    <p class="subtitle">Automate fixed charges like Share Capital or Maintenance Fees.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?= e($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_msg'])): ?>
                <div class="alert alert-danger">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?= e($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?>
                </div>
            <?php endif; ?>

            <div class="filter-bar">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="filter-label">Filter By:</span>
                    <select style="width: 180px;">
                        <option value="all">All Subscribers</option>
                        <option value="individual">Individual Account</option>
                        <option value="zone">Zone / Sitio</option>
                        <option value="barangay">Barangay</option>
                    </select>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; margin-left: auto;">
                    <span class="filter-label">Frequency:</span>
                    <select style="width: 150px;">
                        <option value="all">All</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Quarterly">Quarterly</option>
                        <option value="Semi-Annually">Semi-Annually</option>
                        <option value="Annually">Annually</option>
                    </select>
                    <button class="btn btn-secondary">Apply Filter</button>
                </div>
            </div>

            <!-- SECTION 1: SUBSCRIBED ACCOUNTS -->
            <div class="card" style="padding: 0;">
                <div class="card-header" style="margin: 20px 24px 16px;">
                    <h2 class="card-title">Subscribed Accounts</h2>
                    <button class="btn btn-primary" onclick="openSubscribeModal()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                        Add Subscription
                    </button>
                </div>
                <div class="card-table-wrap" style="border: none; border-radius: 0 0 var(--radius) var(--radius);">
                    <table>
                        <thead>
                            <tr>
                                <th>Account No.</th>
                                <th>Account Name</th>
                                <th>Recurring Plan</th>
                                <th>Billing Amount</th>
                                <th>Enrolled Date</th>
                                <th>Current Billing Date</th>
                                <th>Next Billing Date</th>
                                <th style="text-align:center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($subscriptions) && isset($pdo)): ?>
                                <tr><td colspan="9" style="text-align:center; padding:20px; color:var(--text-muted);">No active subscriptions found.</td></tr>
                            <?php elseif (!isset($pdo)): ?>
                                <tr><td colspan="9" style="text-align:center; padding:20px; color:var(--danger);"><strong>Database Connection Missing.</strong></td></tr>
                            <?php else: ?>
                                <?php foreach ($subscriptions as $sub): ?>
                                    <tr>
                                        <td class="mono" style="color:var(--text-secondary);"><?= e($sub['accnt_no']) ?></td>
                                        <td style="font-weight:600;"><?= e($sub['member_name']) ?></td>
                                        <td style="color:var(--text-secondary);"><?= e($sub['code']) ?> (<?= e($sub['frequency']) ?>)</td>
                                        <td class="mono" style="font-weight:700;">₱ <?= number_format($sub['plan_amount'], 2) ?></td>
                                        <td class="mono"><?= date('m/d/Y', strtotime($sub['start_date'])) ?></td>
                                        <td class="mono"><?= $sub['current_billing_date'] ? date('m/d/Y', strtotime($sub['current_billing_date'])) : '—' ?></td>
                                        <td class="mono" style="color:var(--accent); font-weight:600;">
                                            <?= $sub['next_billing_date'] ? date('m/d/Y', strtotime($sub['next_billing_date'])) : '—' ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <form action="../process/removeMemberFromRecurringPlan.php" method="POST" style="display:inline;" id="form_sub_<?= $sub['subscription_id'] ?>">
                                                <input type="hidden" name="subscription_id" value="<?= $sub['subscription_id'] ?>">
                                                <input type="hidden" name="status" value="<?= $sub['status'] === 'active' ? 'inactive' : 'active' ?>">
                                                <label class="toggle-switch">
                                                    <input type="checkbox" <?= $sub['status'] === 'active' ? 'checked' : '' ?> onchange="document.getElementById('form_sub_<?= $sub['subscription_id'] ?>').submit();">
                                                    <span class="slider"></span>
                                                </label>
                                            </form>
                                        </td>
                                        <td style="text-align:right;">
                                            <div style="display:flex; gap:8px; justify-content:flex-end;"></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 2: GLOBAL RECURRING PLANS -->
            <div class="card" style="padding: 0; margin-top: 40px;">
                <div class="card-header" style="margin: 20px 24px 16px;">
                    <div>
                        <h2 class="card-title">Global Recurring Plans</h2>
                        <div style="font-size:.8rem; color:var(--text-muted); margin-top:4px;">Each group shows the current and next billing cycle row.</div>
                    </div>
                    <button class="btn btn-secondary" onclick="openPlanModal()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Create New Plan
                    </button>
                </div>
                <div class="card-table-wrap" style="border: none; border-radius: 0 0 var(--radius) var(--radius);">
                    <table>
                        <thead>
                            <tr>
                                <th>Plan Type (Bill Code)</th>
                                <th>Base Amount</th>
                                <th>Frequency</th>
                                <th>Billing Date</th>
                                <th style="text-align:center;">Status</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($grouped_plans) && isset($pdo)): ?>
                                <tr><td colspan="6" style="text-align:center; padding:20px; color:var(--text-muted);">No global plans defined.</td></tr>
                            <?php else: ?>
                                <?php foreach ($grouped_plans as $key => $rows): ?>
                                    <?php foreach ($rows as $index => $plan): ?>
                                        <?php
                                            $isFirst   = ($index === 0);
                                            $isCurrent = $plan['start_date'] <= date('Y-m-d');
                                        ?>
                                        <tr>
                                            <td style="font-weight:600;"><?= e($plan['code']) ?></td>
                                            <td class="mono" style="font-weight:700;">₱ <?= number_format($plan['amount'], 2) ?></td>
                                            <td><?= e($plan['frequency']) ?></td>
                                            <td class="mono" style="<?= !$isCurrent ? 'color:var(--accent); font-weight:600;' : '' ?>">
                                                <?= date('m/d/Y', strtotime($plan['start_date'])) ?>
                                            </td>
                                            <td style="text-align:center;">
                                                <form action="../process/editRecurringPlan.php" method="POST" style="display:inline;" id="form_plan_<?= $plan['plan_id'] ?>">
                                                    <input type="hidden" name="plan_id" value="<?= $plan['plan_id'] ?>">
                                                    <input type="hidden" name="status" value="<?= $plan['status'] === 'active' ? 'inactive' : 'active' ?>">
                                                    <label class="toggle-switch">
                                                        <input type="checkbox" <?= $plan['status'] === 'active' ? 'checked' : '' ?> onchange="document.getElementById('form_plan_<?= $plan['plan_id'] ?>').submit();">
                                                        <span class="slider"></span>
                                                    </label>
                                                </form>
                                            </td>
                                            <td style="text-align:right;">
                                                <div style="display:flex; gap:8px; justify-content:flex-end;">
                                                    <?php if ($isFirst): ?>
                                                        <form action="../process/deleteRecurringPlan.php" method="POST" onsubmit="return confirm('WARNING: Deleting this plan will permanently delete ALL member subscriptions tied to it. Are you sure?');">
                                                            <input type="hidden" name="plan_id" value="<?= $plan['plan_id'] ?>">
                                                            <button type="submit" class="icon-btn" title="Delete Plan" style="color:var(--danger); border-color:#fca5a5;">
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <!-- Spacer between groups -->
                                    <tr style="height: 8px; background: var(--off-white);"><td colspan="6" style="border:none;"></td></tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- ADD PLAN MODAL -->
    <div class="modal-overlay" id="planModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 class="title" style="font-size:1.25rem;">Create Global Plan</h2>
                <button class="modal-close-btn" onclick="closePlanModal()">×</button>
            </div>
            <div class="modal-body">
                <form action="../process/addRecurringPlan.php" method="POST">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Bill Code (Charge Type)</label>
                            <select name="bill_code_id" required>
                                <option value="" disabled selected>Select Item...</option>
                                <?php foreach ($bill_codes as $code): ?>
                                    <option value="<?= e($code['code_id']) ?>"><?= e($code['code']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Base Amount (₱)</label>
                            <input type="number" step="0.01" name="amount" class="mono" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label>Frequency</label>
                            <select name="frequency" required>
                                <option value="Monthly">Monthly</option>
                                <option value="Quarterly">Quarterly</option>
                                <option value="Semi-Annually">Semi-Annually</option>
                                <option value="Annually">Annually</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label>Global Start Date</label>
                            <input type="date" name="start_date" id="planStartDate" required>
                        </div>
                    </div>
                    <div class="btn-group" style="justify-content:flex-end; margin-top:16px;">
                        <button type="button" class="btn btn-secondary" onclick="closePlanModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ADD SUBSCRIPTION MODAL -->
    <div class="modal-overlay" id="subscribeModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 class="title" style="font-size:1.25rem;">Assign Subscription</h2>
                <button class="modal-close-btn" onclick="closeSubscribeModal()">×</button>
            </div>
            <div class="modal-body">
                <form action="../process/enrollMemberToRecurringPlan.php" method="POST">
                    <div class="form-grid">

                        <div class="form-group full-width">
                            <label>Consumer Account</label>
                            <select name="member_id" required>
                                <option value="" disabled selected>Search & Select Member...</option>
                                <?php foreach ($members as $mem): ?>
                                    <option value="<?= e($mem['pkey']) ?>"><?= e($mem['pkey']) ?> — <?= e($mem['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label>Recurring Plan</label>
                            <select name="plan_id" id="planSelect" required onchange="updatePlanPreview(this)">
                                <option value="" disabled selected>Select a plan...</option>
                                <?php foreach ($plans as $plan):
                                    $startFormatted = date('m/d/Y', strtotime($plan['start_date']));
                                ?>
                                    <option value="<?= e($plan['plan_id']) ?>"
                                            data-amount="<?= e(number_format($plan['amount'], 2)) ?>"
                                            data-frequency="<?= e($plan['frequency']) ?>"
                                            data-start="<?= e($startFormatted) ?>">
                                        <?= e($plan['code']) ?> — <?= e($plan['frequency']) ?> — ₱<?= number_format($plan['amount'], 2) ?> — starts <?= $startFormatted ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <!-- Plan preview card shown after selection -->
                            <div class="plan-preview" id="planPreview">
                                <div class="plan-preview-item">
                                    <span class="plan-preview-label">Amount</span>
                                    <span class="plan-preview-value" id="previewAmount">—</span>
                                </div>
                                <div class="plan-preview-item">
                                    <span class="plan-preview-label">Frequency</span>
                                    <span class="plan-preview-value" id="previewFrequency">—</span>
                                </div>
                                <div class="plan-preview-item">
                                    <span class="plan-preview-label">Billing Starts</span>
                                    <span class="plan-preview-value" id="previewStart">—</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label>Enrollment Date</label>
                            <input type="date" name="start_date" id="subStartDate" required>
                            <span style="font-size:.72rem; color:var(--text-muted); margin-top:2px;">Date this account is officially enrolled. Billing follows the plan's schedule.</span>
                        </div>

                    </div>

                    <div class="btn-group" style="justify-content:flex-end; margin-top:16px;">
                        <button type="button" class="btn btn-secondary" onclick="closeSubscribeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Subscription</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('planStartDate').value = today;
        document.getElementById('subStartDate').value  = today;

        function openPlanModal()       { document.getElementById('planModal').classList.add('active'); }
        function closePlanModal()      { document.getElementById('planModal').classList.remove('active'); }
        function openSubscribeModal()  { document.getElementById('subscribeModal').classList.add('active'); }
        function closeSubscribeModal() { document.getElementById('subscribeModal').classList.remove('active'); }

        function updatePlanPreview(selectEl) {
            const opt = selectEl.options[selectEl.selectedIndex];
            if (!opt || !opt.value) return;

            document.getElementById('previewAmount').textContent    = '₱ ' + opt.dataset.amount;
            document.getElementById('previewFrequency').textContent = opt.dataset.frequency;
            document.getElementById('previewStart').textContent     = opt.dataset.start;
            document.getElementById('planPreview').classList.add('visible');
        }
    </script>
</body>
</html>