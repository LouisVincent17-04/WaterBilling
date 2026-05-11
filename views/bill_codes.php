<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// TODO: Include your actual database connection
require_once '../database/config.php';

$_SESSION['full_name'] = $_SESSION['full_name'] ?? 'System Admin';
$_SESSION['username']  = $_SESSION['username'] ?? 'admin';
$_SESSION['role']      = $_SESSION['role'] ?? 'Administrator';


$pdo = getDB();

if (!function_exists('e')) {
    function e($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');

// ==========================================
// DYNAMIC DATA FETCHING
// ==========================================
$gl_accounts = [];
$bill_codes = [];

if (isset($pdo)) {
    // 1. Fetch GL Accounts (Only where SummaryAccnt != 0)
    try {
        $gl_stmt = $pdo->query("SELECT AccntID, AccntName FROM chartofaccntstbl WHERE SummaryAccnt != '0' ORDER BY AccntName ASC");
        $gl_accounts = $gl_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("GL Fetch Error: " . $e->getMessage());
    }

    // 2. Fetch Active Bill Codes with their linked GL Account Name
    try {
        $bc_stmt = $pdo->query("
            SELECT bc.*, ca.AccntName 
            FROM bill_codes bc 
            LEFT JOIN chartofaccntstbl ca ON bc.gl_account = ca.AccntID 
            ORDER BY bc.created_at DESC
        ");
        $bill_codes = $bc_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Bill Codes Fetch Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Codes — COWASCO Waters</title>
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
            --radius-sm: 8px;
            --radius: 14px;
            --radius-lg: 20px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow-lg: 0 20px 60px rgba(0,0,0,.18);
            --transition: 0.2s cubic-bezier(0.4,0,0.2,1);
        }
        body { font-family: 'Sora', sans-serif; background: var(--off-white); color: var(--text-primary); }
        
        .page { padding: 32px 32px 60px; animation: fadeIn .4s ease both; max-width: 1400px; margin: 0 auto; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        .header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
        .title { font-size: 1.5rem; font-weight: 700; }
        .subtitle { font-size: .875rem; color: var(--text-secondary); margin-top: 4px; }
        
        /* Alerts */
        .alert { padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: .875rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: var(--success-bg); border: 1px solid var(--success); color: var(--success); }
        .alert-danger { background: var(--danger-bg); border: 1px solid var(--danger); color: var(--danger); }

        .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
        .card-title { font-size: 1.1rem; font-weight: 600; }
        
        /* Forms */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; align-items: start; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        label { font-size: .75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .05em; }
        
        .form-input {
            padding: 9px 13px; font-family: 'Sora', sans-serif; font-size: .875rem;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm); outline: none;
            background: var(--white); color: var(--text-primary); transition: border-color var(--transition), box-shadow var(--transition);
        }
        .form-input:focus { border-color: var(--border-focus); box-shadow: 0 0 0 3px rgba(26,26,46,0.1); }
        select.form-input { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; appearance: none; }

        /* Buttons */
        .btn-group { display: flex; gap: 12px; margin-top: 8px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 18px; border: none; border-radius: var(--radius-sm); font-family: inherit; font-size: .8125rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: transform .1s, background .2s, border-color .2s; }
        .btn:active { transform: scale(0.98); }
        .btn-primary { background: var(--accent); color: var(--white); }
        .btn-primary:hover { background: #0f0f1a; box-shadow: 0 4px 12px rgba(26,26,46,.2); }
        .btn-secondary { background: var(--white); color: var(--text-primary); border: 1.5px solid var(--border); }
        .btn-secondary:hover { border-color: var(--border-focus); background: var(--off-white); }
        
        .icon-btn { width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid var(--border); background: var(--white); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-secondary); transition: all .2s; text-decoration: none; flex-shrink: 0; }
        .icon-btn:hover { border-color: var(--border-focus); color: var(--text-primary); background: var(--off-white); transform: translateY(-1px); }
        .icon-btn svg { width: 14px; height: 14px; }
        
        /* Tables */
        .card-table-wrap { border: 1px solid var(--border); border-radius: var(--radius-sm); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .85rem; white-space: nowrap; }
        th { text-align: left; padding: 12px 16px; color: var(--text-secondary); border-bottom: 1.5px solid var(--border); font-weight: 600; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; }
        td { padding: 14px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; color: var(--text-primary); }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: var(--off-white); }
        
        .mono { font-family: 'JetBrains Mono', monospace; font-size: .8rem; }
        .mono-bold { font-family: 'JetBrains Mono', monospace; font-size: .85rem; font-weight: 700; color: var(--text-primary); }
        .badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 20px; font-size: .68rem; font-weight: 700; letter-spacing: .02em; }
        .badge-type { background: #f3f4f6; color: #4b5563; border: 1px solid var(--border); }

        /* Modal Overlay */
        .modal-overlay { position: fixed; inset: 0; background: rgba(10,10,20,0.55); z-index: 1000; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity .3s ease; backdrop-filter: blur(4px); padding: 16px; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-box { background: var(--white); border-radius: var(--radius-lg); width: 100%; max-width: 800px; box-shadow: var(--shadow-lg); transform: translateY(24px) scale(0.97); opacity: 0; transition: transform 0.35s, opacity 0.35s; display: flex; flex-direction: column; }
        .modal-overlay.active .modal-box { transform: translateY(0) scale(1); opacity: 1; }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 24px; }
        .modal-close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); }
        .modal-close-btn:hover { color: var(--text-primary); }
    </style>
</head>
<body>

    <?php require_once '../common/navbar.php'; ?>

    <div class="main-content">
        <main class="page">
            <div class="header">
                <div>
                    <h1 class="title">Bill Codes Dictionary</h1>
                    <p class="subtitle">Define and manage standard billing categories linked to General Ledger accounts.</p>
                </div>
            </div>

            <!-- ALERT MESSAGES -->
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

            <!-- SECTION 1: ADD NEW CODE -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title" id="formTitle">Register New Bill Code</h2>
                </div>
                
                <form action="../process/addBillCode.php" method="POST" id="billCodeForm">
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Code Name (e.g. WATER METER)</label>
                            <input type="text" name="code" class="form-input" style="text-transform: uppercase;" required>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Long Description</label>
                            <input type="text" name="description" class="form-input" placeholder="Brief explanation...">
                        </div>
                        <div class="form-group">
                            <label>Account Type</label>
                            <select name="type" class="form-input" required>
                                <option value="" disabled selected>Select Type...</option>
                                <option value="Asset">Asset</option>
                                <option value="Liability">Liability</option>
                                <option value="Equity">Equity</option>
                                <option value="Revenue">Revenue</option>
                                <option value="Expense">Expense</option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>GL Account (General Ledger)</label>
                            <select name="gl_account" class="form-input">
                                <option value="" selected>No GL Linked (Unassigned)</option>
                                <?php foreach ($gl_accounts as $gl): ?>
                                    <option value="<?= e($gl['AccntID']) ?>">
                                        <?= e($gl['AccntName']) ?> (<?= e($gl['AccntID']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Default Amount (₱)</label>
                            <input type="number" name="default_amount" class="form-input mono" placeholder="0.00" step="0.01">
                        </div>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Save Code
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('billCodeForm').reset();">Clear</button>
                    </div>
                </form>
            </div>

            <!-- SECTION 2: BILL CODES LEDGER -->
            <div class="card" style="padding: 0;">
                <div class="card-header" style="margin: 20px 24px 16px;">
                    <h2 class="card-title">Active Bill Codes</h2>
                    <div style="display:flex; gap: 8px;">
                        <input type="text" class="form-input" placeholder="Search codes or GL..." style="padding: 7px 12px; width: 250px;">
                    </div>
                </div>
                
                <div class="card-table-wrap" style="border: none; border-radius: 0 0 var(--radius) var(--radius);">
                    <table>
                        <thead>
                            <tr>
                                <th>Description / Code</th>
                                <th>Type</th>
                                <th>GL Account</th>
                                <th>Default Amount</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bill_codes) && isset($pdo)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                        No bill codes have been registered yet.
                                    </td>
                                </tr>
                            <?php elseif (!isset($pdo)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 30px; color: var(--danger);">
                                        <strong>Database Connection Missing.</strong><br>Please ensure you uncomment the database inclusion at the top of the file.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bill_codes as $code): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 700;"><?= e($code['code']) ?></div>
                                            <?php if(!empty($code['description'])): ?>
                                                <div style="font-size: .75rem; color: var(--text-secondary); margin-top: 2px;">
                                                    <?= e($code['description']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge badge-type"><?= e($code['type']) ?></span></td>
                                        
                                        <td>
                                            <?php if ($code['gl_account']): ?>
                                                <div class="mono" style="color: var(--text-secondary);">
                                                    <span style="color: var(--accent); font-weight: 600;"><?= e($code['gl_account']) ?></span> — <?= e($code['AccntName']) ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="mono" style="color: var(--warning); font-style: italic;">Unassigned</div>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="mono-bold">
                                            <?= $code['default_amount'] > 0 ? '₱ ' . number_format($code['default_amount'], 2) : '<span style="color:var(--text-muted); font-weight:400;">—</span>' ?>
                                        </td>
                                        
                                        <td>
                                            <div style="display:flex; gap:8px; justify-content: flex-end;">
                                                <button class="icon-btn" title="Edit Code" onclick="openEditModal(
                                                    <?= $code['code_id'] ?>, 
                                                    '<?= e(addslashes($code['code'])) ?>', 
                                                    '<?= e(addslashes($code['description'] ?? '')) ?>', 
                                                    '<?= e(addslashes($code['type'])) ?>', 
                                                    '<?= e($code['gl_account'] ?? '') ?>', 
                                                    '<?= e($code['default_amount']) ?>'
                                                )">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                </button>
                                                
                                                <?php if ($code['gl_account']): ?>
                                                    <!-- Cannot Delete if GL is linked -->
                                                    <button class="icon-btn" title="Cannot delete: Linked to GL Account" style="opacity: 0.5; cursor: not-allowed;">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                                    </button>
                                                <?php else: ?>
                                                    <!-- Can Delete if no GL linked -->
                                                    <button class="icon-btn" title="Delete Code" style="color: var(--danger); border-color: #fca5a5;" onclick="confirmDelete(<?= $code['code_id'] ?>)">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                                    </button>
                                                <?php endif; ?>
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

    <!-- Hidden Form for Deletion -->
    <form id="deleteForm" action="../process/deleteBillCode.php" method="POST" style="display: none;">
        <input type="hidden" name="code_id" id="delete_code_id">
    </form>

    <!-- EDIT MODAL OVERLAY -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 class="title" style="font-size: 1.25rem;">Edit Bill Code</h2>
                <button class="modal-close-btn" onclick="closeEditModal()">×</button>
            </div>
            <div class="modal-body">
                <form action="../process/editBillCode.php" method="POST">
                    <input type="hidden" name="code_id" id="edit_code_id">
                    
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Code Name</label>
                            <input type="text" name="code" id="edit_code" class="form-input" style="text-transform: uppercase;" required>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Long Description</label>
                            <input type="text" name="description" id="edit_description" class="form-input">
                        </div>
                        <div class="form-group">
                            <label>Account Type</label>
                            <select name="type" id="edit_type" class="form-input" required>
                                <option value="Asset">Asset</option>
                                <option value="Liability">Liability</option>
                                <option value="Equity">Equity</option>
                                <option value="Revenue">Revenue</option>
                                <option value="Expense">Expense</option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>GL Account</label>
                            <select name="gl_account" id="edit_gl_account" class="form-input">
                                <option value="">No GL Linked</option>
                                <?php foreach ($gl_accounts as $gl): ?>
                                    <option value="<?= e($gl['AccntID']) ?>">
                                        <?= e($gl['AccntName']) ?> (<?= e($gl['AccntID']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Default Amount (₱)</label>
                            <input type="number" name="default_amount" id="edit_default_amount" class="form-input mono" step="0.01">
                        </div>
                    </div>
                    <div class="btn-group" style="justify-content: flex-end; margin-top: 16px;">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditModal(id, code, desc, type, gl, amount) {
            document.getElementById('edit_code_id').value = id;
            document.getElementById('edit_code').value = code;
            document.getElementById('edit_description').value = desc;
            document.getElementById('edit_type').value = type;
            document.getElementById('edit_gl_account').value = gl;
            document.getElementById('edit_default_amount').value = amount;
            
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function confirmDelete(id) {
            if(confirm("Are you sure you want to delete this Bill Code? This action cannot be undone.")) {
                document.getElementById('delete_code_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>