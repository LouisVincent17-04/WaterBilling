<?php
require_once '../database/config.php';

requireLogin(); // redirect to login if not authenticated

$flash = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — COWASCO Waters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
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
           MAIN CONTENT AREA
        ============================ */
        .page {
            padding: 36px 36px 48px;
            animation: fadeIn .4s ease both;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-title {
            font-size: 1.625rem;
            font-weight: 700;
            letter-spacing: -.03em;
            color: var(--text-primary);
        }
        .page-subtitle {
            font-size: .875rem;
            color: var(--text-secondary);
            margin-top: 4px;
            font-weight: 400;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 18px;
            font-family: inherit;
            font-size: .8125rem;
            font-weight: 600;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: transform .15s, box-shadow .15s, background .15s;
            letter-spacing: -.01em;
        }
        .btn svg { width: 15px; height: 15px; }
        .btn-primary {
            background: var(--accent);
            color: var(--white);
            box-shadow: 0 2px 8px rgba(26,26,46,.2);
        }
        .btn-primary:hover { background: #0f0f1a; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(26,26,46,.28); }
        .btn-ghost {
            background: var(--white);
            color: var(--text-secondary);
            border: 1.5px solid var(--border);
        }
        .btn-ghost:hover { border-color: var(--border-focus); color: var(--text-primary); }

        /* ============================
           FLASH ALERT
        ============================ */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 16px;
            border-radius: var(--radius-sm);
            font-size: .8125rem;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .alert svg { flex-shrink: 0; width: 16px; height: 16px; margin-top: 1px; }
        .alert-success { background: var(--success-bg); color: var(--success); border: 1px solid #a7f3d0; }

        /* ============================
           STATS GRID
        ============================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px 24px;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            transition: box-shadow .2s, transform .2s;
        }
        .stat-card:hover { box-shadow: var(--shadow); transform: translateY(-2px); }
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            border-radius: var(--radius) var(--radius) 0 0;
        }
        .stat-card.blue::after   { background: linear-gradient(90deg, #2563eb, #60a5fa); }
        .stat-card.green::after  { background: linear-gradient(90deg, #059669, #34d399); }
        .stat-card.amber::after  { background: linear-gradient(90deg, #d97706, #fbbf24); }
        .stat-card.red::after    { background: linear-gradient(90deg, #dc2626, #f87171); }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .stat-label {
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-muted);
        }
        .stat-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .stat-icon svg { width: 18px; height: 18px; }
        .stat-icon.blue   { background: var(--info-bg);    color: var(--info); }
        .stat-icon.green  { background: var(--success-bg); color: var(--success); }
        .stat-icon.amber  { background: var(--warning-bg); color: var(--warning); }
        .stat-icon.red    { background: var(--error-bg);   color: var(--error); }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -.04em;
            color: var(--text-primary);
            font-family: 'JetBrains Mono', monospace;
            line-height: 1;
            margin-bottom: 8px;
        }
        .stat-delta {
            font-size: .75rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
            color: var(--text-muted);
        }
        .stat-delta.up   { color: var(--success); }
        .stat-delta.down { color: var(--error); }
        .stat-delta svg  { width: 12px; height: 12px; }

        /* ============================
           CONTENT GRID
        ============================ */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 24px;
            margin-bottom: 24px;
        }

        /* ============================
           CARD BASE
        ============================ */
        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        .card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }
        .card-head-title {
            font-size: .9rem;
            font-weight: 700;
            letter-spacing: -.015em;
            color: var(--text-primary);
        }
        .card-head-sub {
            font-size: .75rem;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .card-link {
            font-size: .8rem;
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }
        .card-link:hover { text-decoration: underline; }

        /* ============================
           REVENUE CHART AREA
        ============================ */
        .chart-wrap {
            padding: 24px;
            height: 240px;
            position: relative;
        }
        canvas#revenueChart { width: 100% !important; height: 100% !important; }

        /* ============================
           INVOICE TABLE
        ============================ */
        .table-wrap { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .8125rem;
        }
        thead th {
            padding: 10px 24px;
            text-align: left;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--text-muted);
            background: var(--off-white);
            border-bottom: 1px solid var(--border);
        }
        tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: var(--off-white); }
        tbody td { padding: 13px 24px; color: var(--text-primary); vertical-align: middle; }

        .inv-id {
            font-family: 'JetBrains Mono', monospace;
            font-size: .78rem;
            font-weight: 500;
            color: var(--text-secondary);
        }
        .inv-client { font-weight: 600; }
        .inv-amount {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 500;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .02em;
        }
        .badge-dot { width: 6px; height: 6px; border-radius: 50%; }
        .badge-paid    { background: var(--success-bg); color: var(--success); }
        .badge-pending { background: var(--warning-bg); color: var(--warning); }
        .badge-overdue { background: var(--error-bg);   color: var(--error);   }
        .badge-draft   { background: #f1f5f9; color: var(--text-secondary); }
        .badge-paid    .badge-dot { background: var(--success); }
        .badge-pending .badge-dot { background: var(--warning); }
        .badge-overdue .badge-dot { background: var(--error); }
        .badge-draft   .badge-dot { background: var(--text-muted); }

        .action-btn {
            padding: 5px 11px;
            border-radius: 6px;
            border: 1.5px solid var(--border);
            background: none;
            font-family: inherit;
            font-size: .75rem;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            text-decoration: none;
            transition: border-color .15s, color .15s;
        }
        .action-btn:hover { border-color: var(--border-focus); color: var(--text-primary); }

        /* ============================
           ACTIVITY FEED
        ============================ */
        .activity-list { padding: 8px 0; }
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 20px;
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-item:hover { background: var(--off-white); }
        .activity-icon {
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .activity-icon svg { width: 14px; height: 14px; }
        .activity-icon.green  { background: var(--success-bg); color: var(--success); }
        .activity-icon.blue   { background: var(--info-bg);    color: var(--info); }
        .activity-icon.amber  { background: var(--warning-bg); color: var(--warning); }
        .activity-icon.red    { background: var(--error-bg);   color: var(--error); }

        .activity-body { flex: 1; min-width: 0; }
        .activity-text {
            font-size: .8125rem;
            color: var(--text-primary);
            line-height: 1.45;
        }
        .activity-text strong { font-weight: 600; }
        .activity-time {
            font-size: .72rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* ============================
           QUICK ACTIONS
        ============================ */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .quick-action-card {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            padding: 20px 16px;
            text-align: center;
            text-decoration: none;
            color: var(--text-primary);
            transition: border-color .2s, box-shadow .2s, transform .2s;
            cursor: pointer;
        }
        .quick-action-card:hover {
            border-color: var(--accent);
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }
        .qa-icon {
            width: 42px; height: 42px;
            border-radius: 12px;
            background: var(--off-white);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px;
            transition: background .2s;
        }
        .quick-action-card:hover .qa-icon { background: var(--accent-light); }
        .qa-icon svg { width: 20px; height: 20px; color: var(--accent); }
        .qa-label {
            font-size: .8rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .qa-desc {
            font-size: .72rem;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* ============================
           RESPONSIVE
        ============================ */
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .content-grid { grid-template-columns: 1fr; }
            .quick-actions { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .page { padding: 24px 20px 48px; }
            .stats-grid { grid-template-columns: 1fr; }
            .quick-actions { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<?php require_once '../common/navbar.php'; ?>

<div class="main-content">
<main class="page">

    <!-- Flash -->
    <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <?= e($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <div class="page-title">Good <?= (date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening')) ?>, <?= e(explode(' ', $_SESSION['full_name'] ?? 'there')[0]) ?> 👋</div>
            <div class="page-subtitle">Here's what's happening with your business today, <?= date('F j, Y') ?>.</div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="invoices.php?action=export" class="btn btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export
            </a>
            <a href="invoices.php?action=new" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Invoice
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-top">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
            </div>
            <div class="stat-value">$84,240</div>
            <div class="stat-delta up">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                +12.4% vs last month
            </div>
        </div>

        <div class="stat-card green">
            <div class="stat-top">
                <div class="stat-label">Paid Invoices</div>
                <div class="stat-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
            </div>
            <div class="stat-value">142</div>
            <div class="stat-delta up">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                +8 this month
            </div>
        </div>

        <div class="stat-card amber">
            <div class="stat-top">
                <div class="stat-label">Outstanding</div>
                <div class="stat-icon amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
            <div class="stat-value">$12,890</div>
            <div class="stat-delta down">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 9 12 15 6 9"/></svg>
                3 invoices pending
            </div>
        </div>

        <div class="stat-card red">
            <div class="stat-top">
                <div class="stat-label">Overdue</div>
                <div class="stat-icon red">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
            </div>
            <div class="stat-value">$3,450</div>
            <div class="stat-delta down">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 9 12 15 6 9"/></svg>
                2 invoices overdue
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="invoices.php?action=new" class="quick-action-card">
            <div class="qa-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
            </div>
            <div class="qa-label">Create Invoice</div>
            <div class="qa-desc">Bill a client instantly</div>
        </a>
        <a href="member_list.php?action=new" class="quick-action-card">
            <div class="qa-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
            </div>
            <div class="qa-label">Add Client</div>
            <div class="qa-desc">Register a new client</div>
        </a>
        <a href="payments.php?action=record" class="quick-action-card">
            <div class="qa-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            </div>
            <div class="qa-label">Record Payment</div>
            <div class="qa-desc">Log a received payment</div>
        </a>
        <a href="reports.php" class="quick-action-card">
            <div class="qa-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            </div>
            <div class="qa-label">View Reports</div>
            <div class="qa-desc">Analytics & summaries</div>
        </a>
    </div>

    <!-- Content Grid -->
    <div class="content-grid">

        <!-- Revenue Chart + Recent Invoices -->
        <div style="display:flex; flex-direction:column; gap:24px;">

            <!-- Revenue Chart -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <div class="card-head-title">Revenue Overview</div>
                        <div class="card-head-sub">Monthly revenue for the past 6 months</div>
                    </div>
                    <a href="reports.php" class="card-link">Full report →</a>
                </div>
                <div class="chart-wrap">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Recent Invoices -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <div class="card-head-title">Recent Invoices</div>
                        <div class="card-head-sub">Latest 5 invoices</div>
                    </div>
                    <a href="invoices.php" class="card-link">View all →</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Client</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $invoices = [
                                ['INV-0091', 'Acme Corp',       '$4,200.00', 'Apr 28, 2025', 'paid'],
                                ['INV-0090', 'Nova Tech Ltd',   '$1,850.00', 'May 05, 2025', 'pending'],
                                ['INV-0089', 'Green Studios',   '$3,100.00', 'Apr 15, 2025', 'overdue'],
                                ['INV-0088', 'Bright Agency',   '$750.00',   'May 10, 2025', 'draft'],
                                ['INV-0087', 'Sunrise Markets', '$6,400.00', 'Apr 30, 2025', 'pending'],
                            ];
                            foreach ($invoices as $inv):
                            ?>
                            <tr>
                                <td><span class="inv-id"><?= $inv[0] ?></span></td>
                                <td><span class="inv-client"><?= $inv[1] ?></span></td>
                                <td><span class="inv-amount"><?= $inv[2] ?></span></td>
                                <td style="color:var(--text-secondary); font-size:.8rem;"><?= $inv[3] ?></td>
                                <td>
                                    <span class="badge badge-<?= $inv[4] ?>">
                                        <span class="badge-dot"></span>
                                        <?= ucfirst($inv[4]) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="invoices.php?id=<?= strtolower(str_replace('-','', $inv[0])) ?>" class="action-btn">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div style="display:flex; flex-direction:column; gap:24px;">

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <div class="card-head-title">Activity</div>
                        <div class="card-head-sub">Recent account actions</div>
                    </div>
                </div>
                <div class="activity-list">
                    <?php
                    $activities = [
                        ['green', '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
                         '<strong>Acme Corp</strong> paid invoice INV-0091', '2 minutes ago'],
                        ['blue', '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
                         'New invoice <strong>INV-0091</strong> created', '1 hour ago'],
                        ['amber', '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
                         '<strong>Nova Tech</strong> invoice is due in 7 days', '3 hours ago'],
                        ['red', '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>',
                         '<strong>Green Studios</strong> payment overdue', 'Yesterday'],
                        ['blue', '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>',
                         'New client <strong>Bright Agency</strong> added', 'Yesterday'],
                    ];
                    foreach ($activities as $act):
                    ?>
                    <div class="activity-item">
                        <div class="activity-icon <?= $act[0] ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><?= $act[1] ?></svg>
                        </div>
                        <div class="activity-body">
                            <div class="activity-text"><?= $act[2] ?></div>
                            <div class="activity-time"><?= $act[3] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Top Clients -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <div class="card-head-title">Top Clients</div>
                        <div class="card-head-sub">By revenue this month</div>
                    </div>
                    <a href="clients.php" class="card-link">All →</a>
                </div>
                <div style="padding: 8px 0;">
                    <?php
                    $clients = [
                        ['Acme Corp',       '$24,800', 78],
                        ['Nova Tech Ltd',   '$18,200', 57],
                        ['Green Studios',   '$12,400', 39],
                        ['Sunrise Markets', '$9,100',  29],
                    ];
                    foreach ($clients as $c):
                    ?>
                    <div style="padding: 12px 20px; border-bottom: 1px solid var(--border);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:7px;">
                            <span style="font-size:.8125rem; font-weight:600;"><?= $c[0] ?></span>
                            <span style="font-family:'JetBrains Mono',monospace; font-size:.78rem; font-weight:500; color:var(--text-secondary);"><?= $c[1] ?></span>
                        </div>
                        <div style="height:4px; background:var(--off-white); border-radius:20px; overflow:hidden;">
                            <div style="height:100%; width:<?= $c[2] ?>%; background: linear-gradient(90deg, var(--accent), #4f6fd6); border-radius:20px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

</main>
</div><!-- /.main-content -->

<!-- Lightweight chart using Canvas API (no external dep) -->
<script>
(function () {
    const canvas = document.getElementById('revenueChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    // HiDPI
    const dpr = window.devicePixelRatio || 1;
    const rect = canvas.parentElement.getBoundingClientRect();
    canvas.width  = rect.width  * dpr;
    canvas.height = rect.height * dpr;
    ctx.scale(dpr, dpr);
    const W = rect.width, H = rect.height;

    const data   = [38200, 45800, 52100, 48600, 71300, 84240];
    const labels = ['Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'];
    const pad = { top: 20, right: 20, bottom: 36, left: 56 };
    const chartW = W - pad.left - pad.right;
    const chartH = H - pad.top  - pad.bottom;

    const max   = Math.max(...data) * 1.1;
    const xStep = chartW / (data.length - 1);

    const xOf = i => pad.left + i * xStep;
    const yOf = v => pad.top + chartH - (v / max) * chartH;

    // Gradient fill
    const grad = ctx.createLinearGradient(0, pad.top, 0, pad.top + chartH);
    grad.addColorStop(0, 'rgba(26,26,46,.15)');
    grad.addColorStop(1, 'rgba(26,26,46,.0)');

    // Grid lines
    ctx.strokeStyle = '#e8eaed';
    ctx.lineWidth = 1;
    for (let i = 0; i <= 4; i++) {
        const y = pad.top + (chartH / 4) * i;
        ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y);
        ctx.stroke();
        const val = Math.round(max - (max / 4) * i);
        ctx.fillStyle = '#9ca3af';
        ctx.font = '500 11px Sora, sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText('$' + (val >= 1000 ? (val/1000).toFixed(0)+'k' : val), pad.left - 8, y + 4);
    }

    // Area fill
    ctx.beginPath();
    ctx.moveTo(xOf(0), yOf(data[0]));
    for (let i = 1; i < data.length; i++) {
        const cx = (xOf(i-1) + xOf(i)) / 2;
        ctx.bezierCurveTo(cx, yOf(data[i-1]), cx, yOf(data[i]), xOf(i), yOf(data[i]));
    }
    ctx.lineTo(xOf(data.length - 1), pad.top + chartH);
    ctx.lineTo(xOf(0), pad.top + chartH);
    ctx.closePath();
    ctx.fillStyle = grad;
    ctx.fill();

    // Line
    ctx.beginPath();
    ctx.moveTo(xOf(0), yOf(data[0]));
    for (let i = 1; i < data.length; i++) {
        const cx = (xOf(i-1) + xOf(i)) / 2;
        ctx.bezierCurveTo(cx, yOf(data[i-1]), cx, yOf(data[i]), xOf(i), yOf(data[i]));
    }
    ctx.strokeStyle = '#1a1a2e';
    ctx.lineWidth = 2.5;
    ctx.lineJoin = 'round';
    ctx.stroke();

    // Dots + labels
    data.forEach((v, i) => {
        // dot
        ctx.beginPath();
        ctx.arc(xOf(i), yOf(v), 4.5, 0, Math.PI * 2);
        ctx.fillStyle = i === data.length - 1 ? '#1a1a2e' : '#fff';
        ctx.strokeStyle = '#1a1a2e';
        ctx.lineWidth = 2;
        ctx.fill(); ctx.stroke();

        // x-axis label
        ctx.fillStyle = '#9ca3af';
        ctx.font = '500 11px Sora, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(labels[i], xOf(i), H - pad.bottom + 18);
    });
})();
</script>

</body>
</html>