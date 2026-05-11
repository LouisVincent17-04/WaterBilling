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
    <title>Configurations — COWASCO Waters</title>
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
            --shadow-hover:   0 10px 25px rgba(0,0,0,.08), 0 4px 10px rgba(0,0,0,.04);
        }

        html { height: 100%; font-size: 16px; }
        body {
            font-family: 'Sora', sans-serif;
            background: var(--off-white);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* ============================
           PAGE LAYOUT
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
           MANAGEMENT CARDS GRID
        ============================ */
        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }

        .mgmt-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            transition: transform .2s, box-shadow .2s, border-color .2s;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
        }

        .mgmt-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
            border-color: var(--border-focus);
        }

        /* Decorative top accent line */
        .mgmt-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--accent);
            opacity: 0;
            transition: opacity .2s;
        }
        .mgmt-card:hover::before { opacity: 1; }

        .mgmt-icon-wrap {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 16px;
            background: var(--off-white);
            border: 1px solid var(--border);
            transition: background .2s, color .2s, border-color .2s;
        }
        
        .mgmt-icon-wrap svg { width: 22px; height: 22px; color: var(--text-secondary); }

        .mgmt-card:hover .mgmt-icon-wrap {
            background: var(--accent);
            border-color: var(--accent);
        }
        .mgmt-card:hover .mgmt-icon-wrap svg {
            color: var(--white);
        }

        .mgmt-title {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -.02em;
            margin-bottom: 8px;
        }

        .mgmt-desc {
            font-size: .8125rem;
            color: var(--text-secondary);
            line-height: 1.5;
            flex: 1;
        }

        .mgmt-footer {
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--border);
            padding-top: 16px;
        }

        .mgmt-stat {
            display: flex; align-items: center; gap: 6px;
            font-size: .75rem; font-weight: 600; color: var(--text-muted);
        }
        .mgmt-stat-val {
            font-family: 'JetBrains Mono', monospace;
            color: var(--accent);
            background: rgba(26,26,46,.06);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .mgmt-action {
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--info);
            display: flex; align-items: center; gap: 4px;
        }
        .mgmt-action svg { width: 14px; height: 14px; transition: transform .2s; }
        .mgmt-card:hover .mgmt-action svg { transform: translateX(3px); }

        /* ============================
           RESPONSIVE
        ============================ */
        @media (max-width: 768px) {
            .page { padding: 20px 16px 60px; }
            .config-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php require_once '../common/navbar.php'; ?>

<!-- ========== MAIN CONTENT ========== -->
<div class="main-content">
<main class="page">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Configurations</h1>
            <p class="page-subtitle">Manage core system variables, rates, and operational periods</p>
        </div>
    </div>

    <!-- CONFIGURATION GRID -->
    <div class="config-grid">

        <!-- Bill Code Management -->
        <a href="#" class="mgmt-card">
            <div class="mgmt-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <div class="mgmt-title">Bill Code Management</div>
            <div class="mgmt-desc">Configure water rates, minimum charges, and tiered pricing structures (Residential, Commercial, Industrial).</div>
            <div class="mgmt-footer">
                <div class="mgmt-stat">Active Codes: <span class="mgmt-stat-val">4</span></div>
                <div class="mgmt-action">Manage <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg></div>
            </div>
        </a>

        <!-- Discounts Management -->
        <a href="discount_management.php" class="mgmt-card">
            <div class="mgmt-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    <line x1="15" y1="9" x2="9" y2="15"/><polyline points="9 9 9.01 9"/><polyline points="15 15 15.01 15"/>
                </svg>
            </div>
            <div class="mgmt-title">Discounts Management</div>
            <div class="mgmt-desc">Set up and modify rules for Senior Citizen (SC), PWD, and volume-based promotional discounts.</div>
            <div class="mgmt-footer">
                <div class="mgmt-stat">Rules Set: <span class="mgmt-stat-val">2</span></div>
                <div class="mgmt-action">Manage <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg></div>
            </div>
        </a>

        <!-- Bill Period Management -->
        <a href="#" class="mgmt-card">
            <div class="mgmt-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <div class="mgmt-title">Bill Period Management</div>
            <div class="mgmt-desc">Open, close, and monitor monthly billing cycles. Define cutoff dates, penalty dates, and disconnection schedules.</div>
            <div class="mgmt-footer">
                <div class="mgmt-stat">Current: <span class="mgmt-stat-val">Apr 2026</span></div>
                <div class="mgmt-action">Manage <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg></div>
            </div>
        </a>

        <!-- Zones / Barangay Management -->
        <a href="#" class="mgmt-card">
            <div class="mgmt-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                </svg>
            </div>
            <div class="mgmt-title">Zone & Routing Management</div>
            <div class="mgmt-desc">Manage reading zones, barangay assignments, and reading sequences for field meter readers.</div>
            <div class="mgmt-footer">
                <div class="mgmt-stat">Total Zones: <span class="mgmt-stat-val">12</span></div>
                <div class="mgmt-action">Manage <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg></div>
            </div>
        </a>

        <!-- System Users -->
        <a href="#" class="mgmt-card">
            <div class="mgmt-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="mgmt-title">User Roles & Access</div>
            <div class="mgmt-desc">Create staff accounts, assign roles (Tellers, Readers, Admins), and manage system access permissions.</div>
            <div class="mgmt-footer">
                <div class="mgmt-stat">Active Users: <span class="mgmt-stat-val">8</span></div>
                <div class="mgmt-action">Manage <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg></div>
            </div>
        </a>

        <!-- Fee Types -->
        <a href="#" class="mgmt-card">
            <div class="mgmt-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>
                </svg>
            </div>
            <div class="mgmt-title">Miscellaneous Fees</div>
            <div class="mgmt-desc">Define standard costs for installations, water meters, reconnections, and fitting charges.</div>
            <div class="mgmt-footer">
                <div class="mgmt-stat">Fee Types: <span class="mgmt-stat-val">15</span></div>
                <div class="mgmt-action">Manage <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg></div>
            </div>
        </a>

    </div>

</main>
</div><!-- /.main-content -->

</body>
</html>