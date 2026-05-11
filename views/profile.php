<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Default mock data
$_SESSION['full_name'] = $_SESSION['full_name'] ?? 'System Admin';
$_SESSION['username']  = $_SESSION['username'] ?? 'admin';
$_SESSION['role']      = $_SESSION['role'] ?? 'Administrator';

if (!function_exists('e')) {
    function e($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
}

// Generate initials for the avatar
$words = array_filter(explode(' ', $_SESSION['full_name'] ?? 'U'));
$profile_initials = strtoupper(implode('', array_map(fn($w) => $w[0] ?? '', array_slice($words, 0, 2))));
if (empty($profile_initials)) $profile_initials = 'U';

// Mock form submission handling for visual feedback
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // In a real app, you would validate and update the database here
        $_SESSION['full_name'] = $_POST['full_name'] ?? $_SESSION['full_name'];
        $_SESSION['username'] = $_POST['username'] ?? $_SESSION['username'];
        $success_msg = 'Personal information updated successfully.';
        
        // Regenerate initials if name changed
        $words = array_filter(explode(' ', $_SESSION['full_name'] ?? 'U'));
        $profile_initials = strtoupper(implode('', array_map(fn($w) => $w[0] ?? '', array_slice($words, 0, 2))));
    } elseif (isset($_POST['change_password'])) {
        $success_msg = 'Password changed successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — COWASCO Waters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --white: #ffffff;
            --off-white: #f8f9fb;
            --border: #e8eaed;
            --border-focus: #1a1a2e;
            --text-primary: #0f0f1a;
            --text-secondary: #6b7280;
            --accent: #1a1a2e;
            --success: #059669;
            --success-bg: #ecfdf5;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
            --radius-sm: 8px;
            --radius: 14px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
        }
        body { font-family: 'Sora', sans-serif; background: var(--off-white); color: var(--text-primary); }
        
        /* Layout Wrappers */
        .page { padding: 32px 32px 60px; animation: fadeIn .4s ease both; max-width: 900px; margin: 0 auto; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        .header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
        .title { font-size: 1.5rem; font-weight: 700; }
        .subtitle { font-size: .875rem; color: var(--text-secondary); margin-top: 4px; }
        
        /* Alert Message */
        .alert {
            background: var(--success-bg); border: 1px solid var(--success); color: var(--success);
            padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 24px;
            font-size: .875rem; font-weight: 600; display: flex; align-items: center; gap: 8px;
        }

        .card {
            background: var(--white); border: 1px solid var(--border); border-radius: var(--radius);
            padding: 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm);
        }
        .card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border);
        }
        .card-title { font-size: 1.1rem; font-weight: 600; }
        
        /* Profile Header inside Card */
        .profile-header-display {
            display: flex; align-items: center; gap: 20px; margin-bottom: 24px;
        }
        .profile-avatar-large {
            width: 72px; height: 72px; border-radius: 50%; background: var(--accent);
            color: var(--white); display: flex; align-items: center; justify-content: center;
            font-size: 1.75rem; font-weight: 700; letter-spacing: 1px;
            box-shadow: 0 4px 12px rgba(26,26,46,.15);
        }
        .profile-role-badge {
            display: inline-block; padding: 4px 10px; background: var(--off-white);
            border: 1px solid var(--border); border-radius: 20px; font-size: .7rem;
            font-weight: 700; color: var(--text-secondary); text-transform: uppercase;
            letter-spacing: .05em; margin-top: 6px;
        }

        /* Forms */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full-width { grid-column: 1 / -1; }
        
        label { font-size: .75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .05em; }
        input {
            padding: 10px 12px; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-family: inherit; font-size: .875rem; outline: none; background: var(--white); transition: border-color .2s;
        }
        input:focus { border-color: var(--border-focus); }
        input[readonly], input[disabled] { background: var(--off-white); color: var(--text-secondary); cursor: not-allowed; font-weight: 600; }
        
        /* Buttons */
        .btn-group { display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 20px;
            border: none; border-radius: var(--radius-sm); font-family: inherit; font-size: .8125rem;
            font-weight: 600; cursor: pointer; text-decoration: none; transition: background .2s, transform .1s;
        }
        .btn:active { transform: scale(0.98); }
        .btn-primary { background: var(--accent); color: var(--white); }
        .btn-primary:hover { background: #0f0f1a; }
        .btn-danger { background: var(--danger); color: var(--white); }
        .btn-danger:hover { background: #b91c1c; }
    </style>
</head>
<body>

    <?php require_once '../common/navbar.php'; ?>

    <div class="main-content">
        <main class="page">
            
            <div class="header">
                <div>
                    <h1 class="title">My Profile</h1>
                    <p class="subtitle">Manage your account settings and security preferences</p>
                </div>
            </div>

            <?php if (!empty($success_msg)): ?>
                <div class="alert">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <?= e($success_msg) ?>
                </div>
            <?php endif; ?>

            <!-- SECTION 1: PERSONAL INFORMATION -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Personal Information</h2>
                </div>

                <div class="profile-header-display">
                    <div class="profile-avatar-large">
                        <?= e($profile_initials) ?>
                    </div>
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 2px;">
                            <?= e($_SESSION['full_name']) ?>
                        </h3>
                        <div class="profile-role-badge"><?= e($_SESSION['role']) ?></div>
                    </div>
                </div>

                <form action="profile.php" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?= e($_SESSION['full_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?= e($_SESSION['username']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="admin@cowascowaters.com" required>
                        </div>
                        <div class="form-group">
                            <label>Account Role</label>
                            <input type="text" value="<?= e($_SESSION['role']) ?>" disabled title="Your role can only be changed by a Super Administrator.">
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- SECTION 2: SECURITY & PASSWORD -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Security Settings</h2>
                </div>
                
                <form action="profile.php" method="POST">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Current Password</label>
                            <input type="password" name="current_password" placeholder="Enter your current password" required style="max-width: 400px;">
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" placeholder="Create a new password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" placeholder="Type new password again" required>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- SECTION 3: ACCOUNT ACTIONS -->
            <div class="card" style="border-color: #fecaca; background: #fef2f2;">
                <div class="card-header" style="border-bottom-color: #fecaca;">
                    <h2 class="card-title" style="color: var(--danger);">Account Actions</h2>
                </div>
                <p style="font-size: .875rem; color: var(--text-secondary); margin-bottom: 16px;">
                    Securely log out of your account on this device.
                </p>
                
                <div class="btn-group" style="justify-content: flex-start; margin-top: 0;">
                    <a href="../process/logout.php" class="btn btn-danger">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Sign Out
                    </a>
                </div>
            </div>

        </main>
    </div>

</body>
</html>