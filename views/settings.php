<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$_SESSION['full_name'] = $_SESSION['full_name'] ?? 'System Admin';
$_SESSION['username']  = $_SESSION['username'] ?? 'admin';
$_SESSION['role']      = $_SESSION['role'] ?? 'Super Administrator';

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
    <title>System Settings — COWASCO Waters</title>
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
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
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
        .page { padding: 28px 28px 60px; animation: fadeUp .45s ease both; max-width: 1400px; margin: 0 auto; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 14px; }
        .page-title { font-size: clamp(1.2rem, 3vw, 1.55rem); font-weight: 700; letter-spacing: -.035em; color: var(--text-primary); }
        .page-subtitle { font-size: .78rem; color: var(--text-muted); margin-top: 4px; font-weight: 400; }

        /* ── Page Tabs ── */
        .page-tabs {
            display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 2px solid var(--border);
            overflow-x: auto; scrollbar-width: none;
        }
        .page-tabs::-webkit-scrollbar { display: none; }
        .page-tab-link {
            padding: 12px 20px; font-size: .875rem; font-weight: 600; color: var(--text-secondary);
            text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -2px;
            transition: color var(--transition), border-color var(--transition); white-space: nowrap;
        }
        .page-tab-link:hover { color: var(--text-primary); }
        .page-tab-link.active { color: var(--accent); border-bottom-color: var(--accent); }

        .tab-content { display: none; animation: fadeIn .3s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        /* ── Cards & Tables ── */
        .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 16px; }
        .card-title { font-size: 1.1rem; font-weight: 600; }

        .card-table-wrap { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-sm); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .8125rem; }
        thead tr { background: var(--off-white); border-bottom: 1.5px solid var(--border); }
        th { padding: 13px 20px; text-align: left; font-weight: 700; font-size: .69rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; }
        td { padding: 14px 20px; border-bottom: 1px solid var(--border); color: var(--text-primary); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #fafbfc; }

        /* ── Form Controls ── */
        .form-input {
            padding: 9px 13px; font-family: 'Sora', sans-serif; font-size: .845rem;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm); outline: none;
            background: var(--white); color: var(--text-primary); transition: border-color var(--transition), box-shadow var(--transition);
        }
        .form-input:focus { border-color: var(--border-focus); box-shadow: 0 0 0 3px var(--accent-light); }
        select.form-input { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; appearance: none; }
        
        .inline-select { padding: 6px 30px 6px 12px; font-size: .8rem; font-weight: 600; border-color: transparent; background-color: var(--off-white); cursor: pointer; }
        .inline-select:hover, .inline-select:focus { border-color: var(--border); background-color: var(--white); }

        .filter-bar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; align-items: center; }

        /* ── Buttons ── */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 10px 18px; border-radius: var(--radius-sm); border: none; font-family: inherit; font-size: .8125rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: transform var(--transition), box-shadow var(--transition), background var(--transition); white-space: nowrap; }
        .btn:active { transform: scale(0.97); }
        .btn-primary { background: var(--accent); color: var(--white); box-shadow: 0 2px 10px rgba(26,26,46,.25); }
        .btn-primary:hover { background: #0f0f1a; box-shadow: 0 6px 20px rgba(26,26,46,.3); }
        .btn-secondary { background: var(--white); color: var(--text-primary); border: 1.5px solid var(--border); }
        .btn-secondary:hover { border-color: var(--border-focus); background: var(--off-white); }
        .icon-btn { width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid var(--border); background: var(--white); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-secondary); transition: all var(--transition); flex-shrink: 0; }
        .icon-btn:hover { border-color: var(--border-focus); color: var(--text-primary); background: var(--off-white); }

        /* ── Micro Components ── */
        .mono { font-family: 'JetBrains Mono', monospace; font-size: .8rem; color: var(--text-secondary); font-weight: 500; }
        .badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 20px; font-size: .67rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .badge-module { background: var(--off-white); border: 1px solid var(--border); color: var(--text-secondary); }
        
        /* Custom Toggle Switch */
        .toggle-switch { position: relative; display: inline-block; width: 40px; height: 22px; margin-top: 2px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--border); transition: .3s; border-radius: 22px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,.2); }
        input:checked + .slider { background-color: var(--success); }
        input:checked + .slider:before { transform: translateX(18px); }

        /* User Profile Row */
        .user-profile-row { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--accent); color: var(--white); display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; flex-shrink: 0; }

        @media (max-width: 768px) {
            .page { padding: 16px 16px 60px; }
            .filter-bar .form-input { flex: 1; min-width: 100%; }
        }
    </style>
</head>
<body>

    <?php require_once '../common/navbar.php'; ?>

    <div class="main-content">
        <main class="page">

            <div class="page-header">
                <div>
                    <h1 class="page-title">System Settings</h1>
                    <p class="page-subtitle">Manage user access, security roles, and monitor system activity.</p>
                </div>
            </div>

            <!-- Page Level Tabs -->
            <nav class="page-tabs" aria-label="Settings tabs">
                <a href="#accounts" class="page-tab-link active" onclick="switchTab(event, 'tab-accounts')">Account Management</a>
                <a href="#audit" class="page-tab-link" onclick="switchTab(event, 'tab-audit')">Audit Logs</a>
            </nav>


            <!-- ==========================================
                 TAB 1: ACCOUNT MANAGEMENT
            =========================================== -->
            <div id="tab-accounts" class="tab-content active">
                <div class="card" style="padding: 0;">
                    <div class="card-header" style="margin: 20px 24px 16px;">
                        <h2 class="card-title">User Roles & Access</h2>
                        <button class="btn btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add New User
                        </button>
                    </div>

                    <div class="card-table-wrap" style="border: none; border-radius: 0 0 var(--radius) var(--radius);">
                        <table>
                            <thead>
                                <tr>
                                    <th>User Profile</th>
                                    <th>Username</th>
                                    <th>Assigned Role</th>
                                    <th>Account Status</th>
                                    <th>Last Login</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Super Admin (Current User usually) -->
                                <tr>
                                    <td>
                                        <div class="user-profile-row">
                                            <div class="avatar">SA</div>
                                            <div>
                                                <div style="font-weight: 700; color: var(--text-primary);">System Administrator</div>
                                                <div style="font-size: .7rem; color: var(--text-muted);">admin@cowasco.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="mono">admin</td>
                                    <td>
                                        <select class="form-input inline-select">
                                            <option selected>Super Admin</option>
                                            <option>Billing Admin</option>
                                            <option>Meter Reader</option>
                                            <option>Teller</option>
                                        </select>
                                    </td>
                                    <td>
                                        <label class="toggle-switch" title="Account Active">
                                            <input type="checkbox" checked disabled> <!-- Cannot disable self -->
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                    <td class="mono">Just now</td>
                                    <td>
                                        <div style="display:flex; gap:8px; justify-content: flex-end;">
                                            <button class="icon-btn" title="Edit User">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Billing Admin -->
                                <tr>
                                    <td>
                                        <div class="user-profile-row">
                                            <div class="avatar" style="background: #3b82f6;">MJ</div>
                                            <div>
                                                <div style="font-weight: 700; color: var(--text-primary);">Mary Jane Doe</div>
                                                <div style="font-size: .7rem; color: var(--text-muted);">mj.doe@cowasco.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="mono">mjdoe_billing</td>
                                    <td>
                                        <select class="form-input inline-select">
                                            <option>Super Admin</option>
                                            <option selected>Billing Admin</option>
                                            <option>Meter Reader</option>
                                            <option>Teller</option>
                                        </select>
                                    </td>
                                    <td>
                                        <label class="toggle-switch" title="Account Active">
                                            <input type="checkbox" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                    <td class="mono">Today, 08:30 AM</td>
                                    <td>
                                        <div style="display:flex; gap:8px; justify-content: flex-end;">
                                            <button class="icon-btn" title="Reset Password">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            </button>
                                            <button class="icon-btn" title="Edit User">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Teller -->
                                <tr>
                                    <td>
                                        <div class="user-profile-row">
                                            <div class="avatar" style="background: #10b981;">PT</div>
                                            <div>
                                                <div style="font-weight: 700; color: var(--text-primary);">Peter Teller</div>
                                                <div style="font-size: .7rem; color: var(--text-muted);">peter@cowasco.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="mono">peter_teller</td>
                                    <td>
                                        <select class="form-input inline-select">
                                            <option>Super Admin</option>
                                            <option>Billing Admin</option>
                                            <option>Meter Reader</option>
                                            <option selected>Teller</option>
                                        </select>
                                    </td>
                                    <td>
                                        <label class="toggle-switch" title="Account Suspended">
                                            <input type="checkbox"> <!-- Unchecked means disabled -->
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                    <td class="mono">04/28/2026</td>
                                    <td>
                                        <div style="display:flex; gap:8px; justify-content: flex-end;">
                                            <button class="icon-btn" title="Reset Password">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            </button>
                                            <button class="icon-btn" title="Edit User">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            <!-- ==========================================
                 TAB 2: AUDIT LOGS
            =========================================== -->
            <div id="tab-audit" class="tab-content">
                
                <div class="filter-bar">
                    <input type="date" class="form-input" style="width: 160px;" title="Start Date">
                    <span style="color: var(--text-muted);">to</span>
                    <input type="date" class="form-input" style="width: 160px;" title="End Date">
                    
                    <select class="form-input" style="width: 180px;">
                        <option value="">All Users</option>
                        <option value="admin">System Admin</option>
                        <option value="mjdoe">Mary Jane Doe</option>
                    </select>

                    <select class="form-input" style="width: 180px;">
                        <option value="">All Modules</option>
                        <option value="Auth">Authentication</option>
                        <option value="Billing">Billing Management</option>
                        <option value="Members">Member List</option>
                        <option value="Settings">Settings</option>
                    </select>

                    <button class="btn btn-secondary">Filter Logs</button>
                    <button class="btn btn-secondary" style="margin-left: auto;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Export CSV
                    </button>
                </div>

                <div class="card-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action Taken</th>
                                <th>Module</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="mono">05/02/2026 12:25:04 AM</td>
                                <td style="font-weight: 600;">admin</td>
                                <td><span style="color: var(--text-secondary);">Updated user role for</span> mjdoe_billing <span style="color: var(--text-secondary);">to</span> Billing Admin</td>
                                <td><span class="badge badge-module">Settings</span></td>
                                <td class="mono">192.168.1.45</td>
                            </tr>
                            <tr>
                                <td class="mono">05/01/2026 04:12:33 PM</td>
                                <td style="font-weight: 600;">mjdoe_billing</td>
                                <td><span style="color: var(--text-secondary);">Generated Batch Invoices for Zone:</span> Suba-Poblacion</td>
                                <td><span class="badge badge-module">Billing</span></td>
                                <td class="mono">192.168.1.102</td>
                            </tr>
                            <tr>
                                <td class="mono">05/01/2026 11:45:00 AM</td>
                                <td style="font-weight: 600;">admin</td>
                                <td><span style="color: var(--text-secondary);">Modified Water Rate ID #2</span> (Variable Tier)</td>
                                <td><span class="badge badge-module">Rates</span></td>
                                <td class="mono">192.168.1.45</td>
                            </tr>
                            <tr>
                                <td class="mono">05/01/2026 09:10:15 AM</td>
                                <td style="font-weight: 600;">peter_teller</td>
                                <td><span style="color: var(--danger);">Failed login attempt</span> (Invalid password)</td>
                                <td><span class="badge badge-module">Auth</span></td>
                                <td class="mono">112.205.x.x</td>
                            </tr>
                            <tr>
                                <td class="mono">04/30/2026 02:22:11 PM</td>
                                <td style="font-weight: 600;">admin</td>
                                <td><span style="color: var(--text-secondary);">Created new member profile:</span> 100003 (Juan Dela Cruz)</td>
                                <td><span class="badge badge-module">Members</span></td>
                                <td class="mono">192.168.1.45</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- UI Logic Script -->
    <script>
        function switchTab(evt, tabId) {
            evt.preventDefault();
            
            // Remove active class from all links and contents
            document.querySelectorAll('.page-tab-link').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            
            // Add active class to clicked link and target tab
            evt.currentTarget.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }
    </script>
</body>
</html>