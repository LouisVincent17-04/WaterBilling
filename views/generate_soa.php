<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['full_name'] = $_SESSION['full_name'] ?? 'System Admin';
$_SESSION['username']  = $_SESSION['username'] ?? 'admin';
$_SESSION['role']      = $_SESSION['role'] ?? 'Administrator';

require_once '../database/config.php';
$pdo = getDB();

if (!function_exists('e')) {
    function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}

// Fetch open periods
try {
    $stmt = $pdo->query("SELECT * FROM bill_periods WHERE status = 'open' ORDER BY start_date DESC");
    $openPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $ex) { $openPeriods = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate SOA & Post Billing — COWASCO Waters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ─── RESET & TOKENS ─── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --white: #ffffff; --off-white: #f8f9fb; --border: #e8eaed; --border-focus: #1a1a2e;
            --text-primary: #0f0f1a; --text-secondary: #6b7280; --text-muted: #9ca3af;
            --accent: #1a1a2e; --accent-hover: #33334d;
            --success: #059669; --success-bg: #ecfdf5; --error: #dc2626; --error-bg: #fef2f2;
            --info: #2563eb; --info-bg: #eff6ff; --warning: #d97706; --warning-bg: #fffbeb;
            --radius-sm: 8px; --radius: 14px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06); --shadow-lg: 0 10px 40px rgba(0,0,0,.12);
        }
        html { height: 100%; font-size: 16px; }
        body { font-family: 'Sora', sans-serif; background: var(--off-white); color: var(--text-primary); min-height: 100vh; }
        
        /* ─── LAYOUT ─── */
        .page { padding: 32px 36px; max-width: 1200px; margin: 0 auto; animation: fadeIn .4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .page-header { margin-bottom: 24px; }
        .page-title { font-size: 1.5rem; font-weight: 700; letter-spacing: -.03em; }
        .page-subtitle { font-size: .875rem; color: var(--text-secondary); margin-top: 4px; }

        /* ─── TABS ─── */
        .tab-nav { display: flex; gap: 12px; margin-bottom: 24px; border-bottom: 2px solid var(--border); padding-bottom: 12px; }
        .tab-btn { background: none; border: none; font-family: 'Sora', sans-serif; font-size: .95rem; font-weight: 600; color: var(--text-secondary); padding: 8px 16px; cursor: pointer; border-radius: var(--radius-sm); transition: .2s; }
        .tab-btn:hover { background: var(--border); color: var(--text-primary); }
        .tab-btn.active { background: var(--accent); color: var(--white); }
        .tab-pane { display: none; animation: fadeIn .3s ease; }
        .tab-pane.active { display: block; }

        /* ─── CARDS & FORMS ─── */
        .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: visible; margin-bottom: 24px; }
        .card-head { padding: 18px 22px; border-bottom: 1px solid var(--border); background: #fafafa; border-radius: var(--radius) var(--radius) 0 0; font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: 8px;}
        .card-body { padding: 24px; }

        .form-control { width: 100%; padding: 12px 16px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: 'Sora', sans-serif; font-size: .9rem; outline: none; }
        .form-control:focus { border-color: var(--info); }
        
        /* ─── BUTTONS ─── */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; font-family: 'Sora', sans-serif; font-size: .9rem; font-weight: 600; border-radius: var(--radius-sm); cursor: pointer; transition: all .2s; border: none; }
        .btn-primary { background: var(--accent); color: var(--white); }
        .btn-success { background: var(--success); color: var(--white); }
        .btn-success:hover { background: #047857; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(5,150,105,.2); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; box-shadow: none !important; }

        /* ─── POSTING SUMMARY DASHBOARD ─── */
        .stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 20px; }
        .stat-box { border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 16px; text-align: center; }
        .stat-label { font-size: .75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .05em; }
        .stat-value { font-size: 1.75rem; font-weight: 700; color: var(--text-primary); font-family: 'JetBrains Mono', monospace; margin-top: 8px; }
        .stat-box.pending { border-color: var(--warning-border); background: var(--warning-bg); }
        .stat-box.pending .stat-value { color: var(--warning); }

        /* ─── SEARCH WIDGET ─── */
        .search-wrap { position: relative; z-index: 50; }
        .search-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: var(--white); border: 1.5px solid var(--info); border-top: none; border-radius: 0 0 var(--radius-sm) var(--radius-sm); box-shadow: var(--shadow-lg); max-height: 250px; overflow-y: auto; display: none; }
        .search-dropdown.open { display: block; }
        .search-result { padding: 12px 16px; cursor: pointer; border-bottom: 1px solid var(--border); }
        .search-result:hover { background: var(--info-bg); }
        .search-name { font-weight: 700; font-size: .9rem; }
        .search-meta { font-size: .75rem; color: var(--text-secondary); }

        /* ─── SOA RECEIPT PREVIEW ─── */
        .soa-receipt { border: 2px solid var(--border); border-radius: var(--radius-sm); padding: 32px; background: #fff; max-width: 600px; margin: 0 auto; box-shadow: var(--shadow-sm); }
        .soa-header { text-align: center; border-bottom: 2px dashed var(--border); padding-bottom: 16px; margin-bottom: 24px; }
        .soa-title { font-weight: 700; font-size: 1.25rem; letter-spacing: 1px; text-transform: uppercase; }
        .soa-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: .9rem; }
        .soa-row.category { font-weight: 700; color: var(--info); margin-top: 24px; font-size: .8rem; text-transform: uppercase; border-bottom: 1px solid var(--border); padding-bottom: 4px; }
        .soa-row .amount { font-family: 'JetBrains Mono', monospace; font-weight: 600; }
        .soa-total { display: flex; justify-content: space-between; border-top: 2px solid var(--text-primary); padding-top: 16px; margin-top: 24px; font-weight: 700; font-size: 1.25rem; }
        .soa-total .amount { color: var(--error); font-family: 'JetBrains Mono', monospace; }
        
        .alert { padding: 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-weight: 600; display: none; }
        .alert.success { background: var(--success-bg); color: var(--success); border: 1px solid var(--success-border); display: block; }
        .alert.error { background: var(--error-bg); color: var(--error); border: 1px solid var(--error-border); display: block; }

        .spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid #fff; border-top-color: transparent; border-radius: 50%; animation: spin .6s linear infinite; display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<?php require_once '../common/navbar.php'; ?>

<div class="main-content">
    <main class="page">
        
        <div class="page-header">
            <a href="billing_management.php" style="display:inline-flex; align-items:center; gap:6px; color:var(--text-secondary); text-decoration:none; font-size:.8rem; font-weight:600; margin-bottom:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="14" height="14"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Back to Billing Management
            </a>
            <h1 class="page-title">Generate SOA & Confirm Billing</h1>
            <p class="page-subtitle">Lock water readings and officially post the Statement of Accounts to the ledger.</p>
        </div>

        <div id="alertBox" class="alert"></div>

        <div class="tab-nav">
            <button class="tab-btn active" onclick="switchTab('tab-post')">1. Confirm & Post Billing</button>
            <button class="tab-btn" onclick="switchTab('tab-print')">2. Print Statements (SOA)</button>
        </div>

        <div id="tab-post" class="tab-pane active">
            <div class="card">
                <div class="card-head">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    Lock Readings & Post Bills
                </div>
                <div class="card-body">
                    <p style="color:var(--text-secondary); font-size:.9rem; margin-bottom: 20px;">
                        Select a billing period to officially convert draft/encoded water readings into locked Accounts Receivable. 
                        <strong>Once billed, readings can no longer be edited.</strong>
                    </p>

                    <div style="max-width: 400px;">
                        <label style="font-size:.8rem; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:8px;">Select Period to Post</label>
                        <select id="periodSelect" class="form-control" onchange="fetchPostingSummary()">
                            <option value="">— Select an open billing period —</option>
                            <?php foreach ($openPeriods as $p): ?>
                                <option value="<?= $p['period_id'] ?>"><?= e($p['bp_code']) ?> (<?= date('M j, Y', strtotime($p['end_date'])) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="postingSummary" style="display:none;">
                        <div class="stat-grid">
                            <div class="stat-box">
                                <div class="stat-label">Total Readings Encoded</div>
                                <div class="stat-value" id="statTotal">0</div>
                            </div>
                            <div class="stat-box pending">
                                <div class="stat-label">Pending / Draft Readings</div>
                                <div class="stat-value" id="statDraft">0</div>
                                <div style="font-size:.7rem; color:var(--warning); font-weight:600; margin-top:4px;">(Ready to Post)</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Already Billed</div>
                                <div class="stat-value" id="statBilled" style="color:var(--success);">0</div>
                            </div>
                        </div>

                        <div id="draftListContainer" style="margin-top: 24px; max-height: 350px; overflow-y: auto; border: 1px solid var(--border); border-radius: var(--radius-sm); display: none;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: .85rem;">
                                <thead style="background: var(--off-white); position: sticky; top: 0; z-index: 10;">
                                    <tr>
                                        <th style="padding: 10px 14px; border-bottom: 2px solid var(--border); color:var(--text-secondary);">Member Name</th>
                                        <th style="padding: 10px 14px; border-bottom: 2px solid var(--border); text-align: right; color:var(--text-secondary);">Prev</th>
                                        <th style="padding: 10px 14px; border-bottom: 2px solid var(--border); text-align: right; color:var(--text-secondary);">Pres</th>
                                        <th style="padding: 10px 14px; border-bottom: 2px solid var(--border); text-align: right; color:var(--info);">Cons (m³)</th>
                                    </tr>
                                </thead>
                                <tbody id="draftTableBody">
                                    </tbody>
                            </table>
                        </div>

                        <div style="margin-top: 32px; border-top: 1px solid var(--border); padding-top: 24px;">
                            <button class="btn btn-success" id="btnPost" onclick="postBilling()">
                                <span class="spinner" id="spinnerPost"></span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Confirm & Post <span id="btnDraftCount">0</span> Bills
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-print" class="tab-pane">
            <div class="card">
                <div class="card-head">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Generate Statement of Account
                </div>
                <div class="card-body">
                    
                    <div style="display:flex; gap:16px; margin-bottom: 32px; align-items: flex-end;">
                        <div style="flex:1;">
                            <label style="font-size:.8rem; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:8px;">Billing Period (For Water)</label>
                            <select id="soaPeriod" class="form-control">
                                <option value="">— Select period —</option>
                                <?php foreach ($openPeriods as $p): ?>
                                    <option value="<?= $p['period_id'] ?>" data-end="<?= $p['end_date'] ?>"><?= e($p['bp_code']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex:2;">
                            <label style="font-size:.8rem; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:8px;">Search Member</label>
                            <div class="search-wrap">
                                <input type="text" id="memberSearch" class="form-control" placeholder="Search by name or account number..." autocomplete="off">
                                <div class="search-dropdown" id="searchDropdown"></div>
                            </div>
                        </div>
                    </div>

                    <div id="soaContainer" style="display:none;">
                        <div class="soa-receipt" id="printableSOA">
                            <div class="soa-header">
                                <div class="soa-title">Statement of Account</div>
                                <div style="font-weight:600; margin-top:8px;" id="soaName">John Doe</div>
                                <div style="font-size:.85rem; color:var(--text-secondary);" id="soaAcct">Account: 10001</div>
                            </div>
                            
                            <div id="soaItems">
                                </div>

                            <div class="soa-total">
                                <span>TOTAL AMOUNT DUE</span>
                                <span class="amount" id="soaTotalAmount">₱ 0.00</span>
                            </div>
                        </div>

                        <div style="text-align:center; margin-top:24px;">
                            <button class="btn btn-primary" onclick="printSOA()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                Print Statement
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </main>
</div>

<script>
    // --- Utils ---
    const fmtPeso = n => '₱ ' + Number(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const e = str => String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));

    function showAlert(type, msg) {
        const box = document.getElementById('alertBox');
        box.className = `alert ${type}`;
        box.innerHTML = msg;
        setTimeout(() => box.className = 'alert', 5000);
    }
    
    function switchTab(tabId) {
        document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        event.target.classList.add('active');
    }

    // ==========================================
    // TAB 1: POST BILLING LOGIC
    // ==========================================
    function fetchPostingSummary() {
        const periodId = document.getElementById('periodSelect').value;
        if (!periodId) {
            document.getElementById('postingSummary').style.display = 'none';
            return;
        }

        // Fetch counts & list from backend
        fetch(`../process/postBilling.php?action=summary&period_id=${periodId}`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('statTotal').textContent = data.total;
                document.getElementById('statDraft').textContent = data.draft;
                document.getElementById('statBilled').textContent = data.billed;
                document.getElementById('btnDraftCount').textContent = data.draft;
                
                // Populate the preview table
                const tbody = document.getElementById('draftTableBody');
                const tableContainer = document.getElementById('draftListContainer');
                
                if (data.draft_list && data.draft_list.length > 0) {
                    tbody.innerHTML = data.draft_list.map(r => `
                        <tr>
                            <td style="padding: 10px 14px; border-bottom: 1px solid var(--border);">
                                <strong style="color:var(--text-primary);">${e(r.lastname)}, ${e(r.firstname)}</strong> 
                                <div style="font-size:.7rem; color:var(--text-muted);">Acct: ${e(r.account_number || r.member_id)}</div>
                            </td>
                            <td style="padding: 10px 14px; border-bottom: 1px solid var(--border); text-align: right; font-family: 'JetBrains Mono', monospace;">${Number(r.prev_reading).toFixed(2)}</td>
                            <td style="padding: 10px 14px; border-bottom: 1px solid var(--border); text-align: right; font-family: 'JetBrains Mono', monospace;">${Number(r.pres_reading).toFixed(2)}</td>
                            <td style="padding: 10px 14px; border-bottom: 1px solid var(--border); text-align: right; font-family: 'JetBrains Mono', monospace; font-weight:bold; color:var(--info);">${Number(r.consumption).toFixed(2)}</td>
                        </tr>
                    `).join('');
                    tableContainer.style.display = 'block';
                } else {
                    tableContainer.style.display = 'none';
                }

                document.getElementById('btnPost').disabled = (data.draft == 0);
                document.getElementById('postingSummary').style.display = 'block';
            })
            .catch(err => console.error("Error fetching summary:", err));
    }

    function postBilling() {
        const periodId = document.getElementById('periodSelect').value;
        const btn = document.getElementById('btnPost');
        if (!periodId || !confirm("Are you sure? This will permanently lock all draft readings for this period and post them to the ledger.")) return;

        btn.disabled = true;
        document.getElementById('spinnerPost').style.display = 'inline-block';

        fetch(`../process/postBilling.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'post', period_id: periodId })
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            document.getElementById('spinnerPost').style.display = 'none';
            if (res.success) {
                showAlert('success', `Success! ${res.posted_count} readings have been locked and posted as official bills.`);
                fetchPostingSummary(); // Refresh stats
            } else {
                showAlert('error', res.error || 'Posting failed.');
            }
        });
    }

    // ==========================================
    // TAB 2: SOA GENERATION LOGIC
    // ==========================================
    let searchTimer = null;
    const searchInput = document.getElementById('memberSearch');
    const searchDropdown = document.getElementById('searchDropdown');

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        const periodId = document.getElementById('soaPeriod').value;

        if (!periodId) {
            alert("Please select a billing period first.");
            this.value = '';
            return;
        }

        if (q.length < 2) { searchDropdown.classList.remove('open'); return; }

        searchTimer = setTimeout(() => {
            fetch(`../process/searchWaterBillReceivables.php?q=${encodeURIComponent(q)}`) 
                .then(r => r.json())
                .then(data => {
                    if(!data.length) {
                        searchDropdown.innerHTML = `<div style="padding:12px 16px; font-size:.85rem; color:var(--text-muted); text-align:center;">No members found with billed readings for this period.</div>`;
                    } else {
                        searchDropdown.innerHTML = data.map(m => `
                            <div class="search-result" onclick="generateSOA(${m.pkey}, '${e(m.full_name)}')">
                                <div class="search-name">${e(m.full_name)}</div>
                                <div class="search-meta">Account ID: ${m.pkey}</div>
                            </div>`).join('');
                    }
                    searchDropdown.classList.add('open');
                });
        }, 300);
    });

    document.addEventListener('click', e => {
        if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) searchDropdown.classList.remove('open');
    });

    function generateSOA(memberId, memberName) {
        searchDropdown.classList.remove('open');
        searchInput.value = '';
        
        const periodSel = document.getElementById('soaPeriod');
        const periodId = periodSel.value;
        const periodEndDate = periodSel.options[periodSel.selectedIndex].dataset.end;

        document.getElementById('soaName').textContent = memberName;
        document.getElementById('soaAcct').textContent = `Account: ${memberId}`;
        document.getElementById('soaContainer').style.display = 'block';
        document.getElementById('soaItems').innerHTML = `<div style="text-align:center; padding:20px;">Generating statement...</div>`;

        fetch(`../process/getSoaData.php?member_id=${memberId}&period_id=${periodId}&end_date=${periodEndDate}`)
           .then(r => r.json())
            .then(data => {
                // --- NEW: CATCH BACKEND ERRORS ---
                if (data.error) {
                    showAlert('error', data.error);
                    document.getElementById('soaItems').innerHTML = '';
                    document.getElementById('soaTotalAmount').textContent = '₱ 0.00';
                    return;
                }

                let html = '';
                let grandTotal = 0;

                // Render grouped categories (Water, Installment, Recurring, etc.)
                if(data.charges) {
                    for (const [category, items] of Object.entries(data.charges)) {
                        if (items.length > 0) {
                            html += `<div class="soa-row category">${category}</div>`;
                            items.forEach(item => {
                                html += `
                                <div class="soa-row">
                                    <span>${e(item.description)}</span>
                                    <span class="amount">${fmtPeso(item.amount)}</span>
                                </div>`;
                                grandTotal += parseFloat(item.amount);
                            });
                        }
                    }
                }

                if (grandTotal === 0) {
                    html = `<div style="text-align:center; padding:20px; color:var(--success); font-weight:700;">No outstanding balance found.</div>`;
                }

                document.getElementById('soaItems').innerHTML = html;
                document.getElementById('soaTotalAmount').textContent = fmtPeso(grandTotal);
            })
            .catch(() => showAlert('error', 'Failed to generate SOA.'));
    }

    function printSOA() {
        const printContent = document.getElementById('printableSOA').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = `
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto;">
                <div style="text-align:center; margin-bottom: 30px;">
                    <h2>COWASCO WATERS</h2>
                    <p style="color:#666;">Official Statement of Account</p>
                </div>
                ${printContent}
            </div>
        `;
        window.print();
        document.body.innerHTML = originalContent;
        location.reload(); // Reload to restore JS events
    }
</script>
</body>
</html>