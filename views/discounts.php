<?php
/**
 * discounts.php
 * UI Page — Discounts Management
 * Lists all discounts; allows Add, Edit, and Delete via inline modals.
 */

require_once '../database/config.php';

requireLogin();

$flash = flash();
$pdo   = getDB();

// --- Fetch all discounts ---
try {
    $stmt = $pdo->prepare(
        "SELECT d.discount_id,
                d.discount_type,
                d.discount_rate,
                d.created_at,
                d.updated_at,
                COUNT(dm.dm_id) AS member_count
         FROM discounts d
         LEFT JOIN discounted_members dm ON dm.discount_id = d.discount_id
         GROUP BY d.discount_id
         ORDER BY d.created_at DESC"
    );
    $stmt->execute();
    $discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $discounts = [];
    $flash = ['type' => 'error', 'msg' => 'Failed to load discounts: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount Management</title>
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
            --shadow-lg:      0 20px 60px rgba(0,0,0,.14), 0 4px 16px rgba(0,0,0,.08);
        }

        html { height: 100%; font-size: 16px; }
        body {
            font-family: 'Sora', sans-serif;
            background: var(--off-white);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* ── PAGE LAYOUT ───────────────────────────────────────────── */
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

        /* ── ALERTS ────────────────────────────────────────────────── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            font-size: .8375rem;
            font-weight: 500;
            margin-bottom: 24px;
            border: 1px solid transparent;
        }
        .alert-success { background: var(--success-bg); color: var(--success); border-color: #a7f3d0; }
        .alert-error   { background: var(--error-bg);   color: var(--error);   border-color: #fecaca; }
        .alert-warning { background: var(--warning-bg); color: var(--warning); border-color: #fde68a; }
        .alert-info    { background: var(--info-bg);    color: var(--info);    border-color: #bfdbfe; }

        /* ── BUTTONS ───────────────────────────────────────────────── */
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
            transition: transform .15s, box-shadow .15s, background .15s, color .15s;
            letter-spacing: -.01em;
            white-space: nowrap;
        }
        .btn svg { width: 15px; height: 15px; flex-shrink: 0; }
        .btn-primary {
            background: var(--accent);
            color: var(--white);
            box-shadow: 0 2px 8px rgba(26,26,46,.2);
        }
        .btn-primary:hover { background: #0f0f1a; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(26,26,46,.28); }
        .btn-danger {
            background: var(--error-bg);
            color: var(--error);
            border: 1.5px solid #fecaca;
        }
        .btn-danger:hover { background: #fee2e2; transform: translateY(-1px); }
        .btn-secondary {
            background: var(--white);
            color: var(--text-secondary);
            border: 1.5px solid var(--border);
        }
        .btn-secondary:hover { border-color: var(--border-focus); color: var(--text-primary); }

        /* ── CARD ──────────────────────────────────────────────────── */
        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 15px;
        }
        .card-head-title {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -.015em;
            color: var(--text-primary);
        }
        .card-head-sub {
            font-size: .75rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* ── TABLE ─────────────────────────────────────────────────── */
        .table-wrap { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .8125rem;
        }
        thead th {
            padding: 12px 24px;
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
        tbody td { padding: 14px 24px; color: var(--text-primary); vertical-align: middle; }

        .inv-id {
            font-family: 'JetBrains Mono', monospace;
            font-size: .78rem;
            font-weight: 500;
            color: var(--text-secondary);
        }
        .cell-sub {
            color: var(--text-secondary);
            font-size: .75rem;
            margin-top: 3px;
        }

        /* ── BADGES ────────────────────────────────────────────────── */
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
        .badge-success { background: var(--success-bg); color: var(--success); }
        .badge-success .badge-dot { background: var(--success); }
        .badge-info    { background: var(--info-bg);    color: var(--info); }
        .badge-info    .badge-dot { background: var(--info); }

        /* ── ACTION BUTTONS ────────────────────────────────────────── */
        .action-wrap { display: flex; gap: 6px; }
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
            transition: border-color .15s, color .15s, background .15s;
        }
        .action-btn:hover { border-color: var(--border-focus); color: var(--text-primary); }
        .action-btn-danger { color: var(--error); border-color: #fecaca; }
        .action-btn-danger:hover { background: var(--error-bg); border-color: var(--error); color: var(--error); }

        /* ── RATE PILL ─────────────────────────────────────────────── */
        .rate-pill {
            display: inline-block;
            background: var(--accent-light);
            color: var(--accent);
            font-family: 'JetBrains Mono', monospace;
            font-size: .8rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
        }

        /* ── EMPTY STATE ───────────────────────────────────────────── */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-muted);
        }
        .empty-state svg { width: 40px; height: 40px; margin-bottom: 12px; opacity: .4; }
        .empty-state p { font-size: .875rem; }

        /* ── MODAL ─────────────────────────────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 15, 26, .55);
            backdrop-filter: blur(3px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 440px;
            animation: modalIn .25s ease both;
            border: 1px solid var(--border);
        }
        @keyframes modalIn { from { opacity: 0; transform: scale(.96) translateY(8px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px 26px 18px;
            border-bottom: 1px solid var(--border);
        }
        .modal-title {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -.015em;
        }
        .modal-close {
            width: 30px; height: 30px;
            border-radius: 50%;
            border: none;
            background: var(--off-white);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            transition: background .15s, color .15s;
        }
        .modal-close:hover { background: var(--border); color: var(--text-primary); }
        .modal-close svg { width: 14px; height: 14px; }
        .modal-body { padding: 24px 26px; }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 16px 26px 22px;
            border-top: 1px solid var(--border);
        }

        /* ── FORM ELEMENTS ─────────────────────────────────────────── */
        .form-group { margin-bottom: 18px; }
        .form-group:last-child { margin-bottom: 0; }
        .form-label {
            display: block;
            font-size: .775rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
            letter-spacing: -.01em;
        }
        .form-label span { color: var(--error); margin-left: 2px; }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: .8375rem;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--white);
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus { border-color: var(--border-focus); box-shadow: 0 0 0 3px rgba(26,26,46,.08); }
        .form-hint {
            font-size: .725rem;
            color: var(--text-muted);
            margin-top: 5px;
        }

        /* ── DELETE CONFIRM MODAL CONTENT ──────────────────────────── */
        .delete-icon {
            width: 52px; height: 52px;
            border-radius: 50%;
            background: var(--error-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .delete-icon svg { width: 24px; height: 24px; color: var(--error); }
        .delete-title {
            font-size: 1rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
        }
        .delete-msg {
            font-size: .8375rem;
            color: var(--text-secondary);
            text-align: center;
            line-height: 1.55;
        }
        .delete-target {
            font-weight: 700;
            color: var(--text-primary);
        }

        @media (max-width: 768px) {
            .page { padding: 24px 20px 48px; }
            .card-head { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

<?php require_once '../common/navbar.php'; ?>

<div class="main-content">
<main class="page">

    <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <div class="page-title">Discount Management</div>
            <div class="page-subtitle">Define and manage discount types and their corresponding rates.</div>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add Discount
        </button>
    </div>

    <!-- Discounts Table Card -->
    <div class="card">
        <div class="card-head">
            <div>
                <div class="card-head-title">All Discounts</div>
                <div class="card-head-sub"><?= count($discounts) ?> discount type<?= count($discounts) !== 1 ? 's' : '' ?> configured</div>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Discount Type</th>
                        <th>Rate</th>
                        <th>Members Assigned</th>
                        <th>Created</th>
                        <th>Last Updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($discounts)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="8" y1="12" x2="16" y2="12"></line>
                                </svg>
                                <p>No discounts configured yet. Click <strong>Add Discount</strong> to get started.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($discounts as $i => $d): ?>
                    <tr>
                        <td><span class="inv-id"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span></td>
                        <td>
                            <strong><?= htmlspecialchars($d['discount_type']) ?></strong>
                        </td>
                        <td>
                            <span class="rate-pill"><?= number_format((float)$d['discount_rate'], 2) ?>%</span>
                        </td>
                        <td>
                            <?php if ((int)$d['member_count'] > 0): ?>
                                <span class="badge badge-success">
                                    <span class="badge-dot"></span>
                                    <?= number_format($d['member_count']) ?> member<?= (int)$d['member_count'] !== 1 ? 's' : '' ?>
                                </span>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: .78rem;">No members</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= date('M d, Y', strtotime($d['created_at'])) ?></div>
                            <div class="cell-sub"><?= date('h:i A', strtotime($d['created_at'])) ?></div>
                        </td>
                        <td>
                            <div><?= date('M d, Y', strtotime($d['updated_at'])) ?></div>
                            <div class="cell-sub"><?= date('h:i A', strtotime($d['updated_at'])) ?></div>
                        </td>
                        <td>
                            <div class="action-wrap">
                                <button class="action-btn"
                                    onclick="openEditModal(
                                        <?= (int)$d['discount_id'] ?>,
                                        <?= htmlspecialchars(json_encode($d['discount_type'])) ?>,
                                        <?= htmlspecialchars(json_encode($d['discount_rate'])) ?>
                                    )">
                                    Edit
                                </button>
                                <button class="action-btn action-btn-danger"
                                    onclick="openDeleteModal(
                                        <?= (int)$d['discount_id'] ?>,
                                        <?= htmlspecialchars(json_encode($d['discount_type'])) ?>,
                                        <?= (int)$d['member_count'] ?>
                                    )">
                                    Delete
                                </button>
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

<!-- ================================================================
     MODAL: ADD DISCOUNT
================================================================ -->
<div class="modal-overlay" id="addModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="addModalTitle">
        <div class="modal-header">
            <div class="modal-title" id="addModalTitle">Add New Discount</div>
            <button class="modal-close" onclick="closeAddModal()" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <form method="POST" action="addDiscount.php" id="addForm">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="add_type">
                        Discount Type <span>*</span>
                    </label>
                    <input
                        type="text"
                        id="add_type"
                        name="discount_type"
                        class="form-control"
                        placeholder="e.g. Senior Citizen, PWD, Student"
                        maxlength="100"
                        required
                        autocomplete="off"
                    >
                    <div class="form-hint">Enter a descriptive name for this discount type.</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="add_rate">
                        Discount Rate (%) <span>*</span>
                    </label>
                    <input
                        type="number"
                        id="add_rate"
                        name="discount_rate"
                        class="form-control"
                        placeholder="e.g. 20"
                        min="0"
                        max="100"
                        step="0.01"
                        required
                    >
                    <div class="form-hint">Enter a value between 0 and 100 (e.g. 20 for 20%).</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Save Discount
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================================================================
     MODAL: EDIT DISCOUNT
================================================================ -->
<div class="modal-overlay" id="editModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="modal-header">
            <div class="modal-title" id="editModalTitle">Edit Discount</div>
            <button class="modal-close" onclick="closeEditModal()" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <form method="POST" action="editDiscount.php" id="editForm">
            <input type="hidden" name="discount_id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_type">
                        Discount Type <span>*</span>
                    </label>
                    <input
                        type="text"
                        id="edit_type"
                        name="discount_type"
                        class="form-control"
                        maxlength="100"
                        required
                        autocomplete="off"
                    >
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_rate">
                        Discount Rate (%) <span>*</span>
                    </label>
                    <input
                        type="number"
                        id="edit_rate"
                        name="discount_rate"
                        class="form-control"
                        min="0"
                        max="100"
                        step="0.01"
                        required
                    >
                    <div class="form-hint">Enter a value between 0 and 100.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Update Discount
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================================================================
     MODAL: DELETE CONFIRM
================================================================ -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
        <form method="POST" action="deleteDiscount.php" id="deleteForm">
            <input type="hidden" name="discount_id" id="delete_id">
            <div class="modal-body" style="padding-top: 30px; padding-bottom: 10px;">
                <div class="delete-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6l-1 14H6L5 6"></path>
                        <path d="M10 11v6"></path><path d="M14 11v6"></path>
                        <path d="M9 6V4h6v2"></path>
                    </svg>
                </div>
                <div class="delete-title" id="deleteModalTitle">Delete Discount</div>
                <div class="delete-msg">
                    Are you sure you want to delete
                    <span class="delete-target" id="delete_label"></span>?
                    <span id="delete_warning"></span>
                    This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6l-1 14H6L5 6"></path>
                    </svg>
                    Delete Discount
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // ── ADD MODAL ──────────────────────────────────────────────────
    function openAddModal() {
        document.getElementById('addForm').reset();
        document.getElementById('addModal').classList.add('active');
        document.getElementById('add_type').focus();
    }
    function closeAddModal() {
        document.getElementById('addModal').classList.remove('active');
    }

    // ── EDIT MODAL ─────────────────────────────────────────────────
    function openEditModal(id, type, rate) {
        document.getElementById('edit_id').value   = id;
        document.getElementById('edit_type').value = type;
        document.getElementById('edit_rate').value = parseFloat(rate);
        document.getElementById('editModal').classList.add('active');
        document.getElementById('edit_type').focus();
    }
    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }

    // ── DELETE MODAL ───────────────────────────────────────────────
    function openDeleteModal(id, type, memberCount) {
        document.getElementById('delete_id').value    = id;
        document.getElementById('delete_label').textContent = '"' + type + '"';

        const warningEl = document.getElementById('delete_warning');
        if (memberCount > 0) {
            warningEl.innerHTML = '<br><strong style="color:var(--error)">Warning:</strong> This discount is assigned to ' + memberCount + ' member(s). Deletion will be blocked until all assignments are removed. ';
        } else {
            warningEl.textContent = ' ';
        }

        document.getElementById('deleteModal').classList.add('active');
    }
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
    }

    // ── CLOSE ON OVERLAY CLICK ─────────────────────────────────────
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });

    // ── CLOSE ON ESC ──────────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
        }
    });
</script>

</body>
</html>