<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../database/config.php';

$pdo = getDB();

$_SESSION['full_name'] = $_SESSION['full_name'] ?? 'System Admin';
$_SESSION['username']  = $_SESSION['username'] ?? 'admin';
$_SESSION['role']      = $_SESSION['role'] ?? 'Administrator';

if (!function_exists('e')) {
    function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}

try {
    $stmt = $pdo->query("
        SELECT 
            bp.*, 
            u_open.username  AS opener_name, 
            u_close.username AS closer_name 
        FROM bill_periods bp
        LEFT JOIN users u_open  ON bp.opened_by = u_open.id
        LEFT JOIN users u_close ON bp.closed_by = u_close.id
        ORDER BY bp.start_date DESC
    ");
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $periods = [];
    $_SESSION['error_msg'] = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Period Management — COWASCO Waters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">

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
            --success: #059669;
            --success-bg: #ecfdf5;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
            --radius-sm: 8px;
            --radius: 14px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
        }
        body {
            font-family: 'Sora', sans-serif;
            background: var(--off-white);
            color: var(--text-primary);
            min-height: 100vh;
        }

        .page { padding: 32px 32px 60px; animation: fadeIn .4s ease both; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        .header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
        .title { font-size: 1.5rem; font-weight: 700; }
        .subtitle { font-size: .875rem; color: var(--text-secondary); margin-top: 4px; }

        .card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 24px;
            margin-bottom: 24px; box-shadow: var(--shadow-sm);
        }
        .card-title {
            font-size: 1.1rem; font-weight: 600; margin-bottom: 16px;
            padding-bottom: 12px; border-bottom: 1px solid var(--border);
        }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        label { font-size: .8rem; font-weight: 600; color: var(--text-secondary); }

        input, select {
            padding: 10px 12px; border: 1.5px solid var(--border);
            border-radius: var(--radius-sm); font-family: inherit;
            font-size: .875rem; outline: none; width: 100%;
            transition: border-color .2s, box-shadow .2s;
        }
        input:focus, select:focus { border-color: var(--border-focus); box-shadow: 0 0 0 3px rgba(26,26,46,.07); }
        input[readonly] { background: var(--off-white); color: var(--text-secondary); cursor: not-allowed; }

        /* Date input wrapper with calendar icon */
        .date-wrap { position: relative; }
        .date-wrap input { padding-right: 38px; cursor: pointer; }
        .date-wrap .cal-icon {
            position: absolute; right: 11px; top: 50%;
            transform: translateY(-50%);
            pointer-events: none; color: var(--text-muted);
            display: flex; align-items: center;
        }

        .btn-group { display: flex; gap: 12px; margin-top: 8px; }
        .btn {
            padding: 10px 20px; border: none; border-radius: var(--radius-sm);
            font-family: inherit; font-size: .875rem; font-weight: 600;
            cursor: pointer; text-decoration: none; display: inline-block; transition: all .2s;
        }
        .btn-primary { background: var(--accent); color: var(--white); }
        .btn-primary:hover { background: #0f0f1a; transform: translateY(-1px); }
        .btn-secondary { background: var(--white); color: var(--text-primary); border: 1.5px solid var(--border); }
        .btn-secondary:hover { border-color: var(--border-focus); }

        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .85rem; white-space: nowrap; }
        th { text-align: left; padding: 12px 16px; color: var(--text-secondary); border-bottom: 1.5px solid var(--border); font-weight: 600; }
        td { padding: 14px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; }

        .mono { font-family: 'JetBrains Mono', monospace; font-size: .8rem; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: .7rem; font-weight: 700; }
        .badge-open  { background: var(--success-bg); color: var(--success); }
        .badge-close { background: var(--danger-bg);  color: var(--danger);  }

        .action-links { display: flex; gap: 12px; }
        .action-links a { color: var(--text-secondary); text-decoration: none; font-weight: 600; font-size: .8rem; transition: color .2s; }
        .action-links a:hover { color: var(--accent); }
        .action-links a.close-action:hover  { color: #d97706; }
        .action-links a.delete-action:hover { color: var(--danger); }

        .next-period-hint {
            margin-top: 10px; padding: 10px 14px;
            background: #f0f9ff; border: 1px solid #bae6fd;
            border-radius: var(--radius-sm); font-size: .8rem;
            color: #0369a1; display: none;
        }
        .next-period-hint strong { font-weight: 700; }

        /* ── Flatpickr theme overrides ───────────────────────── */
        .flatpickr-calendar {
            font-family: 'Sora', sans-serif !important;
            border: 1.5px solid var(--border-focus) !important;
            border-radius: var(--radius) !important;
            box-shadow: 0 8px 32px rgba(0,0,0,.12) !important;
            overflow: hidden;
        }
        .flatpickr-months { background: var(--accent) !important; padding: 4px 0; }
        .flatpickr-month,
        .flatpickr-prev-month,
        .flatpickr-next-month { color: #fff !important; fill: #fff !important; }
        .flatpickr-prev-month:hover svg,
        .flatpickr-next-month:hover svg { fill: #fff !important; opacity: .7; }
        .flatpickr-current-month input.cur-year,
        .flatpickr-current-month .flatpickr-monthDropdown-months {
            color: #fff !important;
            font-family: 'Sora', sans-serif !important;
            font-weight: 700;
            background: transparent !important;
        }
        .flatpickr-current-month .flatpickr-monthDropdown-months option { background: var(--accent) !important; }
        .flatpickr-weekdays { background: var(--off-white) !important; }
        .flatpickr-weekday {
            font-family: 'Sora', sans-serif !important;
            font-weight: 700; font-size: .7rem;
            color: var(--text-muted) !important;
            background: var(--off-white) !important;
        }
        .flatpickr-day {
            font-family: 'Sora', sans-serif !important;
            font-size: .8rem; border-radius: 8px !important;
        }
        .flatpickr-day.selected,
        .flatpickr-day.selected:hover {
            background: var(--accent) !important;
            border-color: var(--accent) !important;
            color: #fff !important;
        }
        .flatpickr-day:hover { background: var(--off-white) !important; }
        .flatpickr-day.today {
            border-color: var(--accent) !important;
            color: var(--accent) !important;
            font-weight: 700;
        }
        .flatpickr-day.today.selected { color: #fff !important; }
        .numInputWrapper span { border-color: var(--border) !important; }
        .numInputWrapper span:hover { background: var(--off-white) !important; }

        @media (max-width: 768px) { .page { padding: 20px 16px; } }
    </style>
</head>
<body>

<?php require_once '../common/navbar.php'; ?>

<div class="main-content">
<main class="page">

    <div class="header">
        <div>
            <h1 class="title">Bill Period Management</h1>
            <p class="subtitle">Create and manage billing cycles</p>
        </div>
        <a href="configurations.php" class="btn btn-secondary" style="display:flex;align-items:center;gap:6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="16" height="16">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>
            Back to Configs
        </a>
    </div>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div style="background:var(--success-bg);color:var(--success);padding:12px 16px;border-radius:var(--radius-sm);border:1px solid #a7f3d0;margin-bottom:20px;font-weight:600;font-size:.875rem;">
            <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_msg'])): ?>
        <div style="background:var(--danger-bg);color:var(--danger);padding:12px 16px;border-radius:var(--radius-sm);border:1px solid #fecaca;margin-bottom:20px;font-weight:600;font-size:.875rem;">
            <?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2 class="card-title">Create New Period</h2>
        <form action="../process/addBillPeriod.php" method="POST">
            <div class="form-grid">

                <div class="form-group">
                    <label>Duration</label>
                    <select name="duration" id="duration">
                        <option value="1">Monthly (1 month)</option>
                        <option value="3">Quarterly (3 months)</option>
                        <option value="6">Semi-Annual (6 months)</option>
                        <option value="12">Annual (12 months)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>From Date</label>
                    <div class="date-wrap">
                        <input type="text" name="date_from" id="date_from"
                               placeholder="MM/DD/YYYY" autocomplete="off" required>
                        <span class="cal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label>To Date (auto-filled by duration)</label>
                    <div class="date-wrap">
                        <input type="text" name="date_to" id="date_to"
                               placeholder="MM/DD/YYYY" autocomplete="off" required>
                        <span class="cal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Period Code (Auto-generated)</label>
                    <input type="text" name="period_code" id="period_code"
                           placeholder="e.g. 010226_020226" readonly required>
                </div>

            </div>

            <div class="next-period-hint" id="next_period_hint">
                📅 A <strong>next period</strong> will also be auto-created:
                <strong id="next_from_display"></strong> → <strong id="next_to_display"></strong>
                &nbsp;(Code: <span id="next_code_display" style="font-family:monospace"></span>)
            </div>

            <div class="btn-group" style="margin-top:16px;">
                <button type="submit" class="btn btn-primary">Save Period</button>
                <button type="button" class="btn btn-secondary" id="clear_btn">Clear Form</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title">Billing Periods Ledger</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Status</th>
                        <th>Opened By</th>
                        <th>Closed By</th>
                        <th>Closed On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($periods)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted);">
                                No billing periods have been created yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($periods as $p): ?>
                        <tr>
                            <td class="mono"><?= e($p['bp_code']) ?></td>
                            <td><?= date('m/d/Y', strtotime($p['start_date'])) ?></td>
                            <td><?= date('m/d/Y', strtotime($p['end_date'])) ?></td>
                            <td>
                                <?php if ($p['status'] === 'open'): ?>
                                    <span class="badge badge-open">OPEN</span>
                                <?php else: ?>
                                    <span class="badge badge-close">CLOSED</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($p['opener_name'] ?? 'System') ?></td>
                            <td <?= empty($p['closer_name']) ? 'style="color:var(--text-muted)"' : '' ?>>
                                <?= e($p['closer_name'] ?? 'N/A') ?>
                            </td>
                            <td <?= empty($p['closed_at']) ? 'style="color:var(--text-muted)"' : '' ?>>
                                <?= !empty($p['closed_at']) ? date('m/d/Y', strtotime($p['closed_at'])) : 'N/A' ?>
                            </td>
                            <td>
                                <div class="action-links">
                                    <?php if ($p['status'] === 'open'): ?>
                                        <a href="../process/editBillPeriod.php?id=<?= $p['period_id'] ?>">Edit</a>
                                        <form action="../process/editBillPeriod.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="period_id"   value="<?= $p['period_id'] ?>">
                                            <input type="hidden" name="period_code" value="<?= e($p['bp_code']) ?>">
                                            <input type="hidden" name="date_from"   value="<?= $p['start_date'] ?>">
                                            <input type="hidden" name="date_to"     value="<?= $p['end_date'] ?>">
                                            <input type="hidden" name="status"      value="closed">
                                            <a href="#" class="close-action"
                                               onclick="if(confirm('Close this period?')) this.closest('form').submit(); return false;">
                                               Close Period
                                            </a>
                                        </form>
                                        <a href="../process/deleteBillPeriod.php?id=<?= $p['period_id'] ?>"
                                           class="delete-action"
                                           onclick="return confirm('Delete this billing period?');">Delete</a>
                                    <?php else: ?>
                                        <a href="#" style="color:var(--text-muted);cursor:not-allowed;" title="Cannot edit a closed period">Edit Locked</a>
                                        <a href="../process/deleteBillPeriod.php?id=<?= $p['period_id'] ?>"
                                           class="delete-action"
                                           onclick="return confirm('Delete this closed billing period?');">Delete</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>
</div>

<!-- Flatpickr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const durationSelect  = document.getElementById('duration');
    const dateFromInput   = document.getElementById('date_from');
    const dateToInput     = document.getElementById('date_to');
    const periodCodeInput = document.getElementById('period_code');
    const hintBox         = document.getElementById('next_period_hint');
    const nextFromDisplay = document.getElementById('next_from_display');
    const nextToDisplay   = document.getElementById('next_to_display');
    const nextCodeDisplay = document.getElementById('next_code_display');
    const clearBtn        = document.getElementById('clear_btn');

    // ── Helpers ────────────────────────────────────────────────────────────

    /** MM/DD/YYYY → Date (null if invalid) */
    function parseMMDDYYYY(str) {
        if (!str || str.length < 10) return null;
        const [mm, dd, yyyy] = str.split('/');
        const d = new Date(`${yyyy}-${mm}-${dd}T00:00:00`);
        return isNaN(d.getTime()) ? null : d;
    }

    /** Date → MM/DD/YYYY */
    function toDisplay(dateObj) {
        const mm   = String(dateObj.getMonth() + 1).padStart(2, '0');
        const dd   = String(dateObj.getDate()).padStart(2, '0');
        const yyyy = dateObj.getFullYear();
        return `${mm}/${dd}/${yyyy}`;
    }

    /** Date → YYYY-MM-DD */
    function toYMD(dateObj) {
        const mm = String(dateObj.getMonth() + 1).padStart(2, '0');
        const dd = String(dateObj.getDate()).padStart(2, '0');
        return `${dateObj.getFullYear()}-${mm}-${dd}`;
    }

    /** Add N calendar months, handling month-end overflow */
    function addMonths(dateObj, months) {
        const day = dateObj.getDate();
        const d   = new Date(dateObj);
        d.setMonth(d.getMonth() + months);
        if (d.getDate() !== day) d.setDate(0);
        return d;
    }

    /** YYYY-MM-DD → MMDDYY (bp_code segment) */
    function toBpSegment(ymd) {
        const [yyyy, mm, dd] = ymd.split('-');
        return mm + dd + yyyy.substring(2);
    }

    function getMonths() { return parseInt(durationSelect.value, 10); }

    // ── Recalculate (runs after From Date or Duration changes) ─────────────
    function recalculate() {
        const fromDate = parseMMDDYYYY(dateFromInput.value);
        if (!fromDate) {
            fpTo.clear();
            periodCodeInput.value = '';
            hintBox.style.display = 'none';
            return;
        }
        const months = getMonths();
        const toDate = addMonths(fromDate, months);

        // Push new To Date into Flatpickr
        fpTo.setDate(toDate, true, 'm/d/Y');

        // Period code
        periodCodeInput.value =
            toBpSegment(toYMD(fromDate)) + '_' + toBpSegment(toYMD(toDate));

        // Next-period preview
        const nextTo = addMonths(toDate, months);
        nextFromDisplay.textContent = toDisplay(toDate);
        nextToDisplay.textContent   = toDisplay(nextTo);
        nextCodeDisplay.textContent =
            toBpSegment(toYMD(toDate)) + '_' + toBpSegment(toYMD(nextTo));
        hintBox.style.display = 'block';
    }

    // Runs when the user manually picks / changes the To Date
    function onToDateChange() {
        const fromDate = parseMMDDYYYY(dateFromInput.value);
        const toDate   = parseMMDDYYYY(dateToInput.value);
        if (!fromDate || !toDate) {
            periodCodeInput.value = '';
            hintBox.style.display = 'none';
            return;
        }
        periodCodeInput.value =
            toBpSegment(toYMD(fromDate)) + '_' + toBpSegment(toYMD(toDate));

        const nextTo = addMonths(toDate, getMonths());
        nextFromDisplay.textContent = toDisplay(toDate);
        nextToDisplay.textContent   = toDisplay(nextTo);
        nextCodeDisplay.textContent =
            toBpSegment(toYMD(toDate)) + '_' + toBpSegment(toYMD(nextTo));
        hintBox.style.display = 'block';
    }

    // ── Flatpickr instances ────────────────────────────────────────────────
    const fpFrom = flatpickr(dateFromInput, {
        dateFormat:    'm/d/Y',   // MM/DD/YYYY display
        allowInput:    true,      // allow manual typing too
        disableMobile: true,      // always show custom picker, not native
        onChange:      recalculate,
    });

    const fpTo = flatpickr(dateToInput, {
        dateFormat:    'm/d/Y',
        allowInput:    true,
        disableMobile: true,
        onChange:      onToDateChange,
    });

    // Duration change reruns everything
    durationSelect.addEventListener('change', recalculate);

    // Clear button resets both pickers + derived fields
    clearBtn.addEventListener('click', () => {
        fpFrom.clear();
        fpTo.clear();
        periodCodeInput.value = '';
        hintBox.style.display = 'none';
    });
});
</script>

</body>
</html>