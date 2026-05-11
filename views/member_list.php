<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$_SESSION['full_name'] = $_SESSION['full_name'] ?? 'System Admin';
$_SESSION['username']  = $_SESSION['username'] ?? 'admin';
$_SESSION['role']      = $_SESSION['role'] ?? 'Administrator';

if (!function_exists('e')) {
    function e($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member List — COWASCO Waters</title>
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
            --accent-light: #e8f0fe;
            --success: #059669;
            --success-bg: #ecfdf5;
            --warning: #d97706;
            --danger: #dc2626;
            --danger-hover: #b91c1c;
            --radius-sm: 8px;
            --radius: 14px;
            --radius-lg: 20px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow: 0 8px 32px rgba(0,0,0,.12);
            --shadow-lg: 0 20px 60px rgba(0,0,0,.18);
            --transition: 0.2s cubic-bezier(0.4,0,0.2,1);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--off-white);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
        }

        /* ── Page Layout ── */
        .page {
            padding: 28px 28px 60px;
            animation: fadeUp .45s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 14px;
        }

        .page-title {
            font-size: clamp(1.2rem, 3vw, 1.55rem);
            font-weight: 700;
            letter-spacing: -.035em;
            color: var(--text-primary);
        }

        .page-subtitle {
            font-size: .78rem;
            color: var(--text-muted);
            margin-top: 2px;
            font-weight: 400;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            border: none;
            font-family: inherit;
            font-size: .8125rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: transform var(--transition), box-shadow var(--transition), background var(--transition), border-color var(--transition);
            white-space: nowrap;
        }

        .btn:active { transform: scale(0.97); }
        .btn svg { width: 15px; height: 15px; flex-shrink: 0; }

        .btn-primary {
            background: var(--accent);
            color: var(--white);
            box-shadow: 0 2px 10px rgba(26,26,46,.25);
        }
        .btn-primary:hover {
            background: #0f0f1a;
            box-shadow: 0 6px 20px rgba(26,26,46,.3);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--text-primary);
            border: 1.5px solid var(--border);
        }
        .btn-secondary:hover {
            border-color: var(--border-focus);
            background: var(--off-white);
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }
        .btn-danger:hover {
            background: var(--danger-hover);
            transform: translateY(-1px);
        }

        /* ── Controls Bar ── */
        .controls-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--white);
            padding: 14px 20px;
            border: 1px solid var(--border);
            border-radius: var(--radius) var(--radius) 0 0;
            border-bottom: none;
            flex-wrap: wrap;
            gap: 12px;
        }

        .search-wrapper {
            position: relative;
            flex: 1;
            min-width: 220px;
            max-width: 400px;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            width: 16px;
            height: 16px;
            pointer-events: none;
        }

        .search-control {
            width: 100%;
            padding: 9px 12px 9px 38px;
            font-family: 'Sora', sans-serif;
            font-size: .8375rem;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition);
            color: var(--text-primary);
            background: var(--off-white);
        }

        .search-control:focus {
            border-color: var(--border-focus);
            background: var(--white);
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        .search-control::placeholder { color: var(--text-muted); }

        /* ── Table Card ── */
        .card-table-wrap {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 0 0 var(--radius) var(--radius);
            box-shadow: var(--shadow-sm);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .8125rem;
        }

        thead tr {
            background: var(--off-white);
            border-bottom: 1.5px solid var(--border);
        }

        th {
            padding: 13px 20px;
            text-align: left;
            font-weight: 700;
            font-size: .69rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
        }

        td {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }

        tbody tr {
            transition: background var(--transition);
        }

        tbody tr:hover { background: #fafbfc; }

        .mono {
            font-family: 'JetBrains Mono', monospace;
            font-size: .8rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .badge-success { background: var(--success-bg); color: var(--success); }
        .badge-regular { background: var(--accent-light); color: var(--accent); }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--border);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .75rem;
            font-weight: 700;
            color: var(--text-secondary);
            border: 2px solid var(--white);
            box-shadow: var(--shadow-sm);
            flex-shrink: 0;
        }

        .avatar img { width: 100%; height: 100%; object-fit: cover; }

        .icon-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1.5px solid var(--border);
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all var(--transition);
            text-decoration: none;
        }

        .icon-btn:hover {
            border-color: var(--border-focus);
            color: var(--text-primary);
            background: var(--off-white);
            transform: translateY(-1px);
        }

        .icon-btn svg { width: 14px; height: 14px; }

        /* ── MODAL ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(10,10,20,0.55);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity .3s ease;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            padding: 16px;
            overflow-y: auto;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-overlay.active .modal-box {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .modal-box {
            background: var(--white);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 1020px;
            max-height: none;
            margin: auto;
            overflow: visible;
            box-shadow: var(--shadow-lg);
            transform: translateY(24px) scale(0.97);
            opacity: 0;
            transition: transform 0.35s cubic-bezier(0.165, 0.84, 0.44, 1), opacity 0.35s ease;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            padding: 22px 28px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            background: var(--white);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .modal-header-left { display: flex; flex-direction: column; gap: 2px; }

        .modal-title {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -.03em;
            color: var(--text-primary);
        }

        .modal-subtitle {
            font-size: .75rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        .modal-close-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--off-white);
            border: 1.5px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all var(--transition);
            flex-shrink: 0;
        }

        .modal-close-btn:hover {
            background: var(--border);
            color: var(--text-primary);
        }

        .modal-close-btn svg { width: 16px; height: 16px; }

        /* Tabs */
        .modal-tabs {
            display: flex;
            gap: 2px;
            padding: 10px 28px;
            border-bottom: 1px solid var(--border);
            background: var(--off-white);
            overflow-x: auto;
            flex-shrink: 0;
            scrollbar-width: none;
        }

        .modal-tabs::-webkit-scrollbar { display: none; }

        .modal-tab-link {
            display: block;
            padding: 8px 14px;
            border-radius: 7px;
            font-size: .785rem;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            transition: background var(--transition), color var(--transition);
            white-space: nowrap;
            letter-spacing: -.01em;
        }

        .modal-tab-link:hover {
            background: rgba(26,26,46,.06);
            color: var(--text-secondary);
        }

        .modal-tab-link.active {
            background: var(--white);
            color: var(--accent);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        /* Modal Content */
        .modal-content-area {
            flex: 1;
            padding: 0;
        }

        .modal-body {
            padding: 28px;
            display: none;
        }

        .modal-body.active { display: block; }

        /* Form Sections */
        .form-section {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .form-section:last-child { margin-bottom: 0; }

        .form-section-header {
            padding: 12px 20px;
            background: var(--off-white);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section-icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .form-section-icon svg {
            width: 14px;
            height: 14px;
            color: var(--white);
        }

        .form-section-title {
            font-size: .74rem;
            font-weight: 700;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .form-section-body { padding: 20px; }

        /* Profile Header */
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            border-radius: var(--radius);
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: ''; position: absolute; top: -30px; right: -30px;
            width: 120px; height: 120px; border-radius: 50%; background: rgba(255,255,255,.04);
        }

        .profile-header::after {
            content: ''; position: absolute; bottom: -20px; left: 40%;
            width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,.03);
        }

        .profile-photo-wrap { position: relative; flex-shrink: 0; }

        .profile-photo {
            width: 76px; height: 76px; border-radius: 50%; background: rgba(255,255,255,.12);
            display: flex; align-items: center; justify-content: center; overflow: hidden;
            border: 3px solid rgba(255,255,255,.2);
        }

        .profile-photo img { width: 100%; height: 100%; object-fit: cover; }
        .profile-photo svg { width: 36px; height: 36px; color: rgba(255,255,255,.5); }

        .photo-change-btn {
            position: absolute; bottom: 0; right: 0; width: 24px; height: 24px;
            background: var(--white); border-radius: 50%; border: none; display: flex;
            align-items: center; justify-content: center; cursor: pointer; box-shadow: var(--shadow-sm);
        }

        .photo-change-btn svg { width: 12px; height: 12px; color: var(--accent); }

        .profile-info { flex: 1; min-width: 0; }
        .profile-name { font-size: 1rem; font-weight: 700; color: var(--white); letter-spacing: -.02em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .profile-meta { display: flex; gap: 8px; margin-top: 6px; flex-wrap: wrap; }
        .profile-badge { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 20px; font-size: .65rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .profile-badge-success { background: rgba(5,150,105,.25); color: #6ee7b7; }
        .profile-badge-regular { background: rgba(232,240,254,.15); color: #93c5fd; }
        .profile-id { font-family: 'JetBrains Mono', monospace; font-size: .7rem; color: rgba(255,255,255,.4); margin-top: 4px; }

        /* Form Grid (Dynamic 12-Column System) */
        .grid-12 {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 14px;
        }

        .col-span-1  { grid-column: span 1; }
        .col-span-2  { grid-column: span 2; }
        .col-span-3  { grid-column: span 3; }
        .col-span-4  { grid-column: span 4; }
        .col-span-5  { grid-column: span 5; }
        .col-span-6  { grid-column: span 6; }
        .col-span-7  { grid-column: span 7; }
        .col-span-8  { grid-column: span 8; }
        .col-span-9  { grid-column: span 9; }
        .col-span-10 { grid-column: span 10; }
        .col-span-11 { grid-column: span 11; }
        .col-span-12 { grid-column: span 12; }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-label {
            font-size: .72rem;
            font-weight: 600;
            color: var(--text-secondary);
            letter-spacing: .01em;
        }

        .form-input {
            padding: 9px 13px;
            font-family: 'Sora', sans-serif;
            font-size: .845rem;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            outline: none;
            background: var(--white);
            color: var(--text-primary);
            transition: border-color var(--transition), box-shadow var(--transition), background var(--transition);
            width: 100%;
            appearance: none;
            -webkit-appearance: none;
        }

        .form-input:focus {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        .form-input::placeholder { color: var(--text-muted); }
        .form-input:read-only { background: var(--off-white); color: var(--text-secondary); cursor: default; border-color: var(--border); }

        select.form-input {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 32px;
        }

        /* ── XS Name Fields (Salutation / Suffix) ── */
        .col-span-1 .form-input,
        .col-span-2 .form-input {
            padding-left: 8px;
            padding-right: 8px;
            text-align: center;
        }
        .col-span-1 select.form-input,
        .col-span-2 select.form-input {
            padding-right: 22px;
            background-position: right 5px center;
        }

        /* Two-Column Layout */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Modal Footer */
        .modal-footer {
            padding: 18px 28px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            flex-shrink: 0;
            background: var(--off-white);
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        }

        .modal-footer-left { display: flex; gap: 10px; }
        .modal-footer-right { display: flex; gap: 10px; }

        /* ── Responsive Overrides ── */
        @media (max-width: 800px) {
            .two-col { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .page { padding: 16px 16px 60px; }
            .modal-header { padding: 18px 20px; }
            .modal-tabs { padding: 10px 20px; }
            .modal-body { padding: 18px; }
            .modal-footer { padding: 16px 20px; }
            .form-section-body { padding: 14px; }
            .profile-header { flex-direction: column; text-align: center; }
            .profile-meta { justify-content: center; }

            /* Collapse 12-column grid on mobile */
            .grid-12 > div { grid-column: span 12 !important; }

            .page-header { flex-direction: column; align-items: flex-start; }
            .btn span { display: none; }
        }
    </style>
</head>
<body>

    <?php require_once '../common/navbar.php'; ?>

    <div class="main-content">
        <main class="page">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Member List</h1>
                    <p class="page-subtitle">Manage and view all cooperative members</p>
                </div>
                <button class="btn btn-primary" onclick="openMemberModal('add')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Add New Member</span>
                </button>
            </div>

            <!-- Controls Bar -->
            <div class="controls-bar">
                <div class="search-wrapper">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" class="search-control" placeholder="Search by ID, name, or address…">
                </div>
            </div>

            <!-- Table -->
            <div class="card-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Member ID</th>
                            <th>Photo</th>
                            <th>Full Name</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Membership</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="mono">100001</td>
                            <td>
                                <div class="avatar">
                                    <img src="https://via.placeholder.com/150" alt="Glenn">
                                </div>
                            </td>
                            <td style="font-weight:600;">Glenn Revalde Tajanlangit</td>
                            <td style="color:var(--text-secondary);">Suba-Poblacion Cordova, Cebu</td>
                            <td><span class="badge badge-success">Active</span></td>
                            <td><span class="badge badge-regular">Regular</span></td>
                            <td>
                                <div style="display:flex; gap:8px;">
                                    <button class="icon-btn" title="Edit Member" onclick="openMemberModal('edit')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mono">100002</td>
                            <td>
                                <div class="avatar">MA</div>
                            </td>
                            <td style="font-weight:600;">Maria B. Santos</td>
                            <td style="color:var(--text-secondary);">Mingay, Cebu</td>
                            <td><span class="badge badge-success">Active</span></td>
                            <td><span class="badge badge-regular">Associate</span></td>
                            <td>
                                <div style="display:flex; gap:8px;">
                                    <button class="icon-btn" title="Edit Member" onclick="openMemberModal('edit')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </main>
    </div>


    <!-- ═══════════════════════════════════════
         MEMBER MODAL
    ═══════════════════════════════════════ -->
    <div class="modal-overlay" id="memberModalOverlay">
        <div class="modal-box">

            <!-- Header -->
            <div class="modal-header">
                <div class="modal-header-left">
                    <h2 class="modal-title" id="memberModalTitle">Add New Member</h2>
                    <span class="modal-subtitle" id="memberModalSubtitle">Fill in the member's information across all tabs before saving.</span>
                </div>
                <button class="modal-close-btn" onclick="closeMemberModal()" aria-label="Close modal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Tabs -->
            <nav class="modal-tabs" role="tablist" aria-label="Member form sections">
                <a href="#" class="modal-tab-link active" data-tab="personal" role="tab">Personal Information</a>
                <a href="#" class="modal-tab-link" data-tab="membership" role="tab">Membership Information</a>
            </nav>

            <form action="#" method="POST" id="memberForm" novalidate>
                <div class="modal-content-area">

                    <!-- ══ TAB 1: PERSONAL INFORMATION ══ -->
                    <div class="modal-body active" id="tab-personal" role="tabpanel">

                        <!-- Profile Header Strip -->
                        <div class="profile-header">
                            <div class="profile-photo-wrap">
                                <div class="profile-photo">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 19v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </div>
                                <button type="button" class="photo-change-btn" aria-label="Change photo">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                </button>
                            </div>
                            <div class="profile-info">
                                <div class="profile-name" id="profileNameDisplay">Glenn Revalde Tajanlangit</div>
                                <div class="profile-id" id="profileIdDisplay">Member ID: 100001</div>
                                <div class="profile-meta">
                                    <span class="profile-badge profile-badge-success">● Active</span>
                                    <span class="profile-badge profile-badge-regular">Regular</span>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Identity -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="form-section-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </div>
                                <span class="form-section-title">Identity</span>
                            </div>
                            <div class="form-section-body">
                                <div class="grid-12">
                                    <!-- Row 0: ID -->
                                    <div class="form-group col-span-12">
                                        <label class="form-label" for="member_id">Member ID</label>
                                        <input type="text" id="member_id" name="member_id" class="form-input mono" value="100001" readonly>
                                    </div>
                                    <!-- Row 1: Salutation, First Name, Last Name -->
                                    <div class="form-group col-span-2">
                                        <label class="form-label" for="salutation">Salutation</label>
                                        <select id="salutation" name="salutation" class="form-input">
                                            <option>Mr.</option><option>Ms.</option><option>Mrs.</option><option>Dr.</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-span-5">
                                        <label class="form-label" for="first_name">First Name</label>
                                        <input type="text" id="first_name" name="first_name" class="form-input" value="Glenn" placeholder="First name">
                                    </div>
                                    <div class="form-group col-span-5">
                                        <label class="form-label" for="last_name">Last Name</label>
                                        <input type="text" id="last_name" name="last_name" class="form-input" value="Tajanlangit" placeholder="Last name">
                                    </div>
                                    <!-- Row 2: Middle Name, Suffix, DOB -->
                                    <div class="form-group col-span-5">
                                        <label class="form-label" for="middle_name">Middle Name</label>
                                        <input type="text" id="middle_name" name="middle_name" class="form-input" value="Revalde" placeholder="Middle name">
                                    </div>
                                    <div class="form-group col-span-2">
                                        <label class="form-label" for="suffix">Suffix</label>
                                        <input type="text" id="suffix" name="suffix" class="form-input" placeholder="Jr., III…">
                                    </div>
                                    <div class="form-group col-span-5">
                                        <label class="form-label" for="dob">Date of Birth</label>
                                        <input type="date" id="dob" name="dob" class="form-input" value="1980-12-24">
                                    </div>
                                    <!-- Row 3: Gender, Civil Status, Place of Birth -->
                                    <div class="form-group col-span-3">
                                        <label class="form-label" for="gender">Gender</label>
                                        <select id="gender" name="gender" class="form-input">
                                            <option>Male</option><option>Female</option><option>Other</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-span-3">
                                        <label class="form-label" for="civil_status">Civil Status</label>
                                        <select id="civil_status" name="civil_status" class="form-input">
                                            <option>Married</option><option>Single</option><option>Widowed</option><option>Separated</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-span-6">
                                        <label class="form-label" for="place_of_birth">Place of Birth</label>
                                        <input type="text" id="place_of_birth" name="place_of_birth" class="form-input" value="Calan, Poblacion Cordova, Cebu" placeholder="City, Province">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Addresses & Contact -->
                        <div class="two-col">

                            <!-- Left: Addresses -->
                            <div style="display:flex; flex-direction:column; gap:16px;">

                                <!-- ── RESIDENCE ADDRESS ── -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <div class="form-section-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                        </div>
                                        <span class="form-section-title">Residence Address</span>
                                    </div>
                                    <div class="form-section-body">
                                        <div class="grid-12">
                                            <!-- Row 1: H/B No. + Barangay/Street -->
                                            <div class="form-group col-span-3">
                                                <label class="form-label">H/B No.</label>
                                                <input type="text" class="form-input" placeholder="No.">
                                            </div>
                                            <div class="form-group col-span-9">
                                                <label class="form-label">Barangay / Street</label>
                                                <input type="text" class="form-input" placeholder="Barangay or street" value="Cogon">
                                            </div>
                                            <!-- Row 2: Province + City/Municipality (widened) -->
                                            <div class="form-group col-span-5">
                                                <label class="form-label">Province</label>
                                                <input type="text" class="form-input" value="Cebu">
                                            </div>
                                            <div class="form-group col-span-7">
                                                <label class="form-label">City / Municipality</label>
                                                <input type="text" class="form-input" value="Cordova">
                                            </div>
                                            <!-- Row 3: Postal/Zip below (own row) -->
                                            <div class="form-group col-span-4">
                                                <label class="form-label">Postal / Zip Code</label>
                                                <input type="text" class="form-input" value="6017">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── PROVINCE ADDRESS ── -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <div class="form-section-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        </div>
                                        <span class="form-section-title">Province Address</span>
                                    </div>
                                    <div class="form-section-body">
                                        <div class="grid-12">
                                            <div class="form-group col-span-3">
                                                <label class="form-label">H/B No.</label>
                                                <input type="text" class="form-input" placeholder="No.">
                                            </div>
                                            <div class="form-group col-span-9">
                                                <label class="form-label">Barangay / Street</label>
                                                <input type="text" class="form-input" placeholder="Barangay or street">
                                            </div>
                                            <div class="form-group col-span-5">
                                                <label class="form-label">Province</label>
                                                <input type="text" class="form-input">
                                            </div>
                                            <div class="form-group col-span-7">
                                                <label class="form-label">City / Municipality</label>
                                                <input type="text" class="form-input">
                                            </div>
                                            <div class="form-group col-span-4">
                                                <label class="form-label">Postal / Zip Code</label>
                                                <input type="text" class="form-input">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── OFFICE / BUSINESS ADDRESS ── -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <div class="form-section-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                                        </div>
                                        <span class="form-section-title">Office / Business Address</span>
                                    </div>
                                    <div class="form-section-body">
                                        <div class="grid-12">
                                            <div class="form-group col-span-3">
                                                <label class="form-label">H/B No.</label>
                                                <input type="text" class="form-input" placeholder="No.">
                                            </div>
                                            <div class="form-group col-span-9">
                                                <label class="form-label">Barangay / Street</label>
                                                <input type="text" class="form-input" placeholder="Barangay or street">
                                            </div>
                                            <div class="form-group col-span-5">
                                                <label class="form-label">Province</label>
                                                <input type="text" class="form-input">
                                            </div>
                                            <div class="form-group col-span-7">
                                                <label class="form-label">City / Municipality</label>
                                                <input type="text" class="form-input">
                                            </div>
                                            <div class="form-group col-span-4">
                                                <label class="form-label">Postal / Zip Code</label>
                                                <input type="text" class="form-input">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <!-- /Left -->

                            <!-- Right: Contact, Education, Employment -->
                            <div style="display:flex; flex-direction:column; gap:16px;">

                                <!-- ── CONTACT DETAILS ── -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <div class="form-section-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.41 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.77a16 16 0 0 0 6.29 6.29l.94-.94a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                        </div>
                                        <span class="form-section-title">Contact Details</span>
                                    </div>
                                    <div class="form-section-body">
                                        <div class="grid-12">
                                            <div class="form-group col-span-12">
                                                <label class="form-label" for="contact_number">Contact Number</label>
                                                <input type="tel" id="contact_number" name="contact_number" class="form-input mono" value="+63 32 496 7302" placeholder="+63 9XX XXX XXXX" maxlength="16">
                                            </div>
                                            <div class="form-group col-span-12">
                                                <label class="form-label" for="primary_email">Primary Email Address</label>
                                                <input type="email" id="primary_email" name="primary_email" class="form-input" value="glennrevalde@yahoo.com" placeholder="email@example.com">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── EDUCATION BACKGROUND ── -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <div class="form-section-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                                        </div>
                                        <span class="form-section-title">Education Background</span>
                                    </div>
                                    <div class="form-section-body">
                                        <!-- FIX: was using undefined .form-grid — replaced with .grid-12 + col-span -->
                                        <div class="grid-12">
                                            <div class="form-group col-span-8">
                                                <label class="form-label" for="edu_attainment">Educational Attainment</label>
                                                <select id="edu_attainment" name="edu_attainment" class="form-input">
                                                    <option>College Graduate</option>
                                                    <option>High School Graduate</option>
                                                    <option>Vocational / Technical</option>
                                                    <option>Post Graduate</option>
                                                    <option>Elementary</option>
                                                    <option>No Formal Education</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-span-4">
                                                <label class="form-label" for="edu_year">Year Completed</label>
                                                <input type="text" id="edu_year" name="edu_year" class="form-input" value="2001" placeholder="YYYY">
                                            </div>
                                            <div class="form-group col-span-12">
                                                <label class="form-label" for="edu_course">Course / Degree / Level</label>
                                                <input type="text" id="edu_course" name="edu_course" class="form-input" value="Bachelor of Science in Accountancy" placeholder="e.g. BS Computer Science">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── EMPLOYMENT DETAILS ── -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <div class="form-section-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                                        </div>
                                        <span class="form-section-title">Employment Details</span>
                                    </div>
                                    <div class="form-section-body">
                                        <!-- FIX: was using undefined .form-grid — replaced with .grid-12 + col-span -->
                                        <div class="grid-12">
                                            <div class="form-group col-span-8">
                                                <label class="form-label" for="profession">Profession / Occupation</label>
                                                <select id="profession" name="profession" class="form-input">
                                                    <option>Manager</option>
                                                    <option>Employee</option>
                                                    <option>Business Owner</option>
                                                    <option>Professional</option>
                                                    <option>Self-employed</option>
                                                    <option>Retired</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-span-4">
                                                <label class="form-label" for="tin_no">TIN No.</label>
                                                <input type="text" id="tin_no" name="tin_no" class="form-input mono" value="202-753-99" placeholder="XXX-XXX-XXXXX" maxlength="15">
                                            </div>
                                            <div class="form-group col-span-12">
                                                <label class="form-label" for="org_affiliated">Organization Affiliated</label>
                                                <input type="text" id="org_affiliated" name="org_affiliated" class="form-input" value="BCBP" placeholder="e.g. Company or association name">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <!-- /Right -->

                        </div>
                        <!-- /two-col -->

                    </div>
                    <!-- ── END TAB 1 ── -->

                    <!-- ══ TAB 2: MEMBERSHIP INFORMATION ══ -->
                    <div class="modal-body" id="tab-membership" role="tabpanel">

                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="form-section-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </div>
                                <span class="form-section-title">Membership Details</span>
                            </div>

                            <div class="form-section-body">
                                <div class="grid-12" style="margin-bottom: 32px;">

                                    <!-- Passbook Block -->
                                    <div class="form-group col-span-12">
                                        <label class="form-label">Passbook Number</label>
                                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                            <input type="text" class="form-input mono" value="1" style="font-size: 1.15rem; font-weight: 700; color: var(--text-primary); max-width: 200px;">
                                            <button type="button" class="btn btn-secondary">Get Passbook Number</button>
                                        </div>
                                    </div>

                                    <div class="col-span-12 divider" style="margin: 10px 0;"></div>

                                    <!-- Detail Grid -->
                                    <div class="form-group col-span-4">
                                        <label class="form-label">Membership Date</label>
                                        <input type="date" class="form-input" value="2012-12-15">
                                    </div>
                                    <div class="form-group col-span-4">
                                        <label class="form-label">Type</label>
                                        <select class="form-input">
                                            <option>REGULAR</option>
                                            <option>ASSOCIATE</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-span-4">
                                        <label class="form-label">Status</label>
                                        <select class="form-input">
                                            <option>ACTIVE</option>
                                            <option>INACTIVE</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-span-4">
                                        <label class="form-label">PMES Date</label>
                                        <input type="date" class="form-input" value="2013-03-20">
                                    </div>
                                    <div class="form-group col-span-4">
                                        <label class="form-label">Cluster</label>
                                        <select class="form-input">
                                            <option>Savings & Credit Staff</option>
                                            <option>General Assembly</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-span-4">
                                        <label class="form-label">Segmentation</label>
                                        <select class="form-input">
                                            <option>Bronze</option>
                                            <option>Silver</option>
                                            <option>Gold</option>
                                        </select>
                                    </div>

                                </div>

                                <!-- Attendance Tables Grid -->
                                <div class="two-col">
                                    <div>
                                        <label class="form-label" style="display:block; margin-bottom: 8px;">Ownership Meeting Attendance</label>
                                        <div class="card-table-wrap">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>Meeting Date</th>
                                                        <th>Attendance</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr><td colspan="2" style="text-align:center; padding: 24px; color: var(--text-muted);">No records found</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label" style="display:block; margin-bottom: 8px;">General Assembly Attendance</label>
                                        <div class="card-table-wrap">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>Meeting Date</th>
                                                        <th>Attendance</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr><td colspan="2" style="text-align:center; padding: 24px; color: var(--text-muted);">No records found</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                    <!-- ── END TAB 2 ── -->

                </div><!-- /.modal-content-area -->

                <!-- Footer -->
                <div class="modal-footer">
                    <div class="modal-footer-left">
                        <button type="button" class="btn btn-danger btn-modal-danger" style="display:none;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                            Delete Member
                        </button>
                    </div>
                    <div class="modal-footer-right">
                        <button type="button" class="btn btn-secondary" onclick="closeMemberModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modal-save">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            Save Member
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>


    <script>
        /* ── Modal ── */
        const overlay    = document.getElementById('memberModalOverlay');
        const modalTitle = document.getElementById('memberModalTitle');
        const modalSub   = document.getElementById('memberModalSubtitle');
        const dangerBtn  = document.querySelector('.btn-modal-danger');
        const saveBtn    = document.querySelector('.btn-modal-save');

        function openMemberModal(mode) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';

            if (mode === 'add') {
                modalTitle.textContent  = 'Add New Member';
                modalSub.textContent    = 'Fill in the member\'s information across all tabs before saving.';
                saveBtn.innerHTML       = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:15px;height:15px;"><polyline points="20 6 9 17 4 12"/></svg> Save Member';
                dangerBtn.style.display = 'none';
                document.getElementById('member_id').value = '100003';
            } else {
                modalTitle.textContent  = 'Edit Member Details';
                modalSub.textContent    = 'Modify the member\'s information and save to update the record.';
                saveBtn.innerHTML       = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:15px;height:15px;"><polyline points="20 6 9 17 4 12"/></svg> Save Changes';
                dangerBtn.style.display = 'inline-flex';
            }
        }

        function closeMemberModal() {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        /* ── Tabs ── */
        document.querySelectorAll('.modal-tab-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetTab = document.getElementById('tab-' + this.dataset.tab);
                if (targetTab) {
                    document.querySelectorAll('.modal-tab-link').forEach(l => l.classList.remove('active'));
                    document.querySelectorAll('.modal-body').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    targetTab.classList.add('active');
                }
            });
        });

        /* ── Close on overlay click ── */
        overlay.addEventListener('click', function(e) {
            if (e.target === this) closeMemberModal();
        });

        /* ── Close on Escape ── */
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && overlay.classList.contains('active')) closeMemberModal();
        });

        /* ── Auto-format: Contact Number → +63 9XX XXX XXXX ── */
        const contactInput = document.getElementById('contact_number');
        if (contactInput) {
            contactInput.addEventListener('input', function () {
                let raw = this.value.replace(/\D/g, '');
                if (raw.startsWith('63')) raw = raw.slice(2);
                if (raw.startsWith('0'))  raw = raw.slice(1);
                let formatted = '';
                if (raw.length > 0) formatted  = '+63 ';
                if (raw.length > 0) formatted += raw.slice(0, 3);
                if (raw.length > 3) formatted += ' ' + raw.slice(3, 6);
                if (raw.length > 6) formatted += ' ' + raw.slice(6, 10);
                this.value = formatted.trimEnd();
            });
        }

        /* ── Auto-format: TIN → XXX-XXX-XXXXX ── */
        const tinInput = document.getElementById('tin_no');
        if (tinInput) {
            tinInput.addEventListener('input', function () {
                let raw = this.value.replace(/\D/g, '').slice(0, 11);
                let parts = [];
                if (raw.length > 0) parts.push(raw.slice(0, 3));
                if (raw.length > 3) parts.push(raw.slice(3, 6));
                if (raw.length > 6) parts.push(raw.slice(6, 11));
                this.value = parts.join('-');
            });
        }
    </script>
</body>
</html>