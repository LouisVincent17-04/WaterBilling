<?php

require_once '../database/config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['full_name'] = $_SESSION['full_name'] ?? 'System Admin';
$_SESSION['username']  = $_SESSION['username'] ?? 'admin';
$_SESSION['role']      = $_SESSION['role'] ?? 'Administrator';

if (!function_exists('e')) {
    function e($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');

// ==========================================
// DYNAMIC DATA FETCHING
// ==========================================
$members = [];
$bill_codes = [];
$active_installments = [];
$all_schedules = []; 

$pdo = getDB();

if (isset($pdo)) {
    try {
        $mem_stmt = $pdo->query("SELECT pkey, CONCAT(lastname, ', ', firstname) AS full_name FROM members WHERE status = 'A' ORDER BY lastname ASC");
        $members = $mem_stmt->fetchAll(PDO::FETCH_ASSOC);

        $code_stmt = $pdo->query("SELECT code_id, code, default_amount FROM bill_codes ORDER BY code ASC");
        $bill_codes = $code_stmt->fetchAll(PDO::FETCH_ASSOC);

        $inst_stmt = $pdo->query("
            SELECT i.*, m.pkey AS accnt_no, CONCAT(m.lastname, ', ', m.firstname) AS member_name, c.code
            FROM installment_bills i
            JOIN members m ON i.member_id = m.pkey
            JOIN bill_codes c ON i.bill_code_id = c.code_id
            ORDER BY i.created_at DESC
        ");
        $active_installments = $inst_stmt->fetchAll(PDO::FETCH_ASSOC);

        $sched_stmt = $pdo->query("
            SELECT s.*, u.username AS marked_by_name 
            FROM installment_schedules s 
            LEFT JOIN users u ON s.marked_by = u.id 
            ORDER BY s.due_date ASC
        ");
        while ($row = $sched_stmt->fetch(PDO::FETCH_ASSOC)) {
            $all_schedules[$row['installment_id']][] = $row;
        }

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
    <title>Installment Bills — COWASCO Waters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
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
            --radius-sm: 8px;
            --radius: 14px;
            --radius-lg: 20px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow-lg: 0 20px 60px rgba(0,0,0,.18);
        }
        body { font-family: 'Sora', sans-serif; background: var(--off-white); color: var(--text-primary); }
        
        .page { padding: 32px 32px 60px; max-width: 1400px; margin: 0 auto; animation: fadeIn .4s ease both; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        .header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .title { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
        .subtitle { font-size: .875rem; color: var(--text-secondary); margin-top: 4px; }
        
        .alert { padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: .875rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: var(--success-bg); border: 1px solid var(--success); color: var(--success); }
        .alert-danger { background: var(--danger-bg); border: 1px solid var(--danger); color: var(--danger); }

        .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
        .card-title { font-size: 1.1rem; font-weight: 600; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        label { font-size: .75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .05em; }
        input, select { padding: 9px 12px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: inherit; font-size: .875rem; outline: none; background: var(--white); transition: border-color .2s; }
        input:focus, select:focus { border-color: var(--border-focus); }
        select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; appearance: none; }
        input[readonly] { background: var(--off-white); color: var(--text-secondary); cursor: not-allowed; font-weight: 600; }
        
        .editable-amount { border: 1.5px solid var(--border); border-radius: 6px; padding: 6px 10px; width: 130px; font-family: 'JetBrains Mono', monospace; font-weight: 600; outline: none; transition: border-color 0.2s; }
        .editable-amount:focus { border-color: var(--accent); }

        .btn-group { display: flex; gap: 12px; margin-top: 16px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 0 16px; height: 38px; border: none; border-radius: var(--radius-sm); font-family: inherit; font-size: .8rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: background .2s, border-color .2s, transform .1s; white-space: nowrap; }
        .btn:active { transform: scale(0.98); }
        .btn-primary { background: var(--accent); color: var(--white); }
        .btn-primary:hover { background: #0f0f1a; }
        .btn-secondary { background: var(--white); color: var(--text-primary); border: 1.5px solid var(--border); }
        .btn-secondary:hover { border-color: var(--border-focus); background: var(--off-white); }
        .btn-success { background: var(--success); color: var(--white); }
        
        .icon-btn { width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid var(--border); background: var(--white); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-secondary); transition: all .2s; text-decoration: none; flex-shrink: 0; }
        .icon-btn:hover { border-color: var(--border-focus); color: var(--text-primary); background: var(--off-white); transform: translateY(-1px); }
        .icon-btn svg { width: 14px; height: 14px; }

        .card-table-wrap { border: 1px solid var(--border); border-radius: var(--radius-sm); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .85rem; white-space: nowrap; }
        th { text-align: left; padding: 12px 16px; color: var(--text-secondary); border-bottom: 1.5px solid var(--border); font-weight: 600; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; }
        td { padding: 14px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: var(--off-white); }
        .mono { font-family: 'JetBrains Mono', monospace; font-size: .8rem; }
        
        .badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 20px; font-size: .68rem; font-weight: 700; letter-spacing: .02em; text-transform: uppercase; }
        .badge-success { background: var(--success-bg); color: var(--success); }
        .badge-danger { background: var(--danger-bg); color: var(--danger); }
        .badge-warning { background: var(--warning-bg); color: var(--warning); border: 1px solid #fcd34d; }
        .badge-cancelled { background: var(--border); color: var(--text-secondary); }

        .preview-container { background: var(--off-white); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 16px; display: none; }
        .preview-container.active { display: block; animation: fadeIn 0.3s ease; }
        
        .modal-overlay { position: fixed; inset: 0; background: rgba(10,10,20,0.55); z-index: 1000; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity .3s ease; backdrop-filter: blur(4px); padding: 16px; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-box { background: var(--white); border-radius: var(--radius-lg); width: 100%; max-width: 400px; box-shadow: var(--shadow-lg); transform: translateY(24px) scale(0.97); opacity: 0; transition: transform 0.35s, opacity 0.35s; display: flex; flex-direction: column; }
        .modal-overlay.active .modal-box { transform: translateY(0) scale(1); opacity: 1; }
        .modal-box.large { max-width: 900px; } 
        
        .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 24px; max-height: 70vh; overflow-y: auto; }
        .modal-close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); padding: 0; line-height: 1; }
        .modal-close-btn:hover { color: var(--text-primary); }
        
        /* Slight override to make flatpickr input look consistent with existing styles */
        .flatpickr-input { font-family: 'JetBrains Mono', monospace; font-size: .875rem; }
    </style>
</head>
<body>

    <?php require_once '../common/navbar.php'; ?>

    <div class="main-content">
        <main class="page">
            <div class="header">
                <div>
                    <h1 class="title">Installment Bills</h1>
                    <p class="subtitle">Manage amortized payments, flexible schedules, and bill types</p>
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

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Create Installment Contract</h2>
                </div>
                
                <form id="scheduleForm" action="../process/addInstallmentBill.php" method="POST">
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Consumer Account</label>
                            <select name="member_id" class="form-input" required>
                                <option value="" disabled selected>Search & Select Member...</option>
                                <?php foreach ($members as $mem): ?>
                                    <option value="<?= e($mem['pkey']) ?>">
                                        <?= e($mem['pkey']) ?> — <?= e($mem['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="height: 1px; background: var(--border); margin: 20px 0;"></div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Bill Code</label>
                            <select name="bill_code_id" id="billCodeSelect" class="form-input" required onchange="autofillAmount()">
                                <option value="" disabled selected>Select Item...</option>
                                <?php foreach ($bill_codes as $code): ?>
                                    <option value="<?= e($code['code_id']) ?>" data-amount="<?= e($code['default_amount'] ?? 0) ?>">
                                        <?= e($code['code']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Total Amount (₱)</label>
                            <input type="number" name="total_amount" id="totalAmount" placeholder="0.00" step="0.01" class="form-input mono" required>
                        </div>
                        <div class="form-group">
                            <label>Term (Total Months)</label>
                            <input type="number" name="term" id="term" value="6" min="1" class="form-input mono" oninput="updatePaymentModes()" required>
                        </div>
                        <div class="form-group">
                            <label>Payment Mode</label>
                            <select name="payment_mode" id="payMode" class="form-input" required>
                                </select>
                        </div>
                        <div class="form-group">
                            <label>Amortization Type</label>
                            <select name="amortization_type" id="amortType" class="form-input" required onchange="generateSchedule()">
                                <option value="fixed">Fixed</option>
                                <option value="flexible">Flexible</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="text" name="start_date" id="startDate" class="form-input" placeholder="mm/dd/yyyy" required>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="generateSchedule()">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            Generate Schedule Preview
                        </button>
                    </div>

                    <div id="schedulePreview" class="preview-container" style="margin-top: 24px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
                            <h3 style="font-size: 1rem;">Amortization Schedule</h3>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <span id="scheduleTotalBadge" style="font-size: .85rem; font-weight: 700;"></span>
                                <span id="scheduleStatusBadge" style="font-size: .75rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; background: var(--white); border: 1px solid var(--border);"></span>
                            </div>
                        </div>
                        
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 10%;">#</th>
                                        <th style="width: 45%;">Due Date</th>
                                        <th>Amount to Bill</th>
                                    </tr>
                                </thead>
                                <tbody id="scheduleBody">
                                    </tbody>
                            </table>
                        </div>
                        
                        <div class="btn-group" style="justify-content: flex-end; margin-top: 16px;">
                            <button type="button" class="btn btn-secondary" onclick="closeSchedule()">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                Save Installment Contract
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card" style="padding: 0;">
                <div class="card-header" style="margin: 20px 24px 16px;">
                    <h2 class="card-title">Installment Contracts Ledger</h2>
                    <div style="display:flex; gap: 8px;">
                        <input type="text" placeholder="Search accounts..." style="padding: 6px 12px; width: 200px;" class="form-input">
                    </div>
                </div>
                
                <div class="card-table-wrap" style="border: none; border-radius: 0 0 var(--radius) var(--radius);">
                    <table>
                        <thead>
                            <tr>
                                <th>Start Date</th>
                                <th>Account</th>
                                <th>Category</th>
                                <th>Terms</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($active_installments) && isset($pdo)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                        No active installment contracts found.
                                    </td>
                                </tr>
                            <?php elseif (!isset($pdo)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 30px; color: var(--danger);">
                                        <strong>Database Connection Missing.</strong>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($active_installments as $inst): ?>
                                    <tr>
                                        <td class="mono"><?= date('m/d/Y', strtotime($inst['start_date'])) ?></td>
                                        <td>
                                            <div style="font-weight:600;"><?= e($inst['member_name']) ?></div>
                                            <div class="mono" style="font-size:.7rem; color:var(--text-muted);">#<?= e($inst['accnt_no']) ?></div>
                                        </td>
                                        <td><span style="font-weight:600;"><?= e($inst['code']) ?></span></td>
                                        <td>
                                            <div class="mono"><?= e($inst['term']) ?> Months</div>
                                            <div style="font-size:.7rem; color:var(--text-secondary); text-transform: capitalize; margin-top:2px;"><?= str_replace('_', ' ', e($inst['payment_mode'])) ?></div>
                                        </td>
                                        <td class="mono" style="font-weight:600;">₱ <?= number_format($inst['total_amount'], 2) ?></td>
                                        <td>
                                            <?php 
                                                $badgeClass = 'badge-success';
                                                if ($inst['status'] === 'completed') $badgeClass = 'badge-success';
                                                elseif ($inst['status'] === 'cancelled') $badgeClass = 'badge-cancelled';
                                                elseif ($inst['status'] === 'active') $badgeClass = 'badge-warning';
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= ucfirst(e($inst['status'])) ?></span>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:8px; justify-content: flex-end;">
                                                <button class="icon-btn" title="View Schedules" onclick="openViewModal(<?= $inst['installment_id'] ?>, '<?= e(addslashes($inst['member_name'])) ?>')">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                </button>
                                                <button class="icon-btn" title="Change Contract Status" onclick="openStatusModal(<?= $inst['installment_id'] ?>, '<?= e($inst['status']) ?>')">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                </button>
                                                <button class="icon-btn" title="Delete Contract" style="color: var(--danger); border-color: #fca5a5;" onclick="confirmDelete(<?= $inst['installment_id'] ?>)">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <form id="toggleScheduleForm" action="../process/toggleScheduleStatus.php" method="POST" style="display: none;">
        <input type="hidden" name="schedule_id" id="toggle_schedule_id">
        <input type="hidden" name="action" id="toggle_schedule_action">
    </form>

    <form id="deleteForm" action="../process/deleteInstallmentBill.php" method="POST" style="display: none;">
        <input type="hidden" name="installment_id" id="delete_installment_id">
    </form>

    <div class="modal-overlay" id="viewModal">
        <div class="modal-box large">
            <div class="modal-header">
                <h2 class="title" style="font-size: 1.25rem;">Amortization Schedule</h2>
                <button class="modal-close-btn" onclick="closeViewModal()">×</button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom: 16px;">
                    <span style="font-size: .85rem; color: var(--text-secondary);">Consumer Account:</span>
                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary);" id="view_member_name">--</div>
                </div>

                <div class="card-table-wrap" style="border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <table>
                        <thead>
                            <tr>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Paid At</th>
                                <th>Processed By</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="view_schedule_body">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="statusModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 class="title" style="font-size: 1.25rem;">Update Contract Status</h2>
                <button class="modal-close-btn" onclick="closeStatusModal()">×</button>
            </div>
            <div class="modal-body">
                <form action="../process/editInstallmentBill.php" method="POST">
                    <input type="hidden" name="installment_id" id="status_installment_id">
                    
                    <div class="form-group">
                        <label>Contract Status</label>
                        <select name="status" id="edit_status" class="form-input" required>
                            <option value="active">Active (Pending Payments)</option>
                            <option value="completed">Completed (Fully Paid)</option>
                            <option value="cancelled">Cancelled (Voided)</option>
                        </select>
                    </div>

                    <div class="btn-group" style="justify-content: flex-end; margin-top: 16px;">
                        <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        const allSchedules = <?= json_encode($all_schedules, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        
        // Initialize Flatpickr on the Start Date input
        flatpickr("#startDate", {
            dateFormat: "Y-m-d", // The actual value behind the scenes (for JS and PHP)
            altInput: true,      // Allows a visually different text input for the user
            altFormat: "m/d/Y",  // Formats as mm/dd/yyyy visibly
            defaultDate: "today" // Sets default to today automatically
        });

        function autofillAmount() {
            const selectEl = document.getElementById('billCodeSelect');
            const selectedOption = selectEl.options[selectEl.selectedIndex];
            const defaultAmount = parseFloat(selectedOption.getAttribute('data-amount')) || 0;
            
            const totalAmountInput = document.getElementById('totalAmount');
            
            if (defaultAmount > 0) {
                totalAmountInput.value = defaultAmount.toFixed(2);
            }
        }

        const modeGaps = {
            'monthly': 1,
            'quarterly': 3,
            'semi_annually': 6,
            'annually': 12
        };

        function updatePaymentModes() {
            const term = parseInt(document.getElementById('term').value) || 0;
            const payModeSelect = document.getElementById('payMode');
            const currentVal = payModeSelect.value;
            
            payModeSelect.innerHTML = ''; 
            
            if (term > 0) {
                if (term % 1 === 0) payModeSelect.add(new Option('Monthly', 'monthly'));
                if (term % 3 === 0) payModeSelect.add(new Option('Quarterly', 'quarterly'));
                if (term % 6 === 0) payModeSelect.add(new Option('Semi-Annually', 'semi_annually'));
                if (term % 12 === 0) payModeSelect.add(new Option('Annually', 'annually'));
            } else {
                payModeSelect.add(new Option('Monthly', 'monthly'));
            }

            if (Array.from(payModeSelect.options).some(opt => opt.value === currentVal)) {
                payModeSelect.value = currentVal;
            }
        }
        updatePaymentModes();

        // New Auto-Adjust Function
        function adjustFlexibleAmounts(currentIndex) {
            const amortType = document.getElementById('amortType').value;
            if (amortType !== 'flexible') return;

            const targetTotal = parseFloat(document.getElementById('totalAmount').value) || 0;
            const inputs = document.querySelectorAll('.editable-amount');
            
            // Calculate sum of all payments up to the currently edited one
            let sumUpToIndex = 0;
            for (let i = 0; i <= currentIndex; i++) {
                sumUpToIndex += parseFloat(inputs[i].value) || 0;
            }

            // Calculate remaining balance and remaining rows
            const remainingBalance = targetTotal - sumUpToIndex;
            const remainingRows = inputs.length - 1 - currentIndex;

            // If there are subsequent rows, distribute the remaining balance evenly
            if (remainingRows > 0) {
                let baseAmount = Math.floor((remainingBalance / remainingRows) * 100) / 100;
                let lastAmount = remainingBalance - (baseAmount * (remainingRows - 1));

                for (let i = currentIndex + 1; i < inputs.length; i++) {
                    if (i === inputs.length - 1) {
                        inputs[i].value = lastAmount.toFixed(2);
                    } else {
                        inputs[i].value = baseAmount.toFixed(2);
                    }
                }
            }

            // Trigger total update validation
            updateRunningTotal();
        }

        function updateRunningTotal() {
            const amortType = document.getElementById('amortType').value;
            const targetTotal = parseFloat(document.getElementById('totalAmount').value) || 0;
            const badge = document.getElementById('scheduleTotalBadge');

            if (amortType === 'flexible') {
                const inputs = document.querySelectorAll('.editable-amount');
                let currentSum = 0;
                inputs.forEach(input => {
                    currentSum += parseFloat(input.value) || 0;
                });
                
                let diff = targetTotal - currentSum;
                if (Math.abs(diff) <= 0.01) {
                    badge.innerHTML = `<span style="color:var(--success);">Sum: ₱ ${currentSum.toFixed(2)} ✓</span>`;
                } else {
                    badge.innerHTML = `<span style="color:var(--danger);">Sum: ₱ ${currentSum.toFixed(2)} (Diff: ₱ ${diff.toFixed(2)})</span>`;
                }
            } else {
                badge.innerHTML = ``; // Hide if fixed
            }
        }

        // Attach strict Form Submission Validation
        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            const amortType = document.getElementById('amortType').value;
            const targetTotal = parseFloat(document.getElementById('totalAmount').value) || 0;

            if (amortType === 'flexible') {
                const inputs = document.querySelectorAll('.editable-amount');
                let currentSum = 0;
                inputs.forEach(input => {
                    currentSum += parseFloat(input.value) || 0;
                });

                if (Math.abs(currentSum - targetTotal) > 0.01) {
                    e.preventDefault(); 
                    alert(`Validation Error: The sum of your flexible schedule (₱ ${currentSum.toFixed(2)}) does not match the Total Amount (₱ ${targetTotal.toFixed(2)}). Please adjust the amounts.`);
                }
            }
        });

        function generateSchedule() {
            const amount = parseFloat(document.getElementById('totalAmount').value);
            const termInMonths = parseInt(document.getElementById('term').value);
            const amortType = document.getElementById('amortType').value;
            const modeVal = document.getElementById('payMode').value;
            const modeGap = modeGaps[modeVal];
            const startDateStr = document.getElementById('startDate').value; // Retains YYYY-MM-DD due to flatpickr format
            
            if (isNaN(amount) || isNaN(termInMonths) || !startDateStr) {
                alert("Please fill in the Total Amount, Term, and Start Date to generate the schedule.");
                return;
            }

            const totalPayments = termInMonths / modeGap; 
            const tbody = document.getElementById('scheduleBody');
            tbody.innerHTML = ""; 

            // Parse YYYY-MM-DD reliably
            const parts = startDateStr.split('-');
            let startDate = new Date(parts[0], parts[1] - 1, parts[2]);
            
            let isFlexible = (amortType === 'flexible');

            document.getElementById('scheduleStatusBadge').innerText = `${totalPayments} Payments • ${amortType.toUpperCase()}`;

            let baseAmount = Math.floor((amount / totalPayments) * 100) / 100;
            let lastAmount = parseFloat((amount - (baseAmount * (totalPayments - 1))).toFixed(2));

            for (let i = 0; i < totalPayments; i++) {
                let currentDate = new Date(startDate);
                currentDate.setMonth(startDate.getMonth() + (i * modeGap));
                
                // Format for hidden inputs (YYYY-MM-DD)
                let ddRaw = String(currentDate.getDate()).padStart(2, '0');
                let mmRaw = String(currentDate.getMonth() + 1).padStart(2, '0');
                let yyyyRaw = currentDate.getFullYear();
                let isoDate = `${yyyyRaw}-${mmRaw}-${ddRaw}`;
                
                // Display format for preview (MM/DD/YYYY)
                let displayDate = `${mmRaw}/${ddRaw}/${yyyyRaw}`;

                let currentAmountHTML;
                let amtToPay = (i === totalPayments - 1) ? lastAmount : baseAmount;

                let hiddenDateInput = `<input type="hidden" name="schedule_dates[]" value="${isoDate}">`;

                if (isFlexible) {
                    currentAmountHTML = `${hiddenDateInput} <input type="number" step="0.01" name="schedule_amounts[]" class="editable-amount" value="${amtToPay.toFixed(2)}" oninput="adjustFlexibleAmounts(${i})" required>`;
                } else {
                    currentAmountHTML = `${hiddenDateInput} <input type="hidden" name="schedule_amounts[]" value="${amtToPay.toFixed(2)}"> <span class="mono" style="font-weight:600;">₱ ${amtToPay.toFixed(2)}</span>`;
                }

                tbody.innerHTML += `
                    <tr>
                        <td style="color:var(--text-secondary); font-weight:600;">${i + 1}</td>
                        <td class="mono">${displayDate}</td>
                        <td>${currentAmountHTML}</td>
                    </tr>
                `;
            }

            updateRunningTotal(); 
            document.getElementById('schedulePreview').classList.add('active');
        }

        function closeSchedule() {
            document.getElementById('schedulePreview').classList.remove('active');
        }

        function openViewModal(installmentId, memberName) {
            document.getElementById('view_member_name').innerText = memberName;
            const tbody = document.getElementById('view_schedule_body');
            tbody.innerHTML = "";

            const schedules = allSchedules[installmentId] || [];

            if (schedules.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 20px; color:var(--text-muted);">No schedules found.</td></tr>`;
            } else {
                schedules.forEach(sched => {
                    const dueDateObj = new Date(sched.due_date);
                    const formattedDue = `${String(dueDateObj.getMonth() + 1).padStart(2, '0')}/${String(dueDateObj.getDate()).padStart(2, '0')}/${dueDateObj.getFullYear()}`;
                    
                    const paidAtHtml = sched.paid_at 
                        ? `<span class="mono" style="font-size:.75rem;">${new Date(sched.paid_at).toLocaleString()}</span>` 
                        : `<span style="color:var(--text-muted); font-style:italic;">—</span>`;

                    const markedByHtml = sched.marked_by_name 
                        ? `<span style="font-size:.75rem; font-weight:600; color:var(--text-primary); text-transform:capitalize;">${sched.marked_by_name}</span>`
                        : `<span style="color:var(--text-muted); font-style:italic;">—</span>`;

                    const badgeClass = sched.status === 'paid' ? 'badge-success' : 'badge-warning';
                    
                    let actionHtml = '';
                    if (sched.status === 'pending') {
                        actionHtml = `
                            <button class="btn btn-primary" style="height: 30px; padding: 0 12px; font-size: .7rem;" onclick="toggleScheduleStatus(${sched.schedule_id}, 'paid')">
                                Mark Paid
                            </button>
                        `;
                    } else {
                        actionHtml = `
                            <button class="btn btn-secondary" style="height: 30px; padding: 0 12px; font-size: .7rem;" onclick="toggleScheduleStatus(${sched.schedule_id}, 'pending')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                                Undo
                            </button>
                        `;
                    }

                    tbody.innerHTML += `
                        <tr>
                            <td class="mono" style="font-weight:600;">${formattedDue}</td>
                            <td class="mono">₱ ${parseFloat(sched.amount).toFixed(2)}</td>
                            <td><span class="badge ${badgeClass}">${sched.status.toUpperCase()}</span></td>
                            <td>${paidAtHtml}</td>
                            <td>${markedByHtml}</td>
                            <td style="text-align: right;">${actionHtml}</td>
                        </tr>
                    `;
                });
            }
            document.getElementById('viewModal').classList.add('active');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('active');
        }

        function toggleScheduleStatus(scheduleId, action) {
            let msg = action === 'paid' 
                ? "Mark this schedule as PAID?" 
                : "Undo payment? This will revert the schedule to PENDING and clear the record of who processed it.";
                
            if (confirm(msg)) {
                document.getElementById('toggle_schedule_id').value = scheduleId;
                document.getElementById('toggle_schedule_action').value = action;
                document.getElementById('toggleScheduleForm').submit();
            }
        }

        function openStatusModal(id, currentStatus) {
            document.getElementById('status_installment_id').value = id;
            document.getElementById('edit_status').value = currentStatus;
            document.getElementById('statusModal').classList.add('active');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
        }

        function confirmDelete(id) {
            if(confirm("Are you sure you want to delete this installment contract? All associated scheduled payments will be wiped out. This cannot be undone.")) {
                document.getElementById('delete_installment_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>