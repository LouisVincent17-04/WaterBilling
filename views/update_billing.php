<?php
// Ensure session is started for the navbar variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Mock variables (remove these if your login system already sets them)
$_SESSION['full_name'] = $_SESSION['full_name'] ?? 'Billing Clerk';
$_SESSION['username']  = $_SESSION['username'] ?? 'bclerk';
$_SESSION['role']      = $_SESSION['role'] ?? 'Administrator';

// Helper function expected by the navbar
if (!function_exists('e')) {
    function e($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Billing — COWASCO Waters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --white:          #ffffff;
            --off-white:      #f8f9fb;
            --border:         #e8eaed;
            --border-focus:   #1a1a2e;
            --text-primary:   #0f0f1a;
            --text-secondary: #6b7280;
            --text-muted:     #9ca3af;
            --accent:         #1a1a2e;
            --accent-mid:     #16213e;
            --accent-light:   #e8f0fe;
            --success:        #059669;
            --success-bg:     #ecfdf5;
            --error:          #dc2626;
            --error-bg:       #fef2f2;
            --warning:        #d97706;
            --warning-bg:     #fffbeb;
            --info:           #2563eb;
            --info-bg:        #eff6ff;
            --purple:         #7c3aed;
            --purple-bg:      #f5f3ff;
            --radius-sm:      8px;
            --radius:         14px;
            --radius-lg:      20px;
            --shadow-sm:      0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow:         0 4px 16px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
            --shadow-lg:      0 20px 60px rgba(0,0,0,.10), 0 4px 20px rgba(0,0,0,.06);
        }

        html { height: 100%; font-size: 16px; }
        body {
            font-family: 'Sora', sans-serif;
            background: var(--off-white);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* ============================
           MAIN CONTENT
        ============================ */
        /* The .main-content wrapper styles are now handled by navbar-sidebar.php, 
           so we start directly with .page styling */
        .page { padding: 32px 32px 60px; animation: fadeIn .4s ease both; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        .page-header {
            display: flex; align-items: flex-end; justify-content: space-between;
            margin-bottom: 28px; flex-wrap: wrap; gap: 16px;
        }
        .page-title { font-size: 1.5rem; font-weight: 700; letter-spacing: -.03em; color: var(--text-primary); }
        .page-subtitle { font-size: .875rem; color: var(--text-secondary); margin-top: 4px; font-weight: 400; }

        /* reading date badge */
        .reading-date-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 16px; background: var(--accent); color: #fff;
            border-radius: var(--radius-sm); font-size: .8125rem; font-weight: 600;
        }
        .reading-date-badge svg { width: 14px; height: 14px; }

        /* ============================
           BUTTONS
        ============================ */
        .btn {
            display: inline-flex; align-items: center; gap: 7px; padding: 10px 18px;
            font-family: inherit; font-size: .8125rem; font-weight: 600;
            border-radius: var(--radius-sm); border: none; cursor: pointer;
            text-decoration: none; transition: transform .15s, box-shadow .15s, background .15s, border-color .15s, color .15s;
            letter-spacing: -.01em;
        }
        .btn svg { width: 15px; height: 15px; flex-shrink: 0; }
        .btn-primary { background: var(--accent); color: var(--white); box-shadow: 0 2px 8px rgba(26,26,46,.2); }
        .btn-primary:hover { background: #0f0f1a; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(26,26,46,.28); }
        .btn-ghost { background: var(--white); color: var(--text-secondary); border: 1.5px solid var(--border); }
        .btn-ghost:hover { border-color: var(--border-focus); color: var(--text-primary); }
        .btn-success { background: var(--success-bg); color: var(--success); border: 1.5px solid #a7f3d0; }
        .btn-success:hover { background: #d1fae5; }
        .btn-warning { background: var(--warning-bg); color: var(--warning); border: 1.5px solid #fde68a; }
        .btn-warning:hover { background: #fef3c7; }
        .btn-sm { padding: 7px 13px; font-size: .775rem; }
        .btn-xs { padding: 5px 10px; font-size: .72rem; }

        /* ============================
           LAYOUT
        ============================ */
        .billing-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 24px;
            align-items: start;
        }

        /* ============================
           CARDS
        ============================ */
        .card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: var(--radius); box-shadow: var(--shadow-sm);
            overflow: hidden; margin-bottom: 20px;
        }
        .card:last-child { margin-bottom: 0; }
        .card-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 18px 22px 14px; border-bottom: 1px solid var(--border);
        }
        .card-head-left { display: flex; align-items: center; gap: 12px; }
        .card-head-title { font-size: .9375rem; font-weight: 700; color: var(--text-primary); letter-spacing: -.02em; }
        .card-head-sub { font-size: .78rem; color: var(--text-muted); margin-top: 2px; }
        .card-head-icon {
            width: 34px; height: 34px; border-radius: 9px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .card-head-icon svg { width: 16px; height: 16px; }
        .card-head-icon.blue  { background: var(--info-bg);    color: var(--info); }
        .card-head-icon.green { background: var(--success-bg); color: var(--success); }
        .card-head-icon.amber { background: var(--warning-bg); color: var(--warning); }
        .card-head-icon.purple{ background: var(--purple-bg);  color: var(--purple); }
        .card-head-icon.dark  { background: rgba(26,26,46,.08); color: var(--accent); }
        .card-body { padding: 22px; }

        /* ============================
           FORM ELEMENTS
        ============================ */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
        .form-grid.cols-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group.span-2 { grid-column: span 2; }
        .form-group.span-3 { grid-column: span 3; }
        .form-group.span-4 { grid-column: span 4; }

        label { font-size: .775rem; font-weight: 600; color: var(--text-secondary); letter-spacing: .01em; }
        label .req { color: var(--error); margin-left: 2px; }

        .form-control {
            width: 100%; padding: 9px 12px;
            font-family: 'Sora', sans-serif; font-size: .8375rem;
            color: var(--text-primary); background: var(--white);
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            transition: border-color .2s, box-shadow .2s; outline: none; appearance: none;
        }
        .form-control:focus { border-color: var(--border-focus); box-shadow: 0 0 0 3px rgba(26,26,46,.07); }
        .form-control::placeholder { color: var(--text-muted); }
        .form-control:disabled { background: var(--off-white); color: var(--text-secondary); cursor: not-allowed; }
        .form-control.mono { font-family: 'JetBrains Mono', monospace; font-size: .82rem; }

        select.form-control {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; padding-right: 36px; cursor: pointer;
        }

        .input-group { position: relative; display: flex; align-items: center; }
        .input-prefix { position: absolute; left: 12px; font-size: .8rem; font-weight: 600; color: var(--text-muted); pointer-events: none; font-family: 'JetBrains Mono', monospace; }
        .input-group .form-control.has-prefix { padding-left: 28px; }

        .form-hint { font-size: .72rem; color: var(--text-muted); margin-top: 2px; line-height: 1.5; }

        /* ============================
           STATUS BANNER
        ============================ */
        .no-reading-banner {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 16px; background: var(--error-bg);
            border: 1px solid #fecaca; border-radius: var(--radius-sm);
            margin-bottom: 20px; font-size: .8125rem; font-weight: 600; color: var(--error);
        }
        .no-reading-banner svg { width: 16px; height: 16px; flex-shrink: 0; }

        /* ============================
           ACCOUNT SEARCH
        ============================ */
        .account-search-wrap { position: relative; }
        .account-results {
            position: absolute; top: 100%; left: 0; right: 0;
            background: var(--white); border: 1.5px solid var(--border-focus);
            border-top: none; border-radius: 0 0 var(--radius-sm) var(--radius-sm);
            box-shadow: var(--shadow); z-index: 50; max-height: 220px; overflow-y: auto; display: none;
        }
        .account-results.open { display: block; }
        .account-result {
            display: flex; align-items: center; gap: 10px; padding: 10px 14px;
            cursor: pointer; transition: background .15s; border-bottom: 1px solid var(--border);
        }
        .account-result:last-child { border-bottom: none; }
        .account-result:hover { background: var(--off-white); }
        .account-result-avatar {
            width: 30px; height: 30px; border-radius: 50%; background: var(--accent);
            color: var(--white); display: flex; align-items: center; justify-content: center;
            font-size: .63rem; font-weight: 700; flex-shrink: 0;
        }
        .account-result-name { font-size: .8125rem; font-weight: 600; }
        .account-result-meta { font-size: .72rem; color: var(--text-muted); margin-top: 1px; }

        /* ============================
           READING DISPLAY
        ============================ */
        .reading-display {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 18px; background: var(--off-white);
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            margin-top: 16px;
        }
        .reading-col { text-align: center; }
        .reading-col-label { font-size: .7rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; }
        .reading-col-value { font-size: 1.375rem; font-weight: 700; color: var(--text-primary); font-family: 'JetBrains Mono', monospace; letter-spacing: -.02em; }
        .reading-col-value.highlight { color: var(--accent); }
        .reading-divider { width: 1px; height: 40px; background: var(--border); }
        .usage-arrow { display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .usage-arrow svg { width: 18px; height: 18px; color: var(--text-muted); }
        .usage-label { font-size: .65rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }

        /* ============================
           BILL AMOUNT DISPLAY
        ============================ */
        .bill-amount-display {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 18px; background: var(--accent);
            border-radius: var(--radius-sm); margin-top: 16px; color: #fff;
        }
        .bill-amount-label { font-size: .8rem; font-weight: 600; opacity: .7; }
        .bill-amount-value { font-size: 1.75rem; font-weight: 700; font-family: 'JetBrains Mono', monospace; letter-spacing: -.03em; }

        /* ============================
           CHECKBOX CARDS
        ============================ */
        .checkbox-group { display: flex; flex-direction: column; gap: 10px; }
        .checkbox-card {
            display: flex; align-items: flex-start; gap: 12px; padding: 13px 15px;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            cursor: pointer; transition: border-color .2s, background .2s;
            position: relative; user-select: none;
        }
        .checkbox-card:hover { border-color: var(--border-focus); background: var(--off-white); }
        .checkbox-card input[type="checkbox"] { position: absolute; opacity: 0; width: 0; height: 0; }
        .checkbox-card.checked { border-color: var(--accent); background: var(--accent-light); }
        .checkbox-card.checked-amber { border-color: var(--warning); background: var(--warning-bg); }
        .checkbox-card.checked-blue  { border-color: var(--info);    background: var(--info-bg); }
        .check-box {
            width: 19px; height: 19px; border: 2px solid var(--border);
            border-radius: 5px; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; margin-top: 1px; transition: border-color .2s, background .2s; background: var(--white);
        }
        .checkbox-card.checked       .check-box { background: var(--accent);   border-color: var(--accent); }
        .checkbox-card.checked-amber .check-box { background: var(--warning);  border-color: var(--warning); }
        .checkbox-card.checked-blue  .check-box { background: var(--info);     border-color: var(--info); }
        .check-box svg { width: 10px; height: 10px; stroke: var(--white); opacity: 0; transition: opacity .15s; }
        .checkbox-card.checked       .check-box svg,
        .checkbox-card.checked-amber .check-box svg,
        .checkbox-card.checked-blue  .check-box svg { opacity: 1; }
        .check-label { font-size: .8375rem; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 7px; }
        .check-desc { font-size: .74rem; color: var(--text-muted); margin-top: 3px; line-height: 1.5; }
        .check-badge {
            font-size: .63rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
            padding: 2px 7px; border-radius: 20px;
        }
        .check-badge.senior { background: #fef3c7; color: #b45309; }
        .check-badge.pwd    { background: #dbeafe; color: #1d4ed8; }
        .check-badge.reading{ background: #f0fdf4; color: #15803d; }
        .check-badge.discount{ background: #fdf4ff; color: #9333ea; }

        /* ============================
           FEES GRID
        ============================ */
        .fees-list { display: flex; flex-direction: column; gap: 0; }
        .fee-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 9px 0; border-bottom: 1px solid var(--border); font-size: .8125rem;
        }
        .fee-row:last-child { border-bottom: none; }
        .fee-label { color: var(--text-secondary); font-weight: 500; }
        .fee-value { font-family: 'JetBrains Mono', monospace; font-size: .8rem; font-weight: 600; color: var(--text-primary); }
        .fee-value.zero { color: var(--text-muted); }

        /* credit side */
        .credit-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 9px 0; border-bottom: 1px solid var(--border); font-size: .8125rem;
        }
        .credit-row:last-child { border-bottom: none; }
        .credit-label { color: var(--text-secondary); font-weight: 500; }
        .credit-value { font-family: 'JetBrains Mono', monospace; font-size: .82rem; font-weight: 700; color: var(--success); }

        /* ============================
           TASK MENU
        ============================ */
        .task-menu-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .task-btn {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px 8px; border-radius: var(--radius-sm);
            font-family: inherit; font-size: .775rem; font-weight: 600;
            border: 1.5px solid var(--border); background: var(--white);
            cursor: pointer; color: var(--text-secondary);
            transition: all .2s; text-decoration: none;
        }
        .task-btn svg { width: 13px; height: 13px; flex-shrink: 0; }
        .task-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }
        .task-btn.primary { background: var(--accent); color: #fff; border-color: var(--accent); }
        .task-btn.primary:hover { background: #0f0f1a; }
        .task-btn.danger { background: var(--error-bg); color: var(--error); border-color: #fecaca; }
        .task-btn.danger:hover { background: #fee2e2; border-color: var(--error); }

        /* ============================
           REPORTS MENU
        ============================ */
        .report-link {
            display: flex; align-items: center; gap: 9px; padding: 9px 0;
            border-bottom: 1px solid var(--border); text-decoration: none;
            font-size: .8rem; color: var(--info); font-weight: 500;
            transition: color .15s;
        }
        .report-link:last-child { border-bottom: none; }
        .report-link:hover { color: #1d4ed8; }
        .report-link svg { width: 13px; height: 13px; flex-shrink: 0; }

        /* ============================
           AMORTIZATION
        ============================ */
        .amort-block {
            padding: 12px 14px; background: var(--off-white);
            border: 1px solid var(--border); border-radius: var(--radius-sm); margin-top: 12px;
        }
        .amort-label { font-size: .72rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; }
        .amort-value { font-size: 1.1rem; font-weight: 700; font-family: 'JetBrains Mono', monospace; color: var(--text-primary); }
        .amort-link {
            display: inline-flex; align-items: center; gap: 5px; margin-top: 8px;
            font-size: .75rem; font-weight: 600; color: var(--info);
            text-decoration: none; transition: color .15s;
        }
        .amort-link:hover { color: #1d4ed8; }
        .amort-link svg { width: 12px; height: 12px; }

        /* ============================
           SPECIAL ZONE BANNER
        ============================ */
        .zone-banner {
            padding: 11px 16px; background: var(--warning-bg);
            border: 1px solid #fde68a; border-radius: var(--radius-sm);
            font-size: .78rem; font-weight: 600; color: var(--warning);
            margin-top: 16px; display: flex; align-items: center; gap: 8px;
        }
        .zone-banner svg { width: 14px; height: 14px; flex-shrink: 0; }

        /* ============================
           BILLING HISTORY TABLE
        ============================ */
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; font-size: .8rem; }
        thead tr { background: var(--off-white); }
        th {
            padding: 10px 14px; text-align: left; font-weight: 700;
            font-size: .72rem; color: var(--text-secondary); letter-spacing: .04em;
            text-transform: uppercase; white-space: nowrap; border-bottom: 1px solid var(--border);
        }
        td { padding: 11px 14px; border-bottom: 1px solid var(--border); color: var(--text-primary); white-space: nowrap; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: var(--off-white); }

        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 2px 8px; border-radius: 20px; font-size: .68rem; font-weight: 700;
        }
        .badge-success { background: var(--success-bg); color: var(--success); }
        .badge-warning { background: var(--warning-bg); color: var(--warning); }
        .badge-error   { background: var(--error-bg);   color: var(--error); }
        .badge-info    { background: var(--info-bg);    color: var(--info); }

        /* ============================
           SECTION DIVIDER
        ============================ */
        .section-divider {
            display: flex; align-items: center; gap: 12px; margin: 4px 0 16px;
        }
        .section-divider span {
            font-size: .68rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
            color: var(--text-muted); white-space: nowrap;
        }
        .section-divider::before, .section-divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        /* ============================
           FORM ACTIONS
        ============================ */
        .form-actions {
            display: flex; align-items: center; justify-content: space-between;
            padding: 18px 22px; background: var(--off-white);
            border-top: 1px solid var(--border); gap: 12px; flex-wrap: wrap;
        }
        .form-actions-right { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

        /* ============================
           RESPONSIVE
        ============================ */
        @media (max-width: 1100px) {
            .billing-layout { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .page { padding: 20px 16px 60px; }
            .page-header { margin-bottom: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-grid.cols-3, .form-grid.cols-4 { grid-template-columns: 1fr 1fr; }
            .form-group.span-2, .form-group.span-3, .form-group.span-4 { grid-column: span 1; }
            .task-menu-grid { grid-template-columns: 1fr 1fr; }
            .reading-col-value { font-size: 1.1rem; }
            .bill-amount-value { font-size: 1.375rem; }
        }
        @media (max-width: 480px) {
            .form-grid.cols-3, .form-grid.cols-4 { grid-template-columns: 1fr; }
            .task-menu-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<?php require_once '../common/navbar.php'; ?>

<div class="main-content">
<main class="page">

    <div class="page-header">
        <div>
            <h1 class="page-title">Update Billing</h1>
            <p class="page-subtitle">Adjust meter readings, charges, and customer billing status</p>
        </div>
        <div class="reading-date-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Reading Date: <strong id="readingDateLabel">04/02/2026</strong>
        </div>
    </div>

    <form id="updateBillingForm">
    <div class="billing-layout">

        <div class="left-col">

            <div class="card">
                <div class="card-head">
                    <div class="card-head-left">
                        <div class="card-head-icon blue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </div>
                        <div>
                            <div class="card-head-title">Search Account</div>
                            <div class="card-head-sub">Find consumer by name or account number</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="no-reading-banner" id="noReadingBanner">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        No Reading!!! — Please enter meter reading before saving.
                    </div>

                    <div class="form-grid">
                        <div class="form-group span-2 account-search-wrap">
                            <label>Search by Account Name</label>
                            <input type="text" id="accountSearch" class="form-control" placeholder="Type name or account number…" autocomplete="off">
                            <div class="account-results" id="accountResults"></div>
                        </div>
                        <div class="form-group span-2">
                            <label>Account Holder Name</label>
                            <input type="text" id="accountName" class="form-control" value="MULLER, LYN LEI T." readonly>
                        </div>
                        <div class="form-group">
                            <label>Zone / Barangay</label>
                            <input type="text" id="accountZone" class="form-control" value="SUBA-POBLACION, ARGAO" readonly>
                        </div>
                        <div class="form-group">
                            <label>Account No.</label>
                            <input type="text" id="accountNo" class="form-control mono" value="21445" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div class="card-head-left">
                        <div class="card-head-icon green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        </div>
                        <div>
                            <div class="card-head-title">Meter Reading</div>
                            <div class="card-head-sub">Enter present and previous readings</div>
                        </div>
                    </div>
                    <div>
                        <span style="font-size:.75rem; font-weight:700; background:var(--info-bg); color:var(--info); padding:3px 10px; border-radius:20px;">Rate Code B</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-grid cols-3">
                        <div class="form-group">
                            <label>Reading Date <span class="req">*</span></label>
                            <input type="date" id="readingDate" class="form-control" value="2026-04-02" onchange="syncReadingDate()">
                        </div>
                        <div class="form-group">
                            <label>Previous Reading Date</label>
                            <input type="date" id="prevReadingDate" class="form-control" value="2026-03-02">
                        </div>
                        <div class="form-group">
                            <label>Rate Code</label>
                            <select class="form-control" id="rateCode">
                                <option value="A">A — Residential</option>
                                <option value="B" selected>B — Commercial</option>
                                <option value="C">C — Industrial</option>
                                <option value="D">D — Government</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Present Reading <span class="req">*</span></label>
                            <input type="number" id="presReading" class="form-control mono" value="15" min="0" oninput="recalculate()">
                        </div>
                        <div class="form-group">
                            <label>Previous Reading</label>
                            <input type="number" id="prevReading" class="form-control mono" value="11" min="0" oninput="recalculate()">
                        </div>
                        <div class="form-group">
                            <label>Sequence No.</label>
                            <input type="number" id="seqNo" class="form-control mono" value="2" min="1">
                        </div>
                    </div>

                    <div class="reading-display">
                        <div class="reading-col">
                            <div class="reading-col-label">Previous</div>
                            <div class="reading-col-value" id="dispPrev">11</div>
                        </div>
                        <div class="usage-arrow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            <span class="usage-label">Usage</span>
                        </div>
                        <div class="reading-col">
                            <div class="reading-col-label">Present</div>
                            <div class="reading-col-value" id="dispPres">15</div>
                        </div>
                        <div class="reading-divider"></div>
                        <div class="reading-col">
                            <div class="reading-col-label">Consumption</div>
                            <div class="reading-col-value highlight" id="dispUsage">4 m³</div>
                        </div>
                    </div>

                    <div class="bill-amount-display">
                        <div>
                            <div class="bill-amount-label">Bill Amount</div>
                            <div style="font-size:.72rem; color:rgba(255,255,255,.5); margin-top:2px;">Before discounts & credits</div>
                        </div>
                        <div class="bill-amount-value" id="dispBillAmount">₱ 174.00</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div class="card-head-left">
                        <div class="card-head-icon amber">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </div>
                        <div>
                            <div class="card-head-title">Flags &amp; Options</div>
                            <div class="card-head-sub">Reading flags, discounts, and customer classification</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="section-divider"><span>Reading &amp; Discount</span></div>
                    <div class="checkbox-group" style="margin-bottom:20px;">
                        <label class="checkbox-card" id="readingFlagCard" onclick="toggleCheckbox('readingFlagCard','readingFlag','')">
                            <input type="checkbox" id="readingFlag">
                            <div class="check-box">
                                <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 6 5 9 10 3"/></svg>
                            </div>
                            <div>
                                <div class="check-label">Reading Flag <span class="check-badge reading">Flag</span></div>
                                <div class="check-desc">Mark this reading as flagged for review or re-read</div>
                            </div>
                        </label>
                        <label class="checkbox-card" id="discountFlagCard" onclick="toggleCheckbox('discountFlagCard','discountFlag','')">
                            <input type="checkbox" id="discountFlag">
                            <div class="check-box">
                                <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 6 5 9 10 3"/></svg>
                            </div>
                            <div>
                                <div class="check-label">Discount Flag <span class="check-badge discount">Discount</span></div>
                                <div class="check-desc">Enable volume-based discount for this billing period</div>
                            </div>
                        </label>
                    </div>

                    <div class="form-grid" style="margin-bottom:20px;">
                        <div class="form-group">
                            <label>Discount Volume (m³)</label>
                            <input type="number" id="discountVolume" class="form-control mono" value="0" min="0" oninput="recalculate()">
                        </div>
                        <div class="form-group">
                            <label>Additional Amount (₱)</label>
                            <div class="input-group">
                                <span class="input-prefix">₱</span>
                                <input type="number" id="additionalAmt" class="form-control has-prefix mono" value="0" min="0" step="0.01" oninput="recalculate()">
                            </div>
                        </div>
                    </div>

                    <div class="section-divider"><span>Customer Classification</span></div>
                    <div class="checkbox-group">
                        <label class="checkbox-card" id="scCard" onclick="toggleCheckbox('scCard','sc_discount','checked-amber')">
                            <input type="checkbox" id="sc_discount">
                            <div class="check-box">
                                <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 6 5 9 10 3"/></svg>
                            </div>
                            <div>
                                <div class="check-label">Senior Citizen <span class="check-badge senior">SC</span></div>
                                <div class="check-desc">Applies 20% discount on base bill amount per RA 9994</div>
                            </div>
                        </label>
                        <label class="checkbox-card" id="pwdCard" onclick="toggleCheckbox('pwdCard','pwd_discount','checked-blue')">
                            <input type="checkbox" id="pwd_discount">
                            <div class="check-box">
                                <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 6 5 9 10 3"/></svg>
                            </div>
                            <div>
                                <div class="check-label">PWD (Person with Disability) <span class="check-badge pwd">PWD</span></div>
                                <div class="check-desc">Applies 20% discount on base bill amount per RA 10754</div>
                            </div>
                        </label>
                    </div>

                    <div id="dualDiscountNote" style="display:none; margin-top:12px; padding:10px 13px; background:var(--warning-bg); border:1px solid #fde68a; border-radius:var(--radius-sm); font-size:.75rem; color:var(--warning); line-height:1.5;">
                        <strong>Note:</strong> When both SC and PWD are selected, only the higher qualifying discount (20%) is applied per DOLE/DOH guidelines.
                    </div>

                    <div class="zone-banner" id="zoneBanner" style="display:none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
                        FOR MINGAY AND BACONG ONLY — Rate KW/HR applies
                    </div>
                    <div class="form-grid" id="specialZoneFields" style="display:none; margin-top:16px;">
                        <div class="form-group">
                            <label>Rate KW/HR (₱)</label>
                            <div class="input-group">
                                <span class="input-prefix">₱</span>
                                <input type="number" id="rateKwHr" class="form-control has-prefix mono" placeholder="0.00" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div class="card-head-left">
                        <div class="card-head-icon dark">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <div>
                            <div class="card-head-title">Charges &amp; Fees</div>
                            <div class="card-head-sub">Installment, miscellaneous, and other fees</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-grid cols-3">
                        <div class="form-group">
                            <label>Meter Installation (₱)</label>
                            <div class="input-group">
                                <span class="input-prefix">₱</span>
                                <input type="number" id="meterInst" class="form-control has-prefix mono" value="0" step="0.01" min="0" oninput="recalculate()">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Installment Date</label>
                            <input type="date" id="installmentDate" class="form-control" value="2026-01-02">
                        </div>
                        <div class="form-group">
                            <label>Monthly Installment (₱)</label>
                            <div class="input-group">
                                <span class="input-prefix">₱</span>
                                <input type="number" id="moInstAmt" class="form-control has-prefix mono" value="0" step="0.01" min="0" oninput="recalculate()">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Installment Balance (₱)</label>
                            <div class="input-group">
                                <span class="input-prefix">₱</span>
                                <input type="number" id="instBalance" class="form-control has-prefix mono" value="0" step="0.01" min="0" oninput="recalculate()">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>ASW Balance (₱)</label>
                            <div class="input-group">
                                <span class="input-prefix">₱</span>
                                <input type="number" id="aswBalance" class="form-control has-prefix mono" value="0" step="0.01" min="0" oninput="recalculate()">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Membership Fee (₱)</label>
                            <div class="input-group">
                                <span class="input-prefix">₱</span>
                                <input type="number" id="membershipFee" class="form-control has-prefix mono" value="0" step="0.01" min="0" oninput="recalculate()">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Installation Fee (₱)</label>
                            <div class="input-group">
                                <span class="input-prefix">₱</span>
                                <input type="number" id="installationFee" class="form-control has-prefix mono" value="0" step="0.01" min="0" oninput="recalculate()">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Recon Fee (₱)</label>
                            <div class="input-group">
                                <span class="input-prefix">₱</span>
                                <input type="number" id="reconFee" class="form-control has-prefix mono" value="0" step="0.01" min="0" oninput="recalculate()">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Water Meter (₱)</label>
                            <div class="input-group">
                                <span class="input-prefix">₱</span>
                                <input type="number" id="waterMeter" class="form-control has-prefix mono" value="0" step="0.01" min="0" oninput="recalculate()">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Fittings Fee (₱)</label>
                            <div class="input-group">
                                <span class="input-prefix">₱</span>
                                <input type="number" id="fittingsFee" class="form-control has-prefix mono" value="0" step="0.01" min="0" oninput="recalculate()">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Fittings Fee Balance (₱)</label>
                            <div class="input-group">
                                <span class="input-prefix">₱</span>
                                <input type="number" id="fittingsBalance" class="form-control has-prefix mono" value="0" step="0.01" min="0" oninput="recalculate()">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Other Fees (₱)</label>
                            <div class="input-group">
                                <span class="input-prefix">₱</span>
                                <input type="number" id="otherFees" class="form-control has-prefix mono" value="0" step="0.01" min="0" oninput="recalculate()">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="billing_list.php" class="btn btn-ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        Back
                    </a>
                    <div class="form-actions-right">
                        <button type="button" class="btn btn-ghost" onclick="resetForm()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>
                            Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom:0;">
                <div class="card-head">
                    <div class="card-head-left">
                        <div class="card-head-icon purple">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                        </div>
                        <div>
                            <div class="card-head-title">Billing History</div>
                            <div class="card-head-sub">Account No. 21445 — Recent records</div>
                        </div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Acct No.</th>
                                <th>Rate</th>
                                <th>Flag</th>
                                <th>Prev Rdg Date</th>
                                <th>Pres Rdg Date</th>
                                <th>Present</th>
                                <th>Previous</th>
                                <th>Usage</th>
                                <th>Bill Amount</th>
                                <th>Status</th>
                                <th>Payment Date</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="mono" style="font-size:.78rem;">21445</td>
                                <td><span style="font-weight:700; color:var(--info);">B</span></td>
                                <td><input type="checkbox" disabled></td>
                                <td>12/02/2025</td>
                                <td>01/02/2026</td>
                                <td class="mono">1</td>
                                <td class="mono">0</td>
                                <td class="mono">1</td>
                                <td class="mono" style="font-weight:700;">₱174.00</td>
                                <td><span class="badge badge-success">PAID</span></td>
                                <td>01/15/2026</td>
                                <td class="mono" style="color:var(--text-muted);">01/</td>
                            </tr>
                            <tr>
                                <td class="mono" style="font-size:.78rem;">21445</td>
                                <td><span style="font-weight:700; color:var(--info);">B</span></td>
                                <td><input type="checkbox" disabled></td>
                                <td>01/02/2026</td>
                                <td>02/02/2026</td>
                                <td class="mono">3</td>
                                <td class="mono">1</td>
                                <td class="mono">2</td>
                                <td class="mono" style="font-weight:700;">₱174.00</td>
                                <td><span class="badge badge-success">PAID</span></td>
                                <td>02/12/2026</td>
                                <td class="mono" style="color:var(--text-muted);">02/</td>
                            </tr>
                            <tr>
                                <td class="mono" style="font-size:.78rem;">21445</td>
                                <td><span style="font-weight:700; color:var(--info);">B</span></td>
                                <td><input type="checkbox" disabled></td>
                                <td>02/02/2026</td>
                                <td>03/02/2026</td>
                                <td class="mono">11</td>
                                <td class="mono">3</td>
                                <td class="mono">8</td>
                                <td class="mono" style="font-weight:700;">₱520.00</td>
                                <td><span class="badge badge-warning">PENDING</span></td>
                                <td>—</td>
                                <td class="mono" style="color:var(--text-muted);">—</td>
                            </tr>
                            <tr>
                                <td class="mono" style="font-size:.78rem;">21445</td>
                                <td><span style="font-weight:700; color:var(--info);">B</span></td>
                                <td><input type="checkbox" disabled></td>
                                <td>03/02/2026</td>
                                <td>04/02/2026</td>
                                <td class="mono">15</td>
                                <td class="mono">11</td>
                                <td class="mono">4</td>
                                <td class="mono" style="font-weight:700; color:var(--accent);">₱174.00</td>
                                <td><span class="badge badge-info">CURRENT</span></td>
                                <td>—</td>
                                <td class="mono" style="color:var(--text-muted);">—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><div class="right-col">

            <div class="card">
                <div class="card-head">
                    <div class="card-head-left">
                        <div class="card-head-icon dark">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div>
                            <div class="card-head-title">Task Menu</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="task-menu-grid">
                        <button type="button" class="task-btn" onclick="navigateRecord('prev')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
                            Previous
                        </button>
                        <button type="button" class="task-btn" onclick="navigateRecord('next')">
                            Next
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                        <button type="button" class="task-btn" onclick="openFindDialog()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            Find
                        </button>
                        <button type="button" class="task-btn danger" onclick="confirmClose()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div class="card-head-left">
                        <div class="card-head-icon blue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        </div>
                        <div><div class="card-head-title">Reports</div></div>
                    </div>
                </div>
                <div class="card-body" style="padding-top:14px; padding-bottom:14px;">
                    <a href="#" class="report-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Ledger Per Account
                    </a>
                    <a href="#" class="report-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        Monthly Consumption
                    </a>
                    <a href="#" class="report-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Statement of Accounts
                    </a>
                    <a href="#" class="report-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        Accounts Receivable
                    </a>
                    <a href="#" class="report-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        No Current Meter Reading
                    </a>
                    <a href="#" class="report-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                        Notice of Disconnection
                    </a>
                    <a href="#" class="report-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        Memorandum by Zone
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div class="card-head-left">
                        <div class="card-head-icon green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
                        </div>
                        <div><div class="card-head-title">Credit Side</div></div>
                    </div>
                </div>
                <div class="card-body" style="padding-top:14px; padding-bottom:14px;">
                    <div class="fees-list">
                        <div class="fee-row">
                            <span class="fee-label">Total Credit</span>
                            <span class="fee-value" id="totalCredit">₱ 27,504.00</span>
                        </div>
                        <div class="fee-row">
                            <span class="fee-label">Credit Balance</span>
                            <span class="fee-value" id="creditBalance">₱ 27,504.00</span>
                        </div>
                        <div class="fee-row">
                            <span class="fee-label">Monthly Amortization</span>
                            <span class="fee-value" id="monthlyAmort">₱ 2,292.00</span>
                        </div>
                    </div>

                    <div class="amort-block">
                        <div class="amort-label">Prev Amortization Payment</div>
                        <div class="amort-value" id="prevAmortValue">₱ 0.00</div>
                        <a href="#" class="amort-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            Click to Pay Amortization
                        </a>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom:0;">
                <div class="card-head">
                    <div class="card-head-left">
                        <div class="card-head-icon amber">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <div>
                            <div class="card-head-title">Billing Summary</div>
                            <div class="card-head-sub">Live calculation</div>
                        </div>
                    </div>
                </div>
                <div class="card-body" style="padding-top:14px; padding-bottom:14px;">
                    <div class="fees-list">
                        <div class="fee-row">
                            <span class="fee-label">Bill Amount</span>
                            <span class="fee-value" id="sumBill">₱ 174.00</span>
                        </div>
                        <div class="fee-row">
                            <span class="fee-label">Meter Installation</span>
                            <span class="fee-value zero" id="sumMeterInst">₱ 0.00</span>
                        </div>
                        <div class="fee-row">
                            <span class="fee-label">Monthly Installment</span>
                            <span class="fee-value zero" id="sumMonthlyInst">₱ 0.00</span>
                        </div>
                        <div class="fee-row">
                            <span class="fee-label">Installment Balance</span>
                            <span class="fee-value zero" id="sumInstBalance">₱ 0.00</span>
                        </div>
                        <div class="fee-row">
                            <span class="fee-label">ASW Balance</span>
                            <span class="fee-value zero" id="sumAswBalance">₱ 0.00</span>
                        </div>
                        <div class="fee-row">
                            <span class="fee-label">Other Fees</span>
                            <span class="fee-value zero" id="sumOtherFees">₱ 0.00</span>
                        </div>
                        <div class="fee-row" id="sumScRow" style="display:none;">
                            <span class="fee-label" style="color:var(--warning);">SC Discount (20%)</span>
                            <span class="fee-value" style="color:var(--warning);" id="sumScDiscount">- ₱ 0.00</span>
                        </div>
                        <div class="fee-row" id="sumPwdRow" style="display:none;">
                            <span class="fee-label" style="color:var(--info);">PWD Discount (20%)</span>
                            <span class="fee-value" style="color:var(--info);" id="sumPwdDiscount">- ₱ 0.00</span>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 0 0; margin-top:4px; border-top:2px solid var(--border);">
                        <span style="font-size:.875rem; font-weight:700; color:var(--text-primary);">Amount Due</span>
                        <span style="font-size:1.25rem; font-weight:700; color:var(--accent); font-family:'JetBrains Mono',monospace; letter-spacing:-.03em;" id="sumTotal">₱ 174.00</span>
                    </div>
                </div>
                <div class="form-actions" style="flex-direction:column; gap:9px; padding:16px 20px;">
                    <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save Billing Record
                    </button>
                    <button type="button" class="btn btn-ghost" style="width:100%; justify-content:center;" onclick="resetForm()">
                        Discard &amp; Reset
                    </button>
                </div>
            </div>

        </div></div></form>

</main>
</div><div id="toast" style="
    position:fixed; bottom:28px; right:28px; z-index:999;
    background:var(--accent); color:#fff; padding:14px 20px;
    border-radius:var(--radius-sm); box-shadow:var(--shadow-lg);
    font-size:.8375rem; font-weight:600; display:none;
    align-items:center; gap:10px; max-width:340px;
    animation: slideUp .3s ease;
">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="toastMsg">Billing record saved successfully.</span>
</div>

<style>
@keyframes slideUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
.mono { font-family:'JetBrains Mono', monospace; }
</style>

<script>
/* ============================
   MOCK ACCOUNTS DATA
============================ */
const mockAccounts = [
    { id: '21445', name: 'MULLER, LYN LEI T.',    zone: 'SUBA-POBLACION, ARGAO',  rate: 'B', presR: 15, prevR: 11 },
    { id: '21446', name: 'SANTOS, MARIA B.',       zone: 'MINGAY, ARGAO',          rate: 'A', presR: 42, prevR: 38 },
    { id: '21447', name: 'REYES, JUAN A.',         zone: 'BACONG, ARGAO',          rate: 'B', presR: 88, prevR: 80 },
    { id: '21448', name: 'DELA CRUZ, JOSE E.',     zone: 'POBLACION, ARGAO',       rate: 'C', presR: 120, prevR: 100 },
    { id: '21449', name: 'LIM, ANA D.',            zone: 'SUBA-POBLACION, ARGAO',  rate: 'A', presR: 65, prevR: 60 },
];

const MINGAY_ZONES = ['MINGAY, ARGAO', 'BACONG, ARGAO'];

const searchInput   = document.getElementById('accountSearch');
const resultsPanel  = document.getElementById('accountResults');

searchInput.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    if (q.length < 2) { resultsPanel.classList.remove('open'); return; }
    const matches = mockAccounts.filter(a =>
        a.name.toLowerCase().includes(q) || a.id.includes(q)
    );
    if (!matches.length) {
        resultsPanel.innerHTML = '<div style="padding:12px 14px; font-size:.8rem; color:var(--text-muted);">No accounts found.</div>';
    } else {
        resultsPanel.innerHTML = matches.map(a => `
            <div class="account-result" onclick="selectAccount('${a.id}')">
                <div class="account-result-avatar">${a.name.split(',')[0].slice(0,2).toUpperCase()}</div>
                <div>
                    <div class="account-result-name">${a.name}</div>
                    <div class="account-result-meta">Acct #${a.id} &bull; ${a.zone}</div>
                </div>
            </div>
        `).join('');
    }
    resultsPanel.classList.add('open');
});

document.addEventListener('click', e => {
    if (!document.querySelector('.account-search-wrap').contains(e.target))
        resultsPanel.classList.remove('open');
});

function selectAccount(id) {
    const a = mockAccounts.find(x => x.id === id);
    if (!a) return;
    document.getElementById('accountName').value = a.name;
    document.getElementById('accountZone').value = a.zone;
    document.getElementById('accountNo').value   = a.id;
    document.getElementById('presReading').value = a.presR;
    document.getElementById('prevReading').value = a.prevR;
    searchInput.value = '';
    resultsPanel.classList.remove('open');

    // Show special zone fields for Mingay/Bacong
    const isSpecial = MINGAY_ZONES.includes(a.zone);
    document.getElementById('zoneBanner').style.display       = isSpecial ? 'flex' : 'none';
    document.getElementById('specialZoneFields').style.display = isSpecial ? 'grid' : 'none';

    recalculate();
    showToast(`Account ${a.id} loaded — ${a.name}`);
}

/* ============================
   READING DATE SYNC
============================ */
function syncReadingDate() {
    const d = document.getElementById('readingDate').value;
    if (d) {
        const dt = new Date(d);
        const formatted = String(dt.getMonth()+1).padStart(2,'0') + '/' + String(dt.getDate()).padStart(2,'0') + '/' + dt.getFullYear();
        document.getElementById('readingDateLabel').textContent = formatted;
    }
}

/* ============================
   CHECKBOX TOGGLE
============================ */
function toggleCheckbox(cardId, inputId, checkedClass) {
    const card  = document.getElementById(cardId);
    const input = document.getElementById(inputId);
    input.checked = !input.checked;

    // Remove all checked variants
    card.classList.remove('checked', 'checked-amber', 'checked-blue');
    if (input.checked && checkedClass) card.classList.add(checkedClass);
    else if (input.checked) card.classList.add('checked');

    // Show dual-discount note if both SC and PWD checked
    const scOn  = document.getElementById('sc_discount').checked;
    const pwdOn = document.getElementById('pwd_discount').checked;
    document.getElementById('dualDiscountNote').style.display = (scOn && pwdOn) ? 'block' : 'none';

    recalculate();
}

/* ============================
   LIVE RECALCULATE
============================ */
const fmt = n => '₱ ' + Math.abs(Number(n)).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
const BASE_RATE = 174; // ₱ per m³ (simplified)

function recalculate() {
    const pres   = parseInt(document.getElementById('presReading').value) || 0;
    const prev   = parseInt(document.getElementById('prevReading').value) || 0;
    const usage  = Math.max(0, pres - prev);

    document.getElementById('dispPrev').textContent   = prev;
    document.getElementById('dispPres').textContent   = pres;
    document.getElementById('dispUsage').textContent  = usage + ' m³';

    const billAmt       = usage > 0 ? BASE_RATE * usage : BASE_RATE;
    const meterInst     = parseFloat(document.getElementById('meterInst').value)      || 0;
    const moInst        = parseFloat(document.getElementById('moInstAmt').value)       || 0;
    const instBalance   = parseFloat(document.getElementById('instBalance').value)     || 0;
    const aswBalance    = parseFloat(document.getElementById('aswBalance').value)      || 0;
    const otherFees     = parseFloat(document.getElementById('otherFees').value)       || 0;

    const scOn  = document.getElementById('sc_discount').checked;
    const pwdOn = document.getElementById('pwd_discount').checked;
    const scAmt  = scOn  ? billAmt * 0.20 : 0;
    const pwdAmt = pwdOn ? billAmt * 0.20 : 0;
    const govDiscount = (scOn && pwdOn) ? Math.max(scAmt, pwdAmt) : scAmt + pwdAmt;

    const total = Math.max(0, billAmt - govDiscount + meterInst + moInst + instBalance + aswBalance + otherFees);

    document.getElementById('dispBillAmount').textContent = fmt(billAmt);
    document.getElementById('sumBill').textContent         = fmt(billAmt);
    document.getElementById('sumMeterInst').textContent    = fmt(meterInst);
    document.getElementById('sumMonthlyInst').textContent  = fmt(moInst);
    document.getElementById('sumInstBalance').textContent  = fmt(instBalance);
    document.getElementById('sumAswBalance').textContent   = fmt(aswBalance);
    document.getElementById('sumOtherFees').textContent    = fmt(otherFees);
    document.getElementById('sumScDiscount').textContent   = '- ' + fmt(scAmt);
    document.getElementById('sumPwdDiscount').textContent  = '- ' + fmt(scOn && pwdOn ? 0 : pwdAmt);
    document.getElementById('sumTotal').textContent        = fmt(total);

    document.getElementById('sumScRow').style.display   = scOn  ? '' : 'none';
    document.getElementById('sumPwdRow').style.display  = pwdOn ? '' : 'none';

    // Zero styling
    ['sumMeterInst','sumMonthlyInst','sumInstBalance','sumAswBalance','sumOtherFees'].forEach(id => {
        const el = document.getElementById(id);
        const val = parseFloat(el.textContent.replace(/[^\d.]/g,'')) || 0;
        el.classList.toggle('zero', val === 0);
    });
}

/* ============================
   TASK MENU ACTIONS
============================ */
let currentIndex = 0;
function navigateRecord(dir) {
    if (dir === 'next') {
        currentIndex = Math.min(currentIndex + 1, mockAccounts.length - 1);
    } else {
        currentIndex = Math.max(currentIndex - 1, 0);
    }
    selectAccount(mockAccounts[currentIndex].id);
}

function openFindDialog() {
    const acctNo = prompt('Enter Account Number to find:');
    if (acctNo) {
        const found = mockAccounts.find(a => a.id === acctNo.trim());
        if (found) selectAccount(found.id);
        else showToast('Account not found: ' + acctNo, true);
    }
}

function confirmClose() {
    if (confirm('Close this form? Unsaved changes will be lost.')) {
        window.history.back();
    }
}

/* ============================
   FORM SUBMIT
============================ */
document.getElementById('updateBillingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    showToast('Billing record updated successfully!');
});

/* ============================
   RESET
============================ */
function resetForm() {
    document.getElementById('presReading').value = 15;
    document.getElementById('prevReading').value = 11;
    ['meterInst','moInstAmt','instBalance','aswBalance','membershipFee',
     'installationFee','reconFee','waterMeter','fittingsFee','fittingsBalance','otherFees','additionalAmt','discountVolume']
     .forEach(id => { document.getElementById(id).value = 0; });
    ['sc_discount','pwd_discount','readingFlag','discountFlag'].forEach(id => {
        document.getElementById(id).checked = false;
    });
    ['scCard','pwdCard','readingFlagCard','discountFlagCard'].forEach(id => {
        document.getElementById(id).classList.remove('checked','checked-amber','checked-blue');
    });
    document.getElementById('dualDiscountNote').style.display = 'none';
    document.getElementById('noReadingBanner').style.display  = 'flex';
    recalculate();
}

/* ============================
   TOAST
============================ */
function showToast(msg, isError = false) {
    const t = document.getElementById('toast');
    const m = document.getElementById('toastMsg');
    m.textContent = msg;
    t.style.background = isError ? 'var(--error)' : 'var(--accent)';
    t.style.display = 'flex';
    setTimeout(() => { t.style.display = 'none'; }, 3500);
}

/* INIT */
recalculate();
</script>
</body>
</html>