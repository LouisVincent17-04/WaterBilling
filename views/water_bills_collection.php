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
    <title>Water Bill Collections — COWASCO Waters</title>
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
            --info: #2563eb; --info-bg: #eff6ff; 
            --radius-sm: 8px; --radius: 14px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06); --shadow-lg: 0 10px 40px rgba(0,0,0,.12);
        }
        html { height: 100%; font-size: 16px; }
        body { font-family: 'Sora', sans-serif; background: var(--off-white); color: var(--text-primary); min-height: 100vh; }
        
        /* ─── LAYOUT ─── */
        .page { padding: 32px 36px; max-width: 1400px; margin: 0 auto; animation: fadeIn .4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
        .page-title { font-size: 1.5rem; font-weight: 700; letter-spacing: -.03em; }
        .page-subtitle { font-size: .875rem; color: var(--text-secondary); margin-top: 4px; }
        
        /* ─── CASHIER GRID ─── */
        .cashier-grid { display: grid; grid-template-columns: 1fr 420px; gap: 24px; align-items: start; }
        @media (max-width: 1024px) { .cashier-grid { grid-template-columns: 1fr; } }

        /* ─── CARDS ─── */
        .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: visible; margin-bottom: 20px; }
        .card-head { padding: 18px 22px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fafafa; border-radius: var(--radius) var(--radius) 0 0; }
        .card-title { font-size: .95rem; font-weight: 700; display: flex; align-items: center; gap: 8px; color: var(--info); }
        .card-body { padding: 22px; }

        /* ─── SEARCH & DROPDOWN ─── */
        .search-wrap { position: relative; margin-bottom: 24px; z-index: 50; }
        .form-control { width: 100%; padding: 12px 16px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: 'Sora', sans-serif; font-size: .9rem; transition: border-color .2s; outline: none; }
        .form-control:focus { border-color: var(--info); box-shadow: 0 0 0 3px var(--info-bg); }
        .search-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: var(--white); border: 1.5px solid var(--border); border-top: none; border-radius: 0 0 var(--radius-sm) var(--radius-sm); box-shadow: var(--shadow-lg); max-height: 300px; overflow-y: auto; display: none; }
        .search-dropdown.open { display: block; animation: fadeIn .15s ease; }
        .search-result { display: flex; flex-direction: column; padding: 12px 16px; cursor: pointer; border-bottom: 1px solid var(--border); transition: background .1s; }
        .search-result:last-child { border-bottom: none; }
        .search-result:hover { background: var(--info-bg); }
        .search-res-name { font-weight: 700; font-size: .9rem; color: var(--text-primary); }
        .search-res-meta { font-size: .75rem; color: var(--text-secondary); margin-top: 2px; }

        /* ─── BILLING ITEMS ─── */
        .bill-section-title { font-size: .8rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: .05em; margin: 0 0 12px; border-bottom: 2px solid var(--border); padding-bottom: 6px; display: flex; justify-content: space-between; }
        
        .bill-card { display: flex; align-items: center; justify-content: space-between; padding: 16px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 12px; cursor: pointer; transition: all .2s; }
        .bill-card:hover { border-color: var(--info); background: var(--info-bg); }
        .bill-card.selected { border-color: var(--info); background: var(--info-bg); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1); }
        
        .bill-left { display: flex; align-items: center; gap: 14px; }
        .bill-meta-title { font-weight: 700; font-size: 1rem; color: var(--text-primary); }
        .bill-meta-sub { font-size: .75rem; color: var(--text-secondary); margin-top: 4px; display: flex; gap: 8px; align-items: center; }
        .bill-amount { font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 1.15rem; color: var(--text-primary); }
        
        .badge { font-size: .65rem; padding: 3px 8px; border-radius: 20px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .badge-water { background: #e0f2fe; color: #1e40af; border: 1px solid #bae6fd; }
        
        .chk-wrap { display: flex; align-items: center; justify-content: center; }
        .chk { width: 22px; height: 22px; accent-color: var(--info); cursor: pointer; }

        /* ─── CART / RECEIPT ─── */
        .cart-row { display: flex; justify-content: space-between; font-size: .85rem; margin-bottom: 12px; color: var(--text-secondary); }
        .cart-row strong { color: var(--text-primary); font-family: 'JetBrains Mono', monospace; }
        .cart-total { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 16px; border-top: 2px dashed var(--border); font-size: 1rem; font-weight: 700; }
        .cart-total-amount { font-size: 1.75rem; color: var(--info); font-family: 'JetBrains Mono', monospace; }
        
        .payment-input-wrap { position: relative; margin-top: 24px; }
        .payment-input-wrap span { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-weight: 700; color: var(--text-muted); font-size: 1.25rem;}
        .payment-input { width: 100%; padding: 16px 16px 16px 36px; font-size: 1.5rem; font-family: 'JetBrains Mono', monospace; font-weight: 700; border: 2px solid var(--border); border-radius: var(--radius-sm); outline: none; transition: .2s; color: var(--text-primary); }
        .payment-input:focus { border-color: var(--success); box-shadow: 0 0 0 4px var(--success-bg); }

        .change-row { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding: 16px; background: var(--off-white); border-radius: var(--radius-sm); font-weight: 700; border: 1px solid var(--border); }
        .change-amount { font-family: 'JetBrains Mono', monospace; font-size: 1.5rem; color: var(--success); }

        /* ─── BUTTONS ─── */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 16px 24px; font-family: 'Sora', sans-serif; font-size: 1rem; font-weight: 700; border-radius: var(--radius-sm); cursor: pointer; transition: all .2s; border: none; width: 100%; }
        .btn-success { background: var(--success); color: var(--white); margin-top: 20px; }
        .btn-success:hover { background: #047857; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(5,150,105,.2); }
        .btn-success:disabled { background: var(--border); color: var(--text-muted); cursor: not-allowed; transform: none; box-shadow: none; }
        .spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid var(--white); border-top-color: transparent; border-radius: 50%; animation: spin .6s linear infinite; display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<?php require_once '../common/navbar.php'; ?>

<div class="main-content">
    <main class="page">
        
        <div class="page-header">
            <div>
                <a href="collection_management.php" style="display:inline-flex; align-items:center; gap:6px; color:var(--text-secondary); text-decoration:none; font-size:.8rem; font-weight:600; margin-bottom:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="14" height="14"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Back to Collection Hub
                </a>
                <h1 class="page-title">Water Bill Cashiering</h1>
                <p class="page-subtitle">Process payments specifically for posted monthly water consumption.</p>
            </div>
        </div>

        <div class="cashier-grid">
            
            <div>
                <div class="search-wrap" id="searchWrap">
                    <input type="text" id="memberSearch" class="form-control" placeholder="Search Member Name or Account ID..." autocomplete="off">
                    <div class="search-dropdown" id="searchDropdown"></div>
                </div>

                <div class="card" id="billsContainer" style="display:none; animation: scaleIn .25s ease;">
                    <div class="card-head">
                        <div class="card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="18" height="18"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span id="dispMemberName">—</span>
                        </div>
                        <span style="font-size:.85rem; color:var(--text-secondary); font-weight:600;" id="dispMemberAcct">—</span>
                    </div>
                    <div class="card-body">
                        
                        <div class="bill-section-title">
                            Billed Water Consumption
                            <label style="cursor:pointer; display:flex; align-items:center; gap:6px; color:var(--text-primary); text-transform:none;">
                                <input type="checkbox" id="chkAllWater" onchange="toggleAll(this.checked)"> Select All
                            </label>
                        </div>
                        <div id="waterBillsList">
                            </div>

                    </div>
                </div>
            </div>

            <div>
                <div class="card" style="position: sticky; top: 24px;">
                    <div class="card-head">
                        <div class="card-title" style="color:var(--text-primary);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="18" height="18"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            Payment Receipt
                        </div>
                    </div>
                    <div class="card-body">
                        
                        <div id="cartSummary">
                            <div style="text-align:center; color:var(--text-muted); font-size:.85rem; padding: 20px;">
                                No bills selected.
                            </div>
                        </div>

                        <div class="cart-total">
                            <span>Total Due</span>
                            <span class="cart-total-amount" id="dispTotalDue">₱ 0.00</span>
                        </div>

                        <div class="payment-input-wrap">
                            <span>₱</span>
                            <input type="number" id="tenderedAmount" class="payment-input" placeholder="0.00" step="0.01" disabled>
                        </div>

                        <div class="change-row">
                            <span>Change</span>
                            <span class="change-amount" id="dispChange">₱ 0.00</span>
                        </div>

                        <button class="btn btn-success" id="btnProcess" disabled onclick="processPayment()">
                            <span class="spinner" id="btnSpinner"></span>
                            <span id="btnText">Confirm Payment</span>
                        </button>

                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
    let searchTimer = null;
    let selectedItems = new Map(); // Maps reading_id -> object
    let totalDue = 0;

    // --- Formatters ---
    const fmtPeso = n => '₱ ' + Number(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const e = str => String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));

    // --- Search Input Logic ---
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
            fetch(`../process/searchWaterBillReceivables.php?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.length) {
                        searchDropdown.innerHTML = `<div style="padding:16px; font-size:.85rem; color:var(--text-muted); text-align:center;">No members found with unpaid water bills.</div>`;
                    } else {
                        searchDropdown.innerHTML = data.map(m => `
                            <div class="search-result" onclick='selectMember(${JSON.stringify(m).replace(/'/g, "&#39;")})'>
                                <div class="search-res-name">${e(m.full_name)}</div>
                                <div class="search-res-meta">Acct ID: ${e(m.pkey)} | Unpaid Bills: ${m.unpaid_count}</div>
                            </div>`).join('');
                    }
                    searchDropdown.classList.add('open');
                });
        }, 300);
    });

    document.addEventListener('click', ev => {
        if (!searchInput.contains(ev.target) && !searchDropdown.contains(ev.target)) {
            searchDropdown.classList.remove('open');
        }
    });

    // --- Select Member & Load Bills ---
    function selectMember(memberData) {
        searchDropdown.classList.remove('open');
        searchInput.value = '';
        
        document.getElementById('dispMemberName').textContent = memberData.full_name;
        document.getElementById('dispMemberAcct').textContent = `Account ID: ${memberData.pkey}`;
        
        selectedItems.clear();
        document.getElementById('chkAllWater').checked = false;
        document.getElementById('waterBillsList').innerHTML = `<div style="text-align:center; padding: 20px;"><span class="spinner" style="border-top-color:var(--info); display:inline-block;"></span></div>`;
        document.getElementById('billsContainer').style.display = 'block';

        // Fetch their specific unpaid water bills
        fetch(`../process/getWaterBillReceivables.php?member_id=${memberData.pkey}`)
            .then(r => r.json())
            .then(bills => {
                if (bills.length === 0) {
                    document.getElementById('waterBillsList').innerHTML = '<div style="color:var(--success); font-size:.85rem; font-weight:600; padding:16px; text-align:center; background:var(--success-bg); border-radius:8px;">All water bills are fully paid.</div>';
                } else {
                    const html = bills.map(b => {
                        // Assuming your API returns amount_due for the bill
                        const amt = parseFloat(b.amount_due);
                        return `
                        <label class="bill-card" id="card_${b.reading_id}">
                            <div class="bill-left">
                                <div class="chk-wrap">
                                    <input type="checkbox" class="chk chk-water" value="${b.reading_id}" 
                                           data-amt="${amt}" data-period="${e(b.period_code)}" 
                                           data-cons="${b.consumption}" onchange="toggleItem(this)">
                                </div>
                                <div>
                                    <div class="bill-meta-title">${e(b.period_code)} <span class="badge badge-water">Water</span></div>
                                    <div class="bill-meta-sub">
                                        <span>Cons: ${b.consumption} m³</span>
                                        <span>•</span>
                                        <span>Read Date: ${b.reading_date}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="bill-amount">${fmtPeso(amt)}</div>
                        </label>`;
                    }).join('');
                    document.getElementById('waterBillsList').innerHTML = html;
                }
                updateCart();
            });
    }

    // --- Checkbox Logic ---
    function toggleItem(checkbox) {
        const card = document.getElementById(`card_${checkbox.value}`);
        if (checkbox.checked) {
            card.classList.add('selected');
            selectedItems.set(checkbox.value, { 
                id: checkbox.value, 
                desc: `${checkbox.dataset.period} Water Bill`, 
                amt: parseFloat(checkbox.dataset.amt) 
            });
        } else {
            card.classList.remove('selected');
            selectedItems.delete(checkbox.value);
            document.getElementById('chkAllWater').checked = false;
        }
        updateCart();
    }

    function toggleAll(isChecked) {
        document.querySelectorAll('.chk-water').forEach(chk => {
            if (chk.checked !== isChecked) {
                chk.checked = isChecked;
                toggleItem(chk);
            }
        });
    }

    // --- Cart & Math Logic ---
    function updateCart() {
        totalDue = 0;
        let cartHtml = '';
        
        if (selectedItems.size === 0) {
            cartHtml = `<div style="text-align:center; color:var(--text-muted); font-size:.85rem; padding: 20px;">No bills selected.</div>`;
            document.getElementById('tenderedAmount').disabled = true;
            document.getElementById('tenderedAmount').value = '';
        } else {
            selectedItems.forEach(item => {
                totalDue += item.amt;
                cartHtml += `
                    <div class="cart-row">
                        <span>${item.desc}</span>
                        <strong>${fmtPeso(item.amt)}</strong>
                    </div>`;
            });
            document.getElementById('tenderedAmount').disabled = false;
        }

        document.getElementById('cartSummary').innerHTML = cartHtml;
        document.getElementById('dispTotalDue').textContent = fmtPeso(totalDue);
        calculateChange();
    }

    document.getElementById('tenderedAmount').addEventListener('input', calculateChange);

    function calculateChange() {
        const tendered = parseFloat(document.getElementById('tenderedAmount').value) || 0;
        const btnProcess = document.getElementById('btnProcess');
        
        if (totalDue > 0 && tendered >= totalDue) {
            const change = tendered - totalDue;
            document.getElementById('dispChange').textContent = fmtPeso(change);
            document.getElementById('dispChange').style.color = 'var(--success)';
            btnProcess.disabled = false;
        } else {
            document.getElementById('dispChange').textContent = `₱ 0.00`;
            document.getElementById('dispChange').style.color = 'var(--text-muted)';
            btnProcess.disabled = true;
        }
    }

    // --- Process Payment Mock ---
    function processPayment() {
        const btnProcess = document.getElementById('btnProcess');
        const btnText = document.getElementById('btnText');
        const spinner = document.getElementById('btnSpinner');

        btnProcess.disabled = true;
        btnText.textContent = "Processing...";
        spinner.style.display = "inline-block";

        // Collect reading IDs to mark as paid
        const readingsToPay = Array.from(selectedItems.keys());
        const tendered = parseFloat(document.getElementById('tenderedAmount').value);

        // Simulate API Call
        setTimeout(() => {
            alert(`Payment of ${fmtPeso(tendered)} successful for ${readingsToPay.length} bill(s)!`);
            
            // Reset UI
            selectedItems.clear();
            document.getElementById('billsContainer').style.display = 'none';
            document.getElementById('memberSearch').value = '';
            updateCart();
            
            btnText.textContent = "Confirm Payment";
            spinner.style.display = "none";
        }, 1200);
    }
</script>
</body>
</html>