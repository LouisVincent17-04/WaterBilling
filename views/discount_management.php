<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['full_name'] = $_SESSION['full_name'] ?? 'System Admin';
$_SESSION['username']  = $_SESSION['username'] ?? 'admin';
$_SESSION['role']      = $_SESSION['role'] ?? 'Administrator';

if (!function_exists('e')) {
    function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount Management — COWASCO Waters</title>
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
        
        /* General hover state for icons - specifically matching rates_management.php */
        .action-card:hover .ac-icon {
            background: #2563eb; color: var(--white); border-color: #2563eb;
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
            <h1 class="page-title">Discount Management</h1>
            <p class="page-subtitle">Select a module to configure system-wide discount policies and rules</p>
        </div>
    </div>

    <div class="action-grid">
        
        <a href="water_rate_discounts.php" class="action-card">
            <div class="ac-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="9" y1="15" x2="15" y2="9"></line>
                    <circle cx="9" cy="9" r="0.5"></circle>
                    <circle cx="15" cy="15" r="0.5"></circle>
                </svg>
            </div>
            <div>
                <div class="ac-title">Water Rate Discounts</div>
                <div class="ac-desc">Manage member-specific privileges (e.g., Senior Citizen, Board Member) applied directly to monthly water consumption.</div>
            </div>
        </a>

        <a href="general_discounts.php" class="action-card">
            <div class="ac-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                    <line x1="7" y1="7" x2="7.01" y2="7"></line>
                </svg>
            </div>
            <div>
                <div class="ac-title">General Discounts</div>
                <div class="ac-desc">Configure standardized discounts, flat reductions, and promo codes applicable to miscellaneous fees or penalties.</div>
            </div>
        </a>
        
    </div>

</main>
</div>

</body>
</html>