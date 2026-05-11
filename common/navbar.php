<?php
/**
 * navbar.php
 * COWASCO Waters — Sidebar navigation component
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['user_id']) || !isset($_SESSION['full_name'])) {
    echo("<script>window.location='../views/login.php';</script>");
    exit();
}

$nav_full_name = e($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User');
$nav_username  = e($_SESSION['username'] ?? '');
$nav_role      = e($_SESSION['role'] ?? 'user');

$words = array_filter(explode(' ', $_SESSION['full_name'] ?? 'U'));
$nav_initials = strtoupper(implode('', array_map(fn($w) => $w[0] ?? '', array_slice($words, 0, 2))));
if (empty($nav_initials)) $nav_initials = 'U';

$current_page = basename($_SERVER['PHP_SELF'], '.php');

$nav_items = [
    ['href' => 'dashboard.php', 'key' => ['dashboard'], 'label' => 'Dashboard', 'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
    ['href' => 'billing_management.php', 'key' => ['billing_management', 'create_billing', 'reading_entry', 'batch_reading', 'bill_period', 'recurring_bill', 'installment_bill', 'one_time_billing', 'bill_codes'], 'label' => 'Billing Management', 'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/>'],
    ['href' => 'discount_management.php', 'key' => ['discount_management'], 'label' => 'Discount Management', 'icon' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>'],
    ['href' => 'rates_management.php', 'key' => ['rates_management', 'water_rates', 'rate_codes'], 'label' => 'Rates Management', 'icon' => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>'],
    ['href' => 'invoices.php', 'key' => ['invoices'], 'label' => 'Invoices', 'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>'],
    ['href' => 'member_list.php', 'key' => ['member_list'], 'label' => 'Member List', 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
    ['href' => 'payments.php', 'key' => ['payments'], 'label' => 'Payments', 'icon' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>'],
    ['href' => 'collection_management.php', 'key' => ['collection_management'], 'label' => 'Collection Management', 'icon' => '<path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"/>'],
    ['href' => 'reports.php', 'key' => ['reports'], 'label' => 'Reports', 'icon' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
    ['href' => 'settings.php', 'key' => ['settings'], 'label' => 'Settings', 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'],
];
?>
<style>
/* FAILSAFE: Ensure navbar variables always exist */
:root {
    --white: #ffffff; --off-white: #f8f9fb; --border: #e8eaed; --border-focus: #1a1a2e;
    --text-primary: #0f0f1a; --text-secondary: #6b7280; --text-muted: #9ca3af;
    --accent: #1a1a2e; 
}
.sidebar { position: fixed; top: 0; left: 0; width: 260px; height: 100vh; background: var(--accent); display: flex; flex-direction: column; z-index: 100; overflow: hidden; }
.sidebar::before { content: ''; position: absolute; inset: 0; opacity: .35; pointer-events: none; background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E"); }
.sidebar-brand { padding: 28px 24px 24px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,.08); position: relative; }
.sidebar-brand-mark { width: 36px; height: 36px; background: rgba(255,255,255,.12); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sidebar-brand-mark svg { width: 20px; height: 20px; }
.sidebar-brand-name { font-size: 1.125rem; font-weight: 700; color: #fff; letter-spacing: -.02em; }
.sidebar-brand-badge { font-size: .65rem; font-weight: 600; background: rgba(255,255,255,.15); color: rgba(255,255,255,.7); padding: 2px 7px; border-radius: 20px; letter-spacing: .04em; text-transform: uppercase; margin-left: auto; }
.sidebar-nav { flex: 1; padding: 20px 12px; overflow-y: auto; scrollbar-width: none; }
.sidebar-nav::-webkit-scrollbar { display: none; }
.nav-section-label { font-size: .65rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,255,255,.3); padding: 0 12px; margin-bottom: 8px; margin-top: 20px; }
.nav-section-label:first-child { margin-top: 0; }
.nav-link { display: flex; align-items: center; gap: 11px; padding: 10px 13px; border-radius: 10px; text-decoration: none; color: rgba(255,255,255,.55); font-size: .875rem; font-weight: 500; transition: background .2s, color .2s; position: relative; margin-bottom: 2px; }
.nav-link svg { width: 17px; height: 17px; flex-shrink: 0; stroke-width: 1.8; }
.nav-link:hover { background: rgba(255,255,255,.07); color: rgba(255,255,255,.9); }
.nav-link.active { background: rgba(255,255,255,.12); color: #fff; }
.nav-link.active::before { content: ''; position: absolute; left: 0; top: 20%; bottom: 20%; width: 3px; background: #fff; border-radius: 0 3px 3px 0; margin-left: -12px; }
.nav-badge { margin-left: auto; font-size: .68rem; font-weight: 700; background: #e53e3e; color: #fff; border-radius: 20px; padding: 1px 7px; min-width: 20px; text-align: center; }
.sidebar-footer { padding: 16px 12px; border-top: 1px solid rgba(255,255,255,.08); position: relative; }
.sidebar-user { display: flex; align-items: center; gap: 11px; padding: 10px 12px; border-radius: 10px; transition: background .2s; cursor: pointer; text-decoration: none; }
.sidebar-user:hover { background: rgba(255,255,255,.07); }
.sidebar-avatar { width: 34px; height: 34px; background: linear-gradient(135deg, rgba(255,255,255,.25), rgba(255,255,255,.1)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; color: #fff; flex-shrink: 0; border: 1.5px solid rgba(255,255,255,.15); }
.sidebar-user-info { min-width: 0; }
.sidebar-user-name { font-size: .8125rem; font-weight: 600; color: rgba(255,255,255,.9); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sidebar-user-role { font-size: .7rem; color: rgba(255,255,255,.4); text-transform: capitalize; margin-top: 1px; }
.sidebar-logout { display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 8px; color: rgba(255,255,255,.35); text-decoration: none; transition: background .2s, color .2s; margin-left: auto; flex-shrink: 0; }
.sidebar-logout:hover { background: rgba(255,255,255,.1); color: rgba(255,255,255,.8); }
.sidebar-logout svg { width: 16px; height: 16px; }

/* ===== TOPBAR ===== */
.topbar { position: fixed; top: 0; left: 260px; right: 0; height: 64px; background: rgba(248,249,251,.92); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 32px; z-index: 90; gap: 16px; }
.topbar-breadcrumb { display: flex; align-items: center; gap: 6px; font-size: .8rem; color: var(--text-muted); }
.topbar-breadcrumb span { color: var(--text-secondary); }
.topbar-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }
.topbar-icon-btn { width: 36px; height: 36px; border-radius: 9px; background: none; border: 1.5px solid var(--border); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-secondary); position: relative; transition: background .2s, border-color .2s; text-decoration: none; }
.topbar-icon-btn:hover { background: var(--white); border-color: var(--border-focus); }
.topbar-icon-btn svg { width: 16px; height: 16px; }
.topbar-notif-dot { position: absolute; top: 6px; right: 6px; width: 7px; height: 7px; background: #e53e3e; border-radius: 50%; border: 1.5px solid var(--off-white); }
.topbar-divider { width: 1px; height: 24px; background: var(--border); }
.topbar-user-chip { display: flex; align-items: center; gap: 8px; padding: 5px 12px 5px 5px; border-radius: 40px; border: 1.5px solid var(--border); background: var(--white); cursor: pointer; text-decoration: none; transition: border-color .2s; }
.topbar-user-chip:hover { border-color: var(--border-focus); }
.topbar-chip-avatar { width: 26px; height: 26px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-size: .65rem; font-weight: 700; color: #fff; }
.topbar-chip-name { font-size: .8rem; font-weight: 600; color: var(--text-primary); }

.hamburger { display: none; background: none; border: none; cursor: pointer; padding: 4px; color: var(--text-primary); }
.hamburger svg { width: 22px; height: 22px; }
.sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 99; backdrop-filter: blur(2px); }
.main-content { margin-left: 260px; padding-top: 64px; min-height: 100vh; }

@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); transition: transform .3s cubic-bezier(.16,1,.3,1); }
    .sidebar.open { transform: translateX(0); }
    .sidebar-overlay { display: block; opacity: 0; pointer-events: none; transition: opacity .3s; }
    .sidebar-overlay.open { opacity: 1; pointer-events: auto; }
    .topbar { left: 0; padding: 0 20px; }
    .hamburger { display: flex; }
    .main-content { margin-left: 0; }
}
</style>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-mark">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        </div>
        <div class="sidebar-brand-name">COWASCO Waters</div>
        <span class="sidebar-brand-badge">Pro</span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <?php foreach ($nav_items as $i => $item):
            $is_active = in_array($current_page, $item['key']);
        ?>
        <a href="<?= $item['href'] ?>" class="nav-link <?= $is_active ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><?= $item['icon'] ?></svg>
            <?= $item['label'] ?>
            <?php if (in_array('invoices', $item['key'])): ?><span class="nav-badge">3</span><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= $nav_initials ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= $nav_full_name ?></div>
                <div class="sidebar-user-role"><?= $nav_role ?></div>
            </div>
            <a href="../process/logout.php" class="sidebar-logout" title="Sign out">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </a>
        </div>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<header class="topbar">
    <button class="hamburger" onclick="openSidebar()" aria-label="Open menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
    <div>
        <div class="topbar-breadcrumb">
            <span>COWASCO Waters</span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            <span><?= ucfirst(str_replace('_', ' ', $current_page)) ?></span>
        </div>
    </div>
    <div class="topbar-right">
        <a href="search.php" class="topbar-icon-btn" title="Search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></a>
        <a href="notifications.php" class="topbar-icon-btn" title="Notifications">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <span class="topbar-notif-dot"></span>
        </a>
        <div class="topbar-divider"></div>
        <a href="profile.php" class="topbar-user-chip">
            <div class="topbar-chip-avatar"><?= $nav_initials ?></div>
            <span class="topbar-chip-name"><?= $nav_full_name ?></span>
        </a>
    </div>
</header>
<script>
function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('sidebarOverlay').classList.add('open'); }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebarOverlay').classList.remove('open'); }
</script>