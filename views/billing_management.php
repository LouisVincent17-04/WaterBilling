<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['full_name'] = $_SESSION['full_name'] ?? 'System Admin';
$_SESSION['username']  = $_SESSION['username'] ?? 'admin';
$_SESSION['role']      = $_SESSION['role'] ?? 'Administrator';

if (!function_exists('e')) {
    function e($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Management — COWASCO Waters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --white:          #ffffff;
            --off-white:      #f8f9fb;
            --border:         #e8eaed;
            --border-focus:   #1a1a2e;
            --text-primary:   #0f0f1a;
            --text-secondary: #6b7280;
            --accent:         #1a1a2e;
            --radius-sm:      8px;
            --radius:         14px;
            --shadow-sm:      0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
        }

        html { height: 100%; font-size: 16px; }
        body {
            font-family: 'Sora', sans-serif;
            background: var(--off-white);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* ============================
           PAGE HEADER & LAYOUT
        ============================ */
        .page { padding: 32px 32px 60px; animation: fadeIn .4s ease both; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        .page-header {
            display: flex; align-items: flex-end; justify-content: space-between;
            margin-bottom: 32px; flex-wrap: wrap; gap: 16px;
        }
        .page-title { font-size: 1.5rem; font-weight: 700; letter-spacing: -.03em; color: var(--text-primary); }
        .page-subtitle { font-size: .875rem; color: var(--text-secondary); margin-top: 4px; font-weight: 400; }

        /* ============================
           QUICK ACTIONS GRID
        ============================ */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .action-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            text-decoration: none;
            color: var(--text-primary);
            transition: transform .2s, box-shadow .2s, border-color .2s;
        }
        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-sm);
            border-color: var(--border-focus);
        }
        .ac-icon {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: var(--off-white); border: 1px solid var(--border);
            color: var(--text-secondary); transition: all .2s; flex-shrink: 0;
        }
        .ac-icon svg { width: 22px; height: 22px; }
        .action-card:hover .ac-icon {
            background: var(--accent); color: var(--white); border-color: var(--accent);
        }
        .ac-title { font-weight: 700; font-size: 1rem; margin-bottom: 6px; letter-spacing: -.01em; }
        .ac-desc { font-size: .8125rem; color: var(--text-secondary); line-height: 1.4; }

        @media (max-width: 768px) {
            .page { padding: 20px 16px; }
            .action-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php require_once '../common/navbar.php'; ?>

<div class="main-content">
<main class="page">

    <div class="page-header">
        <div>
            <h1 class="page-title">Billing Hub</h1>
            <p class="page-subtitle">Select a module to manage billing operations</p>
        </div>
    </div>

    <div class="action-grid">
        
        <a href="bill_period.php" class="action-card">
            <div class="ac-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div>
                <div class="ac-title">Bill Period</div>
                <div class="ac-desc">Open or close active billing months and set reading deadlines.</div>
            </div>
        </a>

        <a href="reading_entry.php" class="action-card">
            <div class="ac-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
            </div>
            <div>
                <div class="ac-title">Reading Entry</div>
                <div class="ac-desc">Register a new meter, connection, or consumer account.</div>
            </div>
        </a>

        <a href="generate_soa.php" class="action-card">
            <div class="ac-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <line x1="10" y1="9" x2="8" y2="9"></line>
                </svg>
            </div>
            <div>
                <div class="ac-title">Generate SOA</div>
                <div class="ac-desc">Compile readings, installments, and fees to post official Statements of Account.</div>
            </div>
        </a>

        <a href="recurring_bill.php" class="action-card">
            <div class="ac-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
            </div>
            <div>
                <div class="ac-title">Recurring Bills</div>
                <div class="ac-desc">Manage automated periodic charges like share capital or maintenance fees.</div>
            </div>
        </a>

        <a href="installment_bill.php" class="action-card">
            <div class="ac-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                    <line x1="7" y1="15" x2="7.01" y2="15"></line>
                    <line x1="11" y1="15" x2="15" y2="15"></line>
                </svg>
            </div>
            <div>
                <div class="ac-title">Installment Bills</div>
                <div class="ac-desc">Create amortization schedules and manage staggered payments.</div>
            </div>
        </a>

        <a href="one_time_billing.php" class="action-card">
            <div class="ac-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div>
                <div class="ac-title">One-Time Billing</div>
                <div class="ac-desc">Issue immediate ad-hoc charges like penalties, repairs, or materials.</div>
            </div>
        </a>

        <a href="bill_codes.php" class="action-card">
            <div class="ac-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <div>
                <div class="ac-title">Bill Codes Dictionary</div>
                <div class="ac-desc">Define standard categories, reference codes, and fee descriptions.</div>
            </div>
        </a>
        
    </div>

</main>
</div>

</body>
</html>