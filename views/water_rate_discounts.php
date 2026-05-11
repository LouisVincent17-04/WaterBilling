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
    <title>Assign Water Rate Discounts — COWASCO Waters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* ─── RESET & TOKENS ─── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --white: #ffffff; --off-white: #f8f9fb; --border: #e8eaed; --border-focus: #1a1a2e;
            --text-primary: #0f0f1a; --text-secondary: #6b7280; --text-muted: #9ca3af;
            --accent: #1a1a2e; --accent-hover: #33334d;
            --success: #059669; --success-bg: #ecfdf5; --error: #dc2626; --error-bg: #fef2f2;
            --info: #2563eb; --info-bg: #eff6ff; --radius-sm: 8px; --radius: 14px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06); --shadow-lg: 0 10px 40px rgba(0,0,0,.12);
        }
        html { height: 100%; font-size: 16px; }
        body { font-family: 'Sora', sans-serif; background: var(--off-white); color: var(--text-primary); min-height: 100vh; }
        
        /* ─── LAYOUT ─── */
        .page { padding: 32px 36px; max-width: 1200px; margin: 0 auto; animation: fadeIn .4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
        .page-title { font-size: 1.5rem; font-weight: 700; letter-spacing: -.03em; }
        .page-subtitle { font-size: .875rem; color: var(--text-secondary); margin-top: 4px; }
        
        /* ─── BUTTONS ─── */
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; font-family: 'Sora', sans-serif; font-size: .8125rem; font-weight: 600; border-radius: var(--radius-sm); cursor: pointer; transition: all .2s; border: none; }
        .btn svg { width: 16px; height: 16px; }
        .btn-primary { background: var(--accent); color: var(--white); }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-1px); }
        .btn-ghost { background: var(--white); border: 1.5px solid var(--border); color: var(--text-primary); }
        .btn-ghost:hover { border-color: var(--border-focus); }
        .btn-danger-ghost { background: transparent; color: var(--error); border: 1px solid transparent; padding: 6px 10px; border-radius: 6px; cursor: pointer; transition: .2s; }
        .btn-danger-ghost:hover { background: var(--error-bg); border-color: #fecaca; }

        /* ─── TABLE ─── */
        .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: hidden; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: .875rem; }
        th { background: #f9fafb; padding: 14px 20px; font-weight: 600; color: var(--text-secondary); font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; border-bottom: 2px solid var(--border); white-space: nowrap; }
        td { padding: 14px 20px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--off-white); }
        
        .badge { display: inline-flex; padding: 4px 10px; border-radius: 20px; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .badge-disc { background: #fef3c7; color: #b45309; }
        .mono { font-family: 'JetBrains Mono', monospace; font-weight: 600; color: var(--info); }
        
        .member-name { font-weight: 600; font-size: .9rem; color: var(--text-primary); }
        .member-meta { font-size: .75rem; color: var(--text-secondary); margin-top: 2px; }

        /* ─── MODAL ─── */
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,15,26,.4); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 100; opacity: 0; transition: opacity .2s; }
        .modal-overlay.show { display: flex; opacity: 1; }
        .modal { background: var(--white); width: 100%; max-width: 500px; border-radius: var(--radius); box-shadow: var(--shadow-lg); transform: translateY(20px); transition: transform .2s; overflow: visible; }
        .modal-overlay.show .modal { transform: translateY(0); }
        .modal-head { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-title { font-size: 1.125rem; font-weight: 700; }
        .close-btn { background: none; border: none; cursor: pointer; color: var(--text-secondary); transition: color .2s; }
        .close-btn:hover { color: var(--error); }
        .modal-body { padding: 24px; overflow: visible; }
        .modal-foot { padding: 16px 24px; border-top: 1px solid var(--border); background: var(--off-white); display: flex; justify-content: flex-end; gap: 12px; border-radius: 0 0 var(--radius) var(--radius); }

        /* ─── FORM & SEARCH ─── */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: .75rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: 'Sora', sans-serif; font-size: .875rem; transition: border-color .2s; outline: none; }
        .form-control:focus { border-color: var(--border-focus); }
        
        .search-wrap { position: relative; }
        .search-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: var(--white); border: 1.5px solid var(--border-focus); border-top: none; border-radius: 0 0 var(--radius-sm) var(--radius-sm); box-shadow: var(--shadow); z-index: 50; max-height: 250px; overflow-y: auto; display: none; }
        .search-dropdown.open { display: block; }
        .search-result { display: flex; flex-direction: column; padding: 10px 14px; cursor: pointer; border-bottom: 1px solid var(--border); }
        .search-result:last-child { border-bottom: none; }
        .search-result:hover { background: var(--off-white); }
        .search-res-name { font-weight: 600; font-size: .85rem; }
        .search-res-meta { font-size: .7rem; color: var(--text-muted); }
        
        /* Selected Member Card */
        .selected-member-card { display: none; background: var(--info-bg); border: 1px solid var(--info-border); border-radius: var(--radius-sm); padding: 12px 16px; margin-top: 8px; position: relative; }
        .smc-name { font-weight: 700; color: var(--info); font-size: .9rem; }
        .smc-meta { font-size: .75rem; color: var(--text-secondary); margin-top: 2px; }
        .smc-clear { position: absolute; top: 12px; right: 12px; background: none; border: none; color: var(--info); cursor: pointer; }
        .smc-clear:hover { color: var(--error); }

        .alert-box { display: none; padding: 12px; border-radius: var(--radius-sm); font-size: .8125rem; margin-bottom: 16px; }
        .alert-success { background: var(--success-bg); color: var(--success); border: 1px solid #a7f3d0; }
        .alert-error { background: var(--error-bg); color: var(--error); border: 1px solid #fecaca; }
    </style>
</head>
<body>

<?php require_once '../common/navbar.php'; ?>

<div class="main-content">
    <main class="page">
        <div class="page-header">
            <div>
                <a href="discount_management.php" style="display:inline-flex; align-items:center; gap:6px; color:var(--text-secondary); text-decoration:none; font-size:.8rem; font-weight:600; margin-bottom:8px; transition:color .2s;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="14" height="14"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Back to Settings
                </a>
                <h1 class="page-title">Assign Water Rate Discounts</h1>
                <p class="page-subtitle">Enroll specific members into configured discount programs (e.g., Senior Citizen, Employee).</p>
            </div>
            <button class="btn btn-primary" onclick="openModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Assign Discount
            </button>
        </div>

        <div id="mainAlert" class="alert-box"></div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Address / Zone</th>
                            <th>Assigned Discount</th>
                            <th>Discount Details</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="assignedTableBody">
                        <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">Loading assigned members...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div class="modal-overlay" id="assignModal">
    <div class="modal">
        <div class="modal-head">
            <div class="modal-title">Assign Discount to Member</div>
            <button class="close-btn" onclick="closeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="20" height="20"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="modal-body">
            <div id="modalAlert" class="alert-box"></div>
            
            <input type="hidden" id="selectedMemberId">

            <div class="form-group">
                <label class="form-label">Search Member <span style="color:var(--error)">*</span></label>
                
                <div class="search-wrap" id="searchWrapper">
                    <input type="text" id="memberSearch" class="form-control" placeholder="Type name or ID..." autocomplete="off">
                    <div class="search-dropdown" id="searchDropdown"></div>
                </div>

                <div class="selected-member-card" id="selectedMemberCard">
                    <div class="smc-name" id="smcName">Louis</div>
                    <div class="smc-meta" id="smcMeta">ID: 1001</div>
                    <button class="smc-clear" onclick="clearMemberSelection()" title="Clear Selection">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="16" height="16"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Select Discount Program <span style="color:var(--error)">*</span></label>
                <select id="discountSelect" class="form-control">
                    <option value="">— Select a Discount —</option>
                    </select>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveAssignment()" id="btnSave">Confirm Assignment</button>
        </div>
    </div>
</div>

<script>
    const ENDPOINT = '../process/manageMemberWRDiscount.php';
    let searchTimer = null;

    // --- Initialization ---
    document.addEventListener('DOMContentLoaded', () => {
        loadAssignedMembers();
        loadDiscountOptions();
    });

    // --- Load Data ---
    function loadAssignedMembers() {
        fetch(ENDPOINT + '?action=list_assigned')
            .then(res => res.json())
            .then(data => renderTable(data))
            .catch(() => showMainAlert('error', 'Failed to load enrolled members.'));
    }

    function loadDiscountOptions() {
        fetch(ENDPOINT + '?action=list_discounts')
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById('discountSelect');
                data.forEach(d => {
                    select.innerHTML += `<option value="${d.wmdiscount_id}">${e(d.wmd_name)}</option>`;
                });
            });
    }

    // --- Render Table ---
    function renderTable(members) {
        const tbody = document.getElementById('assignedTableBody');
        if (!members || members.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">No members currently have a discount assigned.</td></tr>`;
            return;
        }

        let html = '';
        members.forEach(m => {
            // Safety fallbacks to prevent "undefined"
            const fName = m.firstname || '';
            const lName = m.lastname || '';
            const fullName = `${fName} ${lName}`.trim() || 'Unknown Name';
            
            const bldg = m.housebldg || '';
            const st = m.street || '';
            const address = `${bldg} ${st}`.trim() || 'No address set';
            
            const zone = m.zone ? `(Zone ${m.zone})` : '';
            const accId = m.pkey || 'N/A';
            
            // Format details safely
            let details = [];
            if (m.free_water_m3 > 0) details.push(`${m.free_water_m3}m³ Free`);
            if (m.active_discount === 'percent' && m.percent_discount) details.push(`${parseFloat(m.percent_discount)}% Off`);
            else if (m.active_discount === 'fixed' && m.fixed_discount) details.push(`₱${parseFloat(m.fixed_discount)} Off`);
            const detailsStr = details.length ? details.join(' + ') : 'No extra deduction';

            const discountName = m.wmd_name || 'Unknown Discount';

            html += `
                <tr>
                    <td>
                        <div class="member-name">${e(fullName)}</div>
                        <div class="member-meta">Account ID: ${e(accId)}</div>
                    </td>
                    <td>
                        <div>${e(address)}</div>
                        <div class="member-meta">${e(zone)}</div>
                    </td>
                    <td><span class="badge badge-disc">${e(discountName)}</span></td>
                    <td class="mono">${detailsStr}</td>
                    <td style="text-align:right;">
                        <button class="btn-danger-ghost" onclick="removeDiscount(${accId}, '${e(fullName)}')" title="Remove Discount">
                            Remove
                        </button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    // --- Search Logic ---
    const searchInput = document.getElementById('memberSearch');
    const searchDropdown = document.getElementById('searchDropdown');

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        
        if (q.length < 2) { 
            searchDropdown.classList.remove('open'); 
            return; 
        }

        searchTimer = setTimeout(() => {
            fetch(ENDPOINT + '?action=search_members&q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    if (!data.length) {
                        searchDropdown.innerHTML = `<div style="padding:10px; font-size:.8rem; color:var(--text-muted); text-align:center;">No members found.</div>`;
                    } else {
                        searchDropdown.innerHTML = data.map(m => {
                            const fName = m.firstname || '';
                            const lName = m.lastname || '';
                            const name = `${fName} ${lName}`.trim() || 'Unknown Name';
                            return `
                            <div class="search-result" onclick="selectMember(${m.pkey}, '${e(name)}')">
                                <div class="search-res-name">${e(name)}</div>
                                <div class="search-res-meta">ID: ${m.pkey} | Zone: ${m.zone || 'N/A'}</div>
                            </div>`;
                        }).join('');
                    }
                    searchDropdown.classList.add('open');
                });
        }, 300);
    });

    document.addEventListener('click', e => {
        if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
            searchDropdown.classList.remove('open');
        }
    });

    function selectMember(id, name) {
        document.getElementById('selectedMemberId').value = id;
        document.getElementById('smcName').textContent = name;
        document.getElementById('smcMeta').textContent = `Account ID: ${id}`;
        
        document.getElementById('searchWrapper').style.display = 'none';
        document.getElementById('selectedMemberCard').style.display = 'block';
        searchDropdown.classList.remove('open');
    }

    function clearMemberSelection() {
        document.getElementById('selectedMemberId').value = '';
        searchInput.value = '';
        document.getElementById('searchWrapper').style.display = 'block';
        document.getElementById('selectedMemberCard').style.display = 'none';
        searchInput.focus();
    }

    // --- Modal Controls ---
    function openModal() {
        clearMemberSelection();
        document.getElementById('discountSelect').value = '';
        document.getElementById('modalAlert').style.display = 'none';
        document.getElementById('assignModal').classList.add('show');
    }

    function closeModal() {
        document.getElementById('assignModal').classList.remove('show');
    }

    // --- Save Assignment ---
    function saveAssignment() {
        const memberId = document.getElementById('selectedMemberId').value;
        const discountId = document.getElementById('discountSelect').value;

        if (!memberId) { showModalAlert('error', 'Please search and select a member.'); return; }
        if (!discountId) { showModalAlert('error', 'Please select a discount program.'); return; }

        const btn = document.getElementById('btnSave');
        btn.textContent = 'Saving...'; btn.disabled = true;

        fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'assign',
                member_id: memberId,
                wmdiscount_id: discountId
            })
        })
        .then(res => res.json())
        .then(res => {
            btn.textContent = 'Confirm Assignment'; btn.disabled = false;
            if (res.success) {
                closeModal();
                showMainAlert('success', 'Discount assigned to member successfully.');
                loadAssignedMembers();
            } else {
                showModalAlert('error', res.error || 'Failed to assign discount.');
            }
        });
    }

    // --- Remove Assignment ---
    function removeDiscount(memberId, memberName) {
        if (!confirm(`Are you sure you want to remove the discount from ${memberName}?`)) return;

        fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'remove',
                member_id: memberId
            })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showMainAlert('success', `Discount removed from ${memberName}.`);
                loadAssignedMembers();
            } else {
                showMainAlert('error', res.error || 'Failed to remove discount.');
            }
        });
    }

    // --- Helpers ---
    function e(str) { return String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match])); }

    function showMainAlert(type, msg) {
        const box = document.getElementById('mainAlert');
        box.className = `alert-box alert-${type}`;
        box.textContent = msg;
        box.style.display = 'block';
        setTimeout(() => box.style.display = 'none', 5000);
    }

    function showModalAlert(type, msg) {
        const box = document.getElementById('modalAlert');
        box.className = `alert-box alert-${type}`;
        box.textContent = msg;
        box.style.display = 'block';
    }
</script>
</body>
</html>