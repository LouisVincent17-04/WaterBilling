<?php
/**
 * One-Time Billing — Fixed Filters, Batch Edit & Batch Delete
 */

require_once '../database/config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['full_name'] = $_SESSION['full_name'] ?? 'System Admin';
$_SESSION['username']  = $_SESSION['username']  ?? 'admin';
$_SESSION['role']      = $_SESSION['role']       ?? 'Administrator';

if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}

$members        = [];
$bill_codes     = [];
$one_time_bills = [];
$cities         = [];
$addresses      = [];

$pdo = getDB();

if (isset($pdo)) {
    // 1. Fetch Members with all their discounts (grouped in PHP for MySQL 5.5 compatibility)
    try {
        $mem_rows = $pdo->query("
            SELECT
                m.pkey,
                CONCAT(m.lastname, ', ', m.firstname) AS full_name,
                d.discount_id,
                d.discount_type,
                d.discount_rate
            FROM members m
            LEFT JOIN discounted_members dm ON m.pkey        = dm.member_id
            LEFT JOIN discounts d           ON dm.discount_id = d.discount_id
            WHERE m.status = 'A'
            ORDER BY m.lastname ASC, d.discount_type ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $membersMap = [];
        foreach ($mem_rows as $row) {
            $pk = $row['pkey'];
            if (!isset($membersMap[$pk])) {
                $membersMap[$pk] = [
                    'pkey'      => $pk,
                    'full_name' => $row['full_name'],
                    'discounts' => [],
                ];
            }
            if ($row['discount_id']) {
                $membersMap[$pk]['discounts'][] = [
                    'id'   => (int)$row['discount_id'],
                    'type' => $row['discount_type'],
                    'rate' => (float)$row['discount_rate'],
                ];
            }
        }
        $members = array_values($membersMap);
    } catch (PDOException $e) {
        error_log("Members Fetch Error: " . $e->getMessage());
    }

    // 2. Fetch Bill Codes
    try {
        $code_stmt = $pdo->query("SELECT code_id, code, description, default_amount FROM bill_codes ORDER BY code ASC");
        $bill_codes = $code_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Bill Codes Fetch Error: " . $e->getMessage());
    }

    // 3. Fetch Cities with active-member counts
    try {
        $city_stmt = $pdo->query("
            SELECT
                TRIM(UPPER(c.city))      AS city_name,
                COUNT(DISTINCT m.pkey)   AS member_count
            FROM cities c
            JOIN addresses    a  ON c.pkey       = a.city_id       AND a.status  = 'A'
            JOIN memberaddress ma ON a.pkey       = ma.address_key  AND ma.status = 'A'
            JOIN members      m  ON ma.member_key = m.pkey          AND m.status  = 'A'
            WHERE c.status = 'A' AND c.city IS NOT NULL AND TRIM(c.city) != ''
            GROUP BY TRIM(UPPER(c.city))
            ORDER BY city_name ASC
        ");
        $cities = $city_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Cities Fetch Error: " . $e->getMessage());
    }

    // 4. Fetch Addresses with active-member counts
    try {
        $addr_stmt = $pdo->query("
            SELECT
                TRIM(UPPER(a.street))    AS street_name,
                COUNT(DISTINCT m.pkey)   AS member_count
            FROM addresses    a
            JOIN memberaddress ma ON a.pkey       = ma.address_key AND ma.status = 'A'
            JOIN members      m  ON ma.member_key = m.pkey         AND m.status  = 'A'
            WHERE a.status = 'A' AND a.street IS NOT NULL AND TRIM(a.street) != ''
            GROUP BY TRIM(UPPER(a.street))
            ORDER BY street_name ASC
        ");
        $addresses = $addr_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Addresses Fetch Error: " . $e->getMessage());
    }

    // 5. Fetch Ad-Hoc Bills Ledger
    try {
        $bill_stmt = $pdo->query("
            SELECT b.*,
                   m.pkey AS accnt_no,
                   CONCAT(m.lastname, ', ', m.firstname) AS member_name,
                   c.code, c.description AS code_desc
            FROM one_time_bills b
            JOIN members    m ON b.member_id   = m.pkey
            JOIN bill_codes c ON b.bill_code_id = c.code_id
            ORDER BY b.created_at DESC
        ");
        $one_time_bills = $bill_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Bills Fetch Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>One-Time Billing</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
/* ── Reset & Tokens ────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --white:       #ffffff; --surface:     #f5f7fa; --surface-2:   #eef1f6;
    --border:      #e2e6ed; --border-2:    #c9d0dc; --text-1:      #111827;
    --text-2:      #4b5563; --text-3:      #9ca3af; --brand:       #1e3a5f;
    --brand-mid:   #2d5282; --brand-light: #ebf2ff;
    --green:       #059669; --green-bg:    #ecfdf5; --green-border:#a7f3d0;
    --red:         #dc2626; --red-bg:      #fef2f2; --red-border:  #fca5a5;
    --amber:       #b45309; --amber-bg:    #fffbeb; --amber-border:#fcd34d;
    --slate:       #6b7280; --slate-bg:    #f3f4f6;
    --radius-xs:   6px; --radius-sm:   10px; --radius:      14px; --radius-lg:   20px;
    --shadow-sm:  0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
    --shadow-xl:  0 24px 64px rgba(0,0,0,.18), 0 4px 16px rgba(0,0,0,.08);
    --font:        'Plus Jakarta Sans', sans-serif;
    --font-mono:   'JetBrains Mono', monospace;
    --ease:        cubic-bezier(.4,0,.2,1);
}

body { font-family: var(--font); background: var(--surface); color: var(--text-1); min-height: 100vh; -webkit-font-smoothing: antialiased; }
.page { padding: 28px 28px 72px; max-width: 1380px; margin: 0 auto; animation: slideUp .45s var(--ease) both; }
@keyframes slideUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }

.page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 28px; }
.page-title { font-size: 1.65rem; font-weight: 800; line-height: 1.2; letter-spacing: -.02em; }
.page-sub   { font-size: .85rem; color: var(--text-2); margin-top: 5px; }

.header-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

.alert { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: var(--radius-sm); font-size: .875rem; font-weight: 600; margin-bottom: 20px; }
.alert-success { background:var(--green-bg);  border:1px solid var(--green-border); color:var(--green); }
.alert-danger  { background:var(--red-bg);    border:1px solid var(--red-border);   color:var(--red); }

.card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 24px; }
.card-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 18px 22px; border-bottom: 1px solid var(--border); }
.card-title { font-size: 1rem; font-weight: 700; }

.btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; height: 38px; padding: 0 18px; border: none; border-radius: var(--radius-sm); font-family: var(--font); font-size: .82rem; font-weight: 700; cursor: pointer; white-space: nowrap; text-decoration: none; transition: background .18s var(--ease), box-shadow .18s, transform .1s; flex-shrink: 0; }
.btn:active { transform: scale(.97); }
.btn svg { width:14px; height:14px; stroke-width:2.5; flex-shrink:0; }
.btn-primary { background:var(--brand); color:#fff; }
.btn-primary:hover { background:var(--brand-mid); }
.btn-success { background:var(--green); color:#fff; }
.btn-success:hover { background:#047857; }
.btn-danger  { background:var(--red);   color:#fff; }
.btn-danger:hover  { background:#b91c1c; }
.btn-ghost { background:var(--white); color:var(--text-1); border:1.5px solid var(--border); }
.btn-ghost:hover { border-color:var(--brand); color:var(--brand); background:var(--brand-light); }
.btn-ghost-red { background:var(--white); color:var(--red); border:1.5px solid var(--red-border); }
.btn-ghost-red:hover { background:var(--red-bg); border-color:var(--red); }

.icon-btn { width:34px; height:34px; border-radius:var(--radius-xs); border:1.5px solid var(--border); background:var(--white); display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-2); transition: all .18s var(--ease); flex-shrink:0; }
.icon-btn:hover { border-color:var(--text-1); color:var(--text-1); background:var(--surface-2); transform:translateY(-1px); }
.icon-btn svg  { width:14px; height:14px; }
.icon-btn.green { color:var(--green);  border-color:var(--green-border); }
.icon-btn.green:hover { background:var(--green-bg); border-color:var(--green); }
.icon-btn.red   { color:var(--red);    border-color:var(--red-border); }
.icon-btn.red:hover { background:var(--red-bg); border-color:var(--red); }

.form-group  { display:flex; flex-direction:column; gap:6px; }
.form-grid   { display:grid; grid-template-columns:repeat(auto-fit, minmax(210px,1fr)); gap:16px; }
.form-grid .span-2 { grid-column: span 2; }
.form-grid .span-full { grid-column: 1 / -1; }

label { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--text-2); }
.field { padding: 9px 13px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: var(--font); font-size: .875rem; color: var(--text-1); background: var(--white); transition: border-color .18s, box-shadow .18s; width: 100%; outline: none; }
.field:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(30,58,95,.1); }
.field[readonly] { background:var(--surface); color:var(--text-2); cursor:not-allowed; }
.field.mono { font-family: var(--font-mono); font-size:.82rem; }
select.field { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 13px center; padding-right: 36px; }
.field-hint { font-size:.7rem; color:var(--text-3); margin-top:2px; }

.badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:.65rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
.badge-green  { background:var(--green-bg);  color:var(--green);  border:1px solid var(--green-border); }
.badge-red    { background:var(--red-bg);    color:var(--red);    border:1px solid var(--red-border); }
.badge-amber  { background:var(--amber-bg);  color:var(--amber);  border:1px solid var(--amber-border); }
.badge-slate  { background:var(--slate-bg);  color:var(--slate);  border:1px solid var(--border); }

.table-wrap { overflow-x: auto; }
table { width:100%; border-collapse:collapse; font-size:.84rem; white-space:nowrap; }
thead th { text-align:left; padding:12px 18px; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--text-2); border-bottom:1.5px solid var(--border); background: var(--surface); position: sticky; top:0; z-index:2; }
tbody td { padding:14px 18px; border-bottom:1px solid var(--border); vertical-align:middle; }
tbody tr:last-child td { border-bottom:none; }
tbody tr:hover { background: #fafbfd; }
tbody tr.row-selected { background: var(--brand-light) !important; }
.mono { font-family:var(--font-mono); font-size:.78rem; }
.member-cell .name  { font-weight:700; }
.member-cell .accnt { font-size:.7rem; color:var(--text-3); margin-top:2px; }
.code-cell   .code  { font-weight:700; font-family:var(--font-mono); font-size:.8rem; }
.code-cell   .desc  { font-size:.74rem; color:var(--text-2); margin-top:2px; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.actions-cell { display:flex; gap:7px; justify-content:flex-end; align-items:center; }
.empty-state { padding:56px 24px; text-align:center; color:var(--text-3); }
.empty-state svg { width:40px; height:40px; margin-bottom:12px; opacity:.4; }

/* ── Batch Toolbar ──────────────────────────────────────────────────────── */
.batch-toolbar {
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    padding: 10px 22px;
    background: var(--brand-light);
    border-bottom: 1.5px solid #bfdbfe;
    font-size: .84rem;
}
.batch-toolbar.visible { display: flex; }
.batch-toolbar-info { display:flex; align-items:center; gap:8px; color:var(--brand); font-weight:700; }
.batch-toolbar-info svg { width:16px; height:16px; }
.batch-toolbar-actions { display:flex; gap:8px; flex-wrap:wrap; }

.search-wrap { position:relative; }
.search-wrap input { padding-left:36px; }
.search-wrap .search-icon { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:var(--text-3); pointer-events:none; width:15px; height:15px; }

.overlay { position:fixed; inset:0; background:rgba(10,18,36,.55); backdrop-filter:blur(5px); z-index:900; display:none; align-items:center; justify-content:center; padding:16px; opacity:0; transition:opacity .3s var(--ease); }
.overlay.open { display:flex; opacity:1; }
.modal { background:var(--white); border-radius:var(--radius-lg); width:100%; max-width:580px; box-shadow:var(--shadow-xl); transform:translateY(28px) scale(.97); opacity:0; transition:transform .35s var(--ease), opacity .35s var(--ease); display:flex; flex-direction:column; max-height:92vh; }
.modal.wide { max-width:1050px; }
.overlay.open .modal { transform:none; opacity:1; }
.modal-head { display:flex; align-items:center; justify-content:space-between; padding:20px 24px; border-bottom:1px solid var(--border); flex-shrink:0; }
.modal-title { font-size:1.1rem; font-weight:800; letter-spacing:-.01em; }
.modal-close { width:32px; height:32px; border-radius:var(--radius-xs); border:1.5px solid var(--border); background:var(--white); display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-2); transition:all .15s; flex-shrink:0; }
.modal-close:hover { background:var(--red-bg); border-color:var(--red-border); color:var(--red); }
.modal-close svg { width:16px; height:16px; }
.modal-body { padding:24px; overflow-y:auto; flex:1; }

.batch-layout { display:grid; grid-template-columns:300px 1fr; gap:24px; }
.batch-sidebar { border-right:1px solid var(--border); padding-right:24px; display:flex; flex-direction:column; gap:14px; }
.batch-sidebar .execute-btn { margin-top:auto; }
.batch-panel { display:flex; flex-direction:column; gap:14px; }
.batch-filters { display:flex; gap:10px; flex-wrap:wrap; }
.batch-filters .field { flex:1; min-width:130px; }
.batch-table-wrap { border:1px solid var(--border); border-radius:var(--radius-sm); overflow:auto; max-height:340px; flex:1; }
.batch-table-wrap table { font-size:.8rem; }
.batch-table-wrap thead th { padding:10px 14px; font-size:.65rem; position:sticky; top:0; z-index:5; background:var(--surface); }
.batch-table-wrap tbody td { padding:11px 14px; }
.batch-count { font-size:.8rem; color:var(--text-2); display:flex; align-items:center; gap:6px; }
.batch-count strong { color:var(--brand); }
.discount-tag { font-size:.68rem; background:var(--amber-bg); color:var(--amber); padding:2px 7px; border-radius:4px; border:1px solid var(--amber-border); font-weight:700; margin-left:6px; vertical-align:middle; }
.discount-auto-pill { display:flex; align-items:center; gap:8px; padding:10px 13px; background:var(--amber-bg); border:1.5px solid var(--amber-border); border-radius:var(--radius-sm); }
.discount-auto-pill .d-type { font-weight:700; color:var(--amber); font-size:.85rem; }
.discount-auto-pill .d-auto { font-size:.72rem; color:var(--text-3); margin-left:auto; font-weight:600; text-transform:uppercase; letter-spacing:.04em; }
.discount-multi-note { font-size:.72rem; color:var(--brand-mid); margin-top:5px; font-weight:600; }
.divider { border:none; border-top:1px solid var(--border); margin:20px 0; }
.form-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:20px; flex-wrap:wrap; }

/* Batch-edit hint box */
.info-box { background:var(--brand-light); border:1px solid #bfdbfe; border-radius:var(--radius-sm); padding:10px 14px; font-size:.8rem; color:var(--brand-mid); margin-bottom:16px; }
.info-box strong { font-weight:800; }

@media (max-width: 768px) {
    .page { padding:16px 16px 64px; }
    .page-header { gap:12px; }
    .header-actions { width:100%; }
    .header-actions .btn { flex:1; justify-content:center; }
    .batch-layout { grid-template-columns:1fr; gap:0; }
    .batch-sidebar { border-right:none; border-bottom:1px solid var(--border); padding-right:0; padding-bottom:20px; margin-bottom:20px; }
    .batch-filters { flex-direction:column; }
    .batch-table-wrap { max-height:250px; }
    .modal { border-radius:var(--radius); }
    .modal.wide { max-width:100%; }
    .form-grid .span-2 { grid-column:1/-1; }
    .batch-toolbar-actions .btn { font-size:.75rem; padding:0 12px; }
}
</style>
</head>
<body>

<?php require_once '../common/navbar.php'; ?>

<div class="main-content">
<main class="page">

    <div class="page-header">
        <div>
            <h1 class="page-title">One-Time Billing</h1>
            <p class="page-sub">Issue ad-hoc charges for penalties, materials, or services</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-ghost" onclick="openAddModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Individual Billing
            </button>
            <button class="btn btn-primary" onclick="openBatchModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Batch Billing
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <?= e($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= e($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Ad-Hoc Charges Ledger</h2>
            <div class="search-wrap">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input class="field" type="text" placeholder="Search accounts…" id="ledgerSearch" oninput="filterLedger(this.value)">
            </div>
        </div>

        <!-- ── Batch Toolbar (visible when rows are selected) ── -->
        <div class="batch-toolbar" id="ledgerBatchToolbar">
            <div class="batch-toolbar-info">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                <span id="ledgerSelCount">0</span> bill(s) selected
            </div>
            <div class="batch-toolbar-actions">
                <button type="button" class="btn btn-primary" onclick="openBatchEditModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit Selected
                </button>
                <button type="button" class="btn btn-ghost-red" onclick="confirmBatchDelete()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                    Delete Selected
                </button>
                <button type="button" class="btn btn-ghost" onclick="clearLedgerSelection()">Clear</button>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:40px; padding-left:22px;">
                            <input type="checkbox" id="ledgerSelectAll" onchange="toggleLedgerAll(this)" title="Select all visible">
                        </th>
                        <th>Date Issued</th>
                        <th>Account</th>
                        <th>Category</th>
                        <th>Term</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="ledgerBody">
                <?php if (empty($one_time_bills)): ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                            <p>No one-time bills have been issued yet.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($one_time_bills as $bill):
                        $sc   = match($bill['status']) {
                            'paid'      => 'badge-green',
                            'cancelled' => 'badge-slate',
                            default     => 'badge-red',
                        };
                        $sdot = match($bill['status']) {
                            'paid'      => '●',
                            'cancelled' => '○',
                            default     => '●',
                        };
                    ?>
                    <tr class="ledger-row"
                        data-name="<?= strtolower(e($bill['member_name'])) ?>"
                        data-accnt="<?= e($bill['accnt_no']) ?>"
                        data-id="<?= (int)$bill['bill_id'] ?>">
                        <td style="padding-left:22px;">
                            <input type="checkbox" class="ledger-cb"
                                   value="<?= (int)$bill['bill_id'] ?>"
                                   onchange="updateLedgerSelection()">
                        </td>
                        <td class="mono"><?= date('m/d/Y', strtotime($bill['bill_date'])) ?></td>
                        <td>
                            <div class="member-cell">
                                <div class="name"><?= e($bill['member_name']) ?></div>
                                <div class="accnt mono">#<?= e($bill['accnt_no']) ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="code-cell">
                                <div class="code"><?= e($bill['code']) ?></div>
                                <div class="desc"><?= e($bill['code_desc']) ?></div>
                            </div>
                        </td>
                        <td class="mono" style="color:var(--text-2);"><?= e($bill['term_days']) ?>d</td>
                        <td class="mono" style="font-weight:700;">₱ <?= number_format($bill['total_amount'], 2) ?></td>
                        <td><span class="badge <?= $sc ?>"><?= $sdot ?> <?= ucfirst(e($bill['status'])) ?></span></td>
                        <td>
                            <div class="actions-cell">
                                <button class="icon-btn" title="Edit"
                                    onclick="openEditModal(
                                        <?= (int)$bill['bill_id'] ?>,
                                        '<?= e(addslashes($bill['member_name'])) ?>',
                                        '<?= e(addslashes($bill['code'])) ?>',
                                        '<?= e($bill['total_amount']) ?>',
                                        '<?= e($bill['bill_date']) ?>',
                                        '<?= e($bill['term_days']) ?>',
                                        '<?= e($bill['due_date']) ?>',
                                        '<?= e($bill['status']) ?>'
                                    )">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button class="icon-btn red" title="Delete" onclick="confirmDelete(<?= (int)$bill['bill_id'] ?>)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
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

<!-- ── Hidden single-row action forms ────────────────────────────────────── -->
<form id="deleteForm"   action="../process/deleteOneTimeBill.php"  method="POST" style="display:none;">
    <input type="hidden" name="bill_id" id="delete_bill_id">
</form>

<!-- ── Hidden batch action forms ─────────────────────────────────────────── -->
<form id="batchDeleteForm" action="../process/batchDeleteOneTimeBill.php" method="POST" style="display:none;">
    <div id="batchDeleteIds"></div>
</form>
<form id="batchEditFormHidden" action="../process/editBatchOneTimeBill.php" method="POST" style="display:none;">
    <div id="batchEditIds"></div>
    <input type="hidden" name="status"    id="be_status_hidden">
    <input type="hidden" name="due_date"  id="be_due_hidden">
    <input type="hidden" name="term_days" id="be_term_hidden">
</form>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Issue Individual Charge
════════════════════════════════════════════════════════════════════════════ -->
<div class="overlay" id="addModal" onclick="handleOverlayClick(event,'addModal')">
<div class="modal">
    <div class="modal-head">
        <span class="modal-title">Issue Individual Charge</span>
        <button class="modal-close" type="button" onclick="closeAddModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <div class="modal-body">
        <form action="../process/addOneTimeBill.php" method="POST" id="addForm">
            <div class="form-grid">
                <div class="form-group span-full">
                    <label>Consumer Account</label>
                    <select name="member_id" id="ind_member_id" class="field" required onchange="calcIndDiscount()">
                        <option value="" disabled selected>Search &amp; select member…</option>
                        <?php foreach ($members as $mem):
                            $discJson  = e(json_encode($mem['discounts']));
                            $discCount = count($mem['discounts']);
                            $discLabel = '';
                            if ($discCount === 1) {
                                $discLabel = " [−".(float)$mem['discounts'][0]['rate']."%]";
                            } elseif ($discCount > 1) {
                                $discLabel = " [".$discCount." discounts]";
                            }
                        ?>
                        <option value="<?= e($mem['pkey']) ?>" data-discounts="<?= $discJson ?>">
                            <?= e($mem['pkey']) ?> — <?= e($mem['full_name']) ?><?= $discLabel ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- ── Discount Section: auto-applied or picker when member has 2+ discounts ── -->
                <div class="form-group span-full" id="ind_discount_section" style="display:none;">
                    <label>Applicable Discount</label>
                    <div id="ind_discount_display"></div>
                    <input type="hidden" name="discount_id" id="ind_discount_id" value="">
                </div>
                <div class="form-group span-full">
                    <label>Charge Category</label>
                    <select name="bill_code_id" id="ind_bill_code_id" class="field" required onchange="autoFillAmount()">
                        <option value="" disabled selected>Select bill code…</option>
                        <?php foreach ($bill_codes as $code):
                            $defaultAmt = $code['default_amount'] ?? 0;
                        ?>
                        <option value="<?= e($code['code_id']) ?>" data-amount="<?= $defaultAmt ?>">
                            <?= e($code['code']) ?> — <?= e($code['description']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Base Amount (₱)</label>
                    <input type="number" step="0.01" id="ind_base" class="field mono" placeholder="0.00" required oninput="calcIndDiscount()">
                </div>
                <div class="form-group">
                    <label>
                        Discounted Total (₱)
                        <span id="ind_disc_badge" class="discount-tag" style="display:none;">−0%</span>
                    </label>
                    <input type="number" step="0.01" name="total_amount" id="ind_total" class="field mono" readonly>
                </div>
                <div class="form-group">
                    <label>Date Issued</label>
                    <input type="date" name="bill_date" id="add_date" class="field" required>
                </div>
                <div class="form-group">
                    <label>Term (Days)</label>
                    <input type="number" name="term_days" id="add_term" class="field mono" required>
                </div>
                <div class="form-group span-full">
                    <label>Due Date</label>
                    <input type="date" name="due_date" id="add_due" class="field" required>
                </div>
            </div>
            <div class="form-footer">
                <button type="button" class="btn btn-ghost" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Post Charge</button>
            </div>
        </form>
    </div>
</div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Batch Issue Charges
════════════════════════════════════════════════════════════════════════════ -->
<div class="overlay" id="batchModal" onclick="handleOverlayClick(event,'batchModal')">
<div class="modal wide">
    <div class="modal-head">
        <span class="modal-title">Batch Issue Charges</span>
        <button class="modal-close" type="button" onclick="closeBatchModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <div class="modal-body">
        <form action="../process/addBatchOneTimeBill.php" method="POST" id="batchForm">
            <div class="batch-layout">
                <div class="batch-sidebar">
                    <div class="form-group">
                        <label>Charge Category</label>
                        <select name="bill_code_id" id="batch_bill_code_id" class="field" required onchange="autoFillBatchAmount()">
                            <option value="" disabled selected>Select bill code…</option>
                            <?php foreach ($bill_codes as $code):
                                $defaultAmt = $code['default_amount'] ?? 0;
                            ?>
                            <option value="<?= e($code['code_id']) ?>" data-amount="<?= $defaultAmt ?>">
                                <?= e($code['code']) ?> — <?= e($code['description']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Base Amount per Member (₱)</label>
                        <input type="number" step="0.01" name="base_amount" id="batch_base_amount"
                               class="field mono" placeholder="0.00" required oninput="updateBatchPrices()">
                    </div>
                    <div class="form-group">
                        <label>Date Issued</label>
                        <input type="date" name="bill_date" id="b_date" class="field" required>
                    </div>
                    <div class="form-group">
                        <label>Term (Days)</label>
                        <input type="number" name="term_days" id="b_term" class="field mono" value="7" required>
                    </div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date" id="b_due" class="field" required>
                    </div>
                    <hr class="divider">
                    <button type="submit" class="btn btn-success execute-btn" style="width:100%; justify-content:center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
                        Execute Batch Billing
                    </button>
                </div>

                <div class="batch-panel">
                    <div class="batch-filters">
                        <!-- BUG FIX: onchange now resets the other filter to prevent silent AND-filter with stale selection -->
                        <select id="f_city" class="field" onchange="onCityChange()" style="flex:1;">
                            <option value="" disabled selected>-- Select a City --</option>
                            <option value="all" style="font-weight:700; color:var(--brand);">Select All (All Cities)</option>
                            <?php foreach ($cities as $c):
                                $cityProper = ucwords(strtolower($c['city_name']));
                            ?>
                            <option value="<?= strtolower(e($c['city_name'])) ?>">
                                <?= e($cityProper) ?> (<?= (int)$c['member_count'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>

                        <select id="f_street" class="field" onchange="onStreetChange()" style="flex:1;">
                            <option value="" disabled selected>-- Select a Street --</option>
                            <option value="all" style="font-weight:700; color:var(--brand);">Select All (All Streets)</option>
                            <?php foreach ($addresses as $a):
                                $streetProper = ucwords(strtolower($a['street_name']));
                            ?>
                            <option value="<?= strtolower(e($a['street_name'])) ?>">
                                <?= e($streetProper) ?> (<?= (int)$a['member_count'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="search-wrap" style="flex:1;">
                            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" id="f_search" class="field" placeholder="Filter fetched list…" oninput="filterFetchedMembers()">
                        </div>
                    </div>

                    <div class="batch-table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:40px;"><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                                    <th>Acct #</th>
                                    <th>Name</th>
                                    <th>Discount</th>
                                    <th style="text-align:right;">Final Price</th>
                                </tr>
                            </thead>
                            <tbody id="batchBody">
                                <tr><td colspan="5" style="text-align:center; padding:20px; color:var(--text-3);">Select a city or street to load members.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="batch-count">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        Selected: <strong id="batchCount">0</strong> members
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Edit Individual Bill Record
════════════════════════════════════════════════════════════════════════════ -->
<div class="overlay" id="editModal" onclick="handleOverlayClick(event,'editModal')">
<div class="modal">
    <div class="modal-head">
        <span class="modal-title">Update Bill Record</span>
        <button class="modal-close" type="button" onclick="closeEditModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <div class="modal-body">
        <form action="../process/editOneTimeBill.php" method="POST">
            <input type="hidden" name="bill_id" id="edit_id">
            <div class="form-grid">
                <div class="form-group span-full">
                    <label>Consumer Account</label>
                    <input type="text" id="edit_name" class="field" readonly>
                </div>
                <div class="form-group span-full">
                    <label>Charge Category</label>
                    <input type="text" id="edit_code" class="field mono" readonly>
                </div>
                <div class="form-group span-2">
                    <label>Total Amount (₱)</label>
                    <input type="number" name="total_amount" id="edit_amount" class="field mono" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Date Issued</label>
                    <input type="date" name="bill_date" id="edit_date" class="field" readonly title="Date issued cannot be changed.">
                </div>
                <div class="form-group">
                    <label>Term (Days)</label>
                    <input type="number" name="term_days" id="edit_term" class="field mono" required>
                </div>
                <div class="form-group span-2">
                    <label>Due Date</label>
                    <input type="date" name="due_date" id="edit_due" class="field" required>
                </div>
                <div class="form-group span-full">
                    <label>Payment Status</label>
                    <select name="status" id="edit_status" class="field" required>
                        <option value="unpaid">Unpaid</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="form-footer">
                <button type="button" class="btn btn-ghost" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Batch Edit Selected Bills
════════════════════════════════════════════════════════════════════════════ -->
<div class="overlay" id="batchEditModal" onclick="handleOverlayClick(event,'batchEditModal')">
<div class="modal">
    <div class="modal-head">
        <span class="modal-title">Batch Edit Bills</span>
        <button class="modal-close" type="button" onclick="closeBatchEditModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <div class="modal-body">
        <div class="info-box">
            Editing <strong id="beSelectedCount">0</strong> selected bill(s). Changes apply to all selected records.
        </div>
        <div class="form-grid">
            <div class="form-group span-full">
                <label>New Payment Status <span style="color:var(--red);">*</span></label>
                <select id="be_status" class="field" required>
                    <option value="unpaid">Unpaid</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label>New Due Date <span style="color:var(--text-3); font-weight:400; text-transform:none;">(optional)</span></label>
                <input type="date" id="be_due" class="field">
                <span class="field-hint">Leave blank to keep each bill's existing due date.</span>
            </div>
            <div class="form-group">
                <label>New Term (Days) <span style="color:var(--text-3); font-weight:400; text-transform:none;">(optional)</span></label>
                <input type="number" id="be_term" class="field mono" placeholder="e.g. 30" oninput="syncBatchEditTerm()">
                <span class="field-hint">Auto-calculated from today → due date when due date is set.</span>
            </div>
        </div>
        <div class="form-footer">
            <button type="button" class="btn btn-ghost" onclick="closeBatchEditModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitBatchEdit()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Apply to Selected
            </button>
        </div>
    </div>
</div>
</div>

<script>
/* ═══════════════════════════════════════════════════════════════════════════
   DATE SYNC UTILITY
═══════════════════════════════════════════════════════════════════════════ */
function dateSync(dateId, termId, dueId) {
    const d = id => document.getElementById(id);
    function toDate(el) { return el.value ? new Date(el.value) : null; }
    function push() {
        const issue = toDate(d(dateId)), days = parseInt(d(termId).value, 10);
        if (!issue || isNaN(days)) return;
        const due = new Date(issue);
        due.setUTCDate(due.getUTCDate() + days);
        d(dueId).value = due.toISOString().split('T')[0];
    }
    function pull() {
        const issue = toDate(d(dateId)), due = toDate(d(dueId));
        if (!issue || !due) return;
        d(termId).value = Math.round((due - issue) / 86400000);
    }
    d(dateId).addEventListener('change', push);
    d(termId).addEventListener('input',  push);
    d(dueId).addEventListener('change',  pull);
}
dateSync('add_date',  'add_term',  'add_due');
dateSync('b_date',    'b_term',    'b_due');
dateSync('edit_date', 'edit_term', 'edit_due');

/* ═══════════════════════════════════════════════════════════════════════════
   INDIVIDUAL BILLING MODAL
═══════════════════════════════════════════════════════════════════════════ */
function autoFillAmount() {
    const sel = document.getElementById('ind_bill_code_id');
    if (!sel.value) return;
    const amt = parseFloat(sel.options[sel.selectedIndex].getAttribute('data-amount')) || 0;
    document.getElementById('ind_base').value = amt.toFixed(2);
    calcIndDiscount();
}

function calcIndDiscount() {
    const memberSel = document.getElementById('ind_member_id');
    const base      = parseFloat(document.getElementById('ind_base').value) || 0;
    const badge     = document.getElementById('ind_disc_badge');
    const total     = document.getElementById('ind_total');
    const section   = document.getElementById('ind_discount_section');
    const display   = document.getElementById('ind_discount_display');
    const hiddenId  = document.getElementById('ind_discount_id');

    // Reset display
    badge.style.display = 'none';
    total.value = base > 0 ? base.toFixed(2) : '';

    if (!memberSel.value) {
        section.style.display = 'none';
        display.innerHTML = '';
        hiddenId.value = '';
        return;
    }

    let discounts = [];
    try { discounts = JSON.parse(memberSel.options[memberSel.selectedIndex].dataset.discounts || '[]'); }
    catch(e) { discounts = []; }

    if (discounts.length === 0) {
        // No discounts — hide section silently
        section.style.display = 'none';
        display.innerHTML = '';
        hiddenId.value = '';
        _applyDiscountRate(base, 0, badge, total);
        return;
    }

    section.style.display = '';

    if (discounts.length === 1) {
        // Single discount — auto-apply, no user choice needed
        const d = discounts[0];
        hiddenId.value = d.id;
        display.innerHTML = `
            <div class="discount-auto-pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--amber)" stroke-width="2.5">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/>
                    <line x1="7" y1="7" x2="7.01" y2="7"/>
                </svg>
                <span class="d-type">${_esc(d.type)}</span>
                <span class="discount-tag" style="display:inline-flex; margin-left:0;">−${d.rate}%</span>
                <span class="d-auto">Auto-applied</span>
            </div>`;
        _applyDiscountRate(base, d.rate, badge, total);
    } else {
        // Multiple discounts — show picker, prompt user
        const optHtml = discounts.map(d =>
            `<option value="${d.id}" data-rate="${d.rate}">${_esc(d.type)} (−${d.rate}%)</option>`
        ).join('');
        display.innerHTML = `
            <select id="ind_discount_picker" class="field" onchange="onDiscountPick()">
                <option value="" data-rate="0">— No discount —</option>
                ${optHtml}
            </select>
            <p class="discount-multi-note">
                ⚠ This member has ${discounts.length} applicable discounts. Select one to apply or leave blank for none.
            </p>`;
        // Default: select the first discount automatically
        const picker = document.getElementById('ind_discount_picker');
        picker.selectedIndex = 1;
        onDiscountPick();
    }
}

function onDiscountPick() {
    const picker   = document.getElementById('ind_discount_picker');
    if (!picker) return;
    const base     = parseFloat(document.getElementById('ind_base').value) || 0;
    const badge    = document.getElementById('ind_disc_badge');
    const total    = document.getElementById('ind_total');
    const hiddenId = document.getElementById('ind_discount_id');
    const opt      = picker.options[picker.selectedIndex];
    const rate     = parseFloat(opt.dataset.rate) || 0;
    hiddenId.value = picker.value;
    _applyDiscountRate(base, rate, badge, total);
}

function _applyDiscountRate(base, rate, badge, total) {
    if (rate > 0) {
        badge.style.display = 'inline-block';
        badge.textContent   = `−${rate}%`;
    } else {
        badge.style.display = 'none';
    }
    total.value = (base - (base * (rate / 100))).toFixed(2);
}

function _esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
}

function openAddModal() {
    document.getElementById('addForm').reset();
    document.getElementById('ind_disc_badge').style.display  = 'none';
    document.getElementById('ind_discount_section').style.display = 'none';
    document.getElementById('ind_discount_display').innerHTML = '';
    document.getElementById('ind_discount_id').value          = '';
    const today = new Date();
    document.getElementById('add_date').valueAsDate = today;
    document.getElementById('add_term').value = 7;
    document.getElementById('add_term').dispatchEvent(new Event('input'));
    calcIndDiscount();
    document.getElementById('addModal').classList.add('open');
}
function closeAddModal() { document.getElementById('addModal').classList.remove('open'); }

/* ═══════════════════════════════════════════════════════════════════════════
   BATCH BILLING MODAL (Issue New Charges)
═══════════════════════════════════════════════════════════════════════════ */
function autoFillBatchAmount() {
    const sel = document.getElementById('batch_bill_code_id');
    if (!sel.value) return;
    const amt = parseFloat(sel.options[sel.selectedIndex].getAttribute('data-amount')) || 0;
    document.getElementById('batch_base_amount').value = amt.toFixed(2);
    updateBatchPrices();
}

function updateBatchPrices() {
    const base = parseFloat(document.getElementById('batch_base_amount').value) || 0;
    document.querySelectorAll('#batchBody .b-row').forEach(row => {
        const rate  = parseFloat(row.getAttribute('data-discount')) || 0;
        const final = base - (base * (rate / 100));
        row.querySelector('.final-price').textContent = '₱ ' + final.toFixed(2);
    });
}

/* BUG FIX: When city is changed, reset street filter to avoid stale AND-filter */
function onCityChange() {
    const street = document.getElementById('f_street');
    if (street.value && street.value !== '') {
        street.selectedIndex = 0; // reset to disabled "Select a Street"
    }
    fetchBatchMembers();
}

/* BUG FIX: When street is changed, reset city filter to avoid stale AND-filter */
function onStreetChange() {
    const city = document.getElementById('f_city');
    if (city.value && city.value !== '') {
        city.selectedIndex = 0; // reset to disabled "Select a City"
    }
    fetchBatchMembers();
}

async function fetchBatchMembers() {
    const city   = document.getElementById('f_city').value;
    const street = document.getElementById('f_street').value;
    const tbody  = document.getElementById('batchBody');
    const q      = document.getElementById('f_search').value.toLowerCase().trim();

    if (!city && !street) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:var(--text-3);">Select a city or street to load members.</td></tr>';
        updateCount();
        return;
    }

    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px;">Loading members…</td></tr>';

    try {
        const fetchCity   = city   === 'all' ? 'all' : encodeURIComponent(city);
        const fetchStreet = street === 'all' ? 'all' : encodeURIComponent(street);

        const response = await fetch(`../process/getMembersByStreet.php?city=${fetchCity}&street=${fetchStreet}`);
        const members  = await response.json();

        if (members.error) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red; padding:20px;">DB Error<br>${members.error}</td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        let count = 0;

        members.forEach(mem => {
            if (q && !mem.full_name.toLowerCase().includes(q)) return;
            count++;

            // Support both new `discounts[]` array and legacy `discount_rate` scalar
            const discounts   = Array.isArray(mem.discounts) ? mem.discounts : [];
            const legacyRate  = parseFloat(mem.discount_rate) || 0;
            // Best discount rate: use highest from discounts array, fall back to legacy
            const bestRate    = discounts.length > 0
                ? Math.max(...discounts.map(d => parseFloat(d.rate) || 0))
                : legacyRate;

            const tr = document.createElement('tr');
            tr.className = 'b-row';
            tr.setAttribute('data-name',     mem.full_name.toLowerCase());
            tr.setAttribute('data-discount',  bestRate);

            // Build discount display
            let discountHtml;
            if (discounts.length === 0 && legacyRate === 0) {
                discountHtml = `<span style="color:var(--text-3); font-size:.75rem;">None</span>`;
            } else if (discounts.length === 1) {
                discountHtml = `<span class="badge badge-amber" title="${discounts[0].type}">−${discounts[0].rate}%</span>`;
            } else if (discounts.length > 1) {
                // Multiple — show all types as stacked badges, best rate auto-selected
                const badges = discounts.map(d =>
                    `<span class="badge badge-amber" title="${d.type}" style="margin-right:3px;">−${d.rate}%</span>`
                ).join('');
                discountHtml = `<div style="display:flex; flex-wrap:wrap; gap:3px;">${badges}
                    <span style="font-size:.68rem; color:var(--text-3); align-self:center; margin-left:2px;">best applied</span>
                    </div>`;
            } else {
                // Legacy fallback
                discountHtml = `<span class="badge badge-amber">−${legacyRate}%</span>`;
            }

            tr.innerHTML = `
                <td><input type="checkbox" name="member_ids[]" value="${mem.pkey}" class="b-cb" onchange="updateCount()"></td>
                <td class="mono">${mem.pkey}</td>
                <td style="font-weight:600; white-space:normal; min-width:140px;">${mem.full_name}</td>
                <td>${discountHtml}</td>
                <td class="final-price mono" style="font-weight:700; text-align:right;">₱ 0.00</td>
            `;
            tbody.appendChild(tr);
        });

        if (count === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px;">No members found matching your search.</td></tr>';
        }

        document.getElementById('selectAll').checked = false;
        updateCount();
        updateBatchPrices();

    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:red;">Failed to fetch members. Check console.</td></tr>';
        console.error(err);
    }
}

function filterFetchedMembers() {
    const q = document.getElementById('f_search').value.toLowerCase().trim();
    document.querySelectorAll('.b-row').forEach(row => {
        row.style.display = row.dataset.name.includes(q) ? '' : 'none';
    });
    document.getElementById('selectAll').checked = false;
    updateCount();
}

function toggleAll(src) {
    document.querySelectorAll('.b-row').forEach(row => {
        if (row.style.display !== 'none') {
            row.querySelector('.b-cb').checked = src.checked;
        }
    });
    updateCount();
}

/* BUG FIX: Only count visible rows' checkboxes (hidden rows should not be counted) */
function updateCount() {
    const count = [...document.querySelectorAll('.b-cb:checked')]
        .filter(cb => cb.closest('tr').style.display !== 'none').length;
    document.getElementById('batchCount').textContent = count;
}

function openBatchModal() {
    document.getElementById('batchForm').reset();
    document.getElementById('f_search').value = '';
    document.getElementById('f_city').selectedIndex   = 0;
    document.getElementById('f_street').selectedIndex = 0;
    document.getElementById('selectAll').checked = false;
    document.getElementById('batchBody').innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:var(--text-3);">Select a city or street to load members.</td></tr>';
    const today = new Date();
    document.getElementById('b_date').valueAsDate = today;
    document.getElementById('b_term').value = 7;
    document.getElementById('b_term').dispatchEvent(new Event('input'));
    updateCount();
    document.getElementById('batchModal').classList.add('open');
}
function closeBatchModal() { document.getElementById('batchModal').classList.remove('open'); }

/* ═══════════════════════════════════════════════════════════════════════════
   EDIT INDIVIDUAL BILL MODAL
═══════════════════════════════════════════════════════════════════════════ */
function openEditModal(id, name, code, amount, date, term, due, status) {
    document.getElementById('edit_id').value     = id;
    document.getElementById('edit_name').value   = name;
    document.getElementById('edit_code').value   = code;
    document.getElementById('edit_amount').value = amount;
    document.getElementById('edit_date').value   = date;
    document.getElementById('edit_term').value   = term;
    document.getElementById('edit_due').value    = due;
    // Set the correct <option> as selected
    const sel = document.getElementById('edit_status');
    for (let i = 0; i < sel.options.length; i++) {
        sel.options[i].selected = (sel.options[i].value === status);
    }
    document.getElementById('editModal').classList.add('open');
}
function closeEditModal() { document.getElementById('editModal').classList.remove('open'); }

/* ═══════════════════════════════════════════════════════════════════════════
   LEDGER SELECTION & BATCH TOOLBAR
═══════════════════════════════════════════════════════════════════════════ */
function toggleLedgerAll(src) {
    document.querySelectorAll('.ledger-cb').forEach(cb => {
        const row = cb.closest('tr');
        if (row.style.display !== 'none') cb.checked = src.checked;
    });
    updateLedgerSelection();
}

function updateLedgerSelection() {
    const checked = [...document.querySelectorAll('.ledger-cb:checked')];
    const count   = checked.length;

    document.getElementById('ledgerSelCount').textContent = count;

    const toolbar = document.getElementById('ledgerBatchToolbar');
    if (count > 0) {
        toolbar.classList.add('visible');
    } else {
        toolbar.classList.remove('visible');
        document.getElementById('ledgerSelectAll').checked = false;
    }

    // Highlight selected rows
    document.querySelectorAll('.ledger-row').forEach(row => {
        const cb = row.querySelector('.ledger-cb');
        row.classList.toggle('row-selected', cb && cb.checked);
    });
}

function clearLedgerSelection() {
    document.querySelectorAll('.ledger-cb').forEach(cb => cb.checked = false);
    document.getElementById('ledgerSelectAll').checked = false;
    updateLedgerSelection();
}

/* ═══════════════════════════════════════════════════════════════════════════
   BATCH EDIT MODAL (for ledger rows)
═══════════════════════════════════════════════════════════════════════════ */
function openBatchEditModal() {
    const count = document.querySelectorAll('.ledger-cb:checked').length;
    if (count === 0) { alert('Please select at least one bill to edit.'); return; }
    document.getElementById('beSelectedCount').textContent = count;
    document.getElementById('be_status').value = 'unpaid';
    document.getElementById('be_due').value    = '';
    document.getElementById('be_term').value   = '';
    document.getElementById('batchEditModal').classList.add('open');
}
function closeBatchEditModal() { document.getElementById('batchEditModal').classList.remove('open'); }

/* Auto-calculate term when due date changes in batch-edit modal */
function syncBatchEditTerm() { /* term typed manually — no reverse sync needed */ }
document.addEventListener('DOMContentLoaded', () => {
    const beDue  = document.getElementById('be_due');
    const beTerm = document.getElementById('be_term');
    if (beDue && beTerm) {
        beDue.addEventListener('change', () => {
            if (!beDue.value) { beTerm.value = ''; return; }
            const today = new Date();
            const due   = new Date(beDue.value);
            beTerm.value = Math.round((due - today) / 86400000);
        });
    }
});

function submitBatchEdit() {
    const status = document.getElementById('be_status').value;
    if (!status) { alert('Please select a status.'); return; }

    const selectedIds = [...document.querySelectorAll('.ledger-cb:checked')].map(cb => cb.value);
    if (selectedIds.length === 0) { alert('No bills selected.'); return; }

    const due  = document.getElementById('be_due').value;
    const term = document.getElementById('be_term').value;

    // Populate hidden form
    const idsContainer = document.getElementById('batchEditIds');
    idsContainer.innerHTML = '';
    selectedIds.forEach(id => {
        const inp  = document.createElement('input');
        inp.type   = 'hidden';
        inp.name   = 'bill_ids[]';
        inp.value  = id;
        idsContainer.appendChild(inp);
    });
    document.getElementById('be_status_hidden').value = status;
    document.getElementById('be_due_hidden').value    = due;
    document.getElementById('be_term_hidden').value   = term;

    document.getElementById('batchEditFormHidden').submit();
}

/* ═══════════════════════════════════════════════════════════════════════════
   BATCH DELETE
═══════════════════════════════════════════════════════════════════════════ */
function confirmBatchDelete() {
    const selectedIds = [...document.querySelectorAll('.ledger-cb:checked')].map(cb => cb.value);
    if (selectedIds.length === 0) { alert('Please select at least one bill to delete.'); return; }

    const msg = `Permanently delete ${selectedIds.length} selected bill(s)? This cannot be undone.`;
    if (!confirm(msg)) return;

    const container = document.getElementById('batchDeleteIds');
    container.innerHTML = '';
    selectedIds.forEach(id => {
        const inp  = document.createElement('input');
        inp.type   = 'hidden';
        inp.name   = 'bill_ids[]';
        inp.value  = id;
        container.appendChild(inp);
    });
    document.getElementById('batchDeleteForm').submit();
}

/* ═══════════════════════════════════════════════════════════════════════════
   SINGLE-ROW ACTIONS
═══════════════════════════════════════════════════════════════════════════ */
function confirmDelete(id) {
    if (confirm('Delete this bill? This action cannot be undone.')) {
        document.getElementById('delete_bill_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   LEDGER SEARCH FILTER
═══════════════════════════════════════════════════════════════════════════ */
function filterLedger(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.ledger-row').forEach(r => {
        r.style.display = (r.dataset.name.includes(q) || r.dataset.accnt.includes(q)) ? '' : 'none';
    });
}

/* ═══════════════════════════════════════════════════════════════════════════
   OVERLAY HELPERS
═══════════════════════════════════════════════════════════════════════════ */
function handleOverlayClick(e, id) {
    if (e.target === document.getElementById(id)) {
        document.getElementById(id).classList.remove('open');
    }
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        ['addModal','batchModal','editModal','batchEditModal'].forEach(id =>
            document.getElementById(id).classList.remove('open')
        );
    }
});
</script>
</body>
</html>