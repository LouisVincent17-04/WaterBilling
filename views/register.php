<?php
require_once '../database/config.php';

requireGuest();

// ==============================================
//  Registration Validation & Processing
// ==============================================

$errors = [];
$old    = ['full_name' => '', 'username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. CSRF
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    }

    // 2. Sanitise
    $full_name        = trim($_POST['full_name']        ?? '');
    $username         = trim($_POST['username']         ?? '');
    $email            = trim($_POST['email']            ?? '');
    $password         = $_POST['password']              ?? '';
    $password_confirm = $_POST['password_confirm']      ?? '';
    $agree_terms      = !empty($_POST['agree_terms']);

    $old = compact('full_name', 'username', 'email');

    // 3. Full Name
    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($full_name) < 2) {
        $errors['full_name'] = 'Full name must be at least 2 characters.';
    } elseif (strlen($full_name) > 120) {
        $errors['full_name'] = 'Full name must not exceed 120 characters.';
    } elseif (!preg_match('/^[\p{L}\s\'-]+$/u', $full_name)) {
        $errors['full_name'] = 'Full name may only contain letters, spaces, hyphens, and apostrophes.';
    }

    // 4. Username
    if (empty($username)) {
        $errors['username'] = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username must be at least 3 characters.';
    } elseif (strlen($username) > 30) {
        $errors['username'] = 'Username must not exceed 30 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        $errors['username'] = 'Username may only contain letters, numbers, underscores, dots, and hyphens.';
    }

    // 5. Email
    if (empty($email)) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (strlen($email) > 180) {
        $errors['email'] = 'Email address is too long.';
    }

    // 6. Password
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors['password'] = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must contain at least one number.';
    } elseif (!preg_match('/[\W_]/', $password)) {
        $errors['password'] = 'Password must contain at least one special character (!@#$%...).';
    }

    // 7. Confirm password
    if (empty($errors['password'])) {
        if ($password !== $password_confirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }
    }

    // 8. Terms
    if (!$agree_terms) {
        $errors['agree_terms'] = 'You must accept the Terms of Service to register.';
    }

    // 9. Uniqueness check (only if no field errors so far)
    if (empty($errors)) {
        $db = getDB();

        $stmt = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors['username'] = 'This username is already taken.';
        }

        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email already exists.';
        }
    }

    // 10. Insert
    if (empty($errors)) {
        $db   = getDB();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $db->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, role, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, \'user\', \'active\', NOW(), NOW())'
        );
        $stmt->execute([$username, $email, $hash, $full_name]);

        redirect('login.php', 'Account created! Please sign in.', 'success');
    }
}

$flash = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — CowascoWaters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --white:      #ffffff;
            --off-white:  #f8f9fb;
            --border:     #e8eaed;
            --border-focus: #1a1a2e;
            --text-primary:   #0f0f1a;
            --text-secondary: #6b7280;
            --text-muted:     #9ca3af;
            --accent:     #1a1a2e;
            --success:    #059669;
            --success-bg: #ecfdf5;
            --error:      #dc2626;
            --error-bg:   #fef2f2;
            --radius-sm:  8px;
            --radius:     14px;
            --radius-lg:  20px;
            --shadow-lg:  0 20px 60px rgba(0,0,0,.10), 0 4px 20px rgba(0,0,0,.06);

            /* Strength colors */
            --str-weak:   #ef4444;
            --str-fair:   #f97316;
            --str-good:   #eab308;
            --str-strong: #22c55e;
        }

        html { height: 100%; }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--off-white);
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            color: var(--text-primary);
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: .5;
            pointer-events: none;
            z-index: 0;
        }

        .page-wrap {
            position: relative; z-index: 1;
            width: 100%; max-width: 500px;
            animation: slideUp .5s cubic-bezier(.16,1,.3,1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .brand {
            text-align: center;
            margin-bottom: 28px;
        }
        .brand-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px; height: 52px;
            background: var(--accent);
            border-radius: 14px;
            margin-bottom: 12px;
        }
        .brand-mark svg { width: 28px; height: 28px; }
        .brand-name { font-size: 1.5rem; font-weight: 700; letter-spacing: -.02em; }
        .brand-tagline { font-size: .8125rem; color: var(--text-muted); margin-top: 4px; }

        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 40px 36px;
        }

        .card-header { margin-bottom: 28px; }
        .card-title { font-size: 1.375rem; font-weight: 700; letter-spacing: -.02em; }
        .card-subtitle { font-size: .8125rem; color: var(--text-secondary); margin-top: 6px; }

        /* Step indicator */
        .steps {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 28px;
        }
        .step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            position: relative;
        }
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 14px;
            left: calc(50% + 14px);
            right: calc(-50% + 14px);
            height: 1px;
            background: var(--border);
        }
        .step-num {
            width: 28px; height: 28px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .75rem; font-weight: 700;
            background: var(--off-white);
            border: 1.5px solid var(--border);
            color: var(--text-muted);
            position: relative; z-index: 1;
        }
        .step.active .step-num {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }
        .step-label { font-size: .7rem; color: var(--text-muted); letter-spacing: .02em; }
        .step.active .step-label { color: var(--accent); font-weight: 600; }

        .alert {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            font-size: .8125rem; line-height: 1.5;
            margin-bottom: 20px;
            animation: fadeIn .3s ease both;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .alert-error   { background: var(--error-bg);   color: var(--error);   border: 1px solid #fecaca; }
        .alert-success { background: var(--success-bg); color: var(--success); border: 1px solid #a7f3d0; }
        .alert svg { flex-shrink: 0; width: 16px; height: 16px; margin-top: 1px; }

        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .form-group { margin-bottom: 16px; }

        label {
            display: block;
            font-size: .8125rem; font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 7px;
            letter-spacing: -.01em;
        }
        .label-note {
            font-size: .72rem;
            color: var(--text-muted);
            font-weight: 400;
            margin-left: 6px;
        }

        .input-wrap { position: relative; }
        .input-icon {
            position: absolute; left: 13px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none; display: flex;
        }
        .input-icon svg { width: 17px; height: 17px; }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 11px 14px 11px 40px;
            font-family: inherit; font-size: .9rem;
            color: var(--text-primary);
            background: var(--off-white);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            outline: none;
            transition: border-color .2s, background .2s, box-shadow .2s;
        }
        input:focus {
            border-color: var(--border-focus);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(26,26,46,.08);
        }
        input.is-error { border-color: var(--error); }
        input.is-error:focus { box-shadow: 0 0 0 3px rgba(220,38,38,.08); }
        input.is-valid { border-color: var(--success); }

        .toggle-pwd {
            position: absolute; right: 13px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; color: var(--text-muted); display: flex; padding: 2px;
        }
        .toggle-pwd:hover { color: var(--text-secondary); }
        .toggle-pwd svg { width: 17px; height: 17px; }

        .field-error {
            font-size: .78rem; color: var(--error);
            margin-top: 5px;
        }

        /* Password strength */
        .strength-wrap { margin-top: 8px; }
        .strength-bars {
            display: flex; gap: 4px; margin-bottom: 5px;
        }
        .strength-bar {
            flex: 1; height: 3px;
            border-radius: 99px;
            background: var(--border);
            transition: background .3s;
        }
        .strength-label {
            font-size: .72rem; color: var(--text-muted);
        }

        /* Password requirements */
        .pwd-reqs {
            margin-top: 8px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 10px;
        }
        .req {
            font-size: .74rem;
            color: var(--text-muted);
            display: flex; align-items: center; gap: 5px;
        }
        .req.met { color: var(--success); }
        .req-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--border);
            flex-shrink: 0;
        }
        .req.met .req-dot { background: var(--success); }

        /* Terms */
        .terms-row {
            display: flex; align-items: flex-start; gap: 10px;
            margin: 18px 0 20px;
            padding: 14px;
            background: var(--off-white);
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--border);
        }
        .terms-row.error-border { border-color: var(--error); }
        .terms-row input[type="checkbox"] {
            width: 16px; height: 16px;
            padding: 0;
            flex-shrink: 0; margin-top: 1px;
            accent-color: var(--accent); cursor: pointer;
        }
        .terms-text { font-size: .8rem; color: var(--text-secondary); line-height: 1.5; }
        .terms-text a { color: var(--accent); font-weight: 500; text-decoration: none; }
        .terms-text a:hover { text-decoration: underline; }

        .btn {
            display: flex; align-items: center; justify-content: center;
            gap: 8px; width: 100%; padding: 12px 20px;
            font-family: inherit; font-size: .9rem; font-weight: 600;
            letter-spacing: -.01em; cursor: pointer;
            border: none; border-radius: var(--radius-sm);
            transition: transform .15s, box-shadow .15s, background .15s;
        }
        .btn-primary {
            background: var(--accent); color: var(--white);
            box-shadow: 0 2px 8px rgba(26,26,46,.25);
        }
        .btn-primary:hover {
            background: #0f0f1a;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(26,26,46,.30);
        }
        .btn-primary:active { transform: translateY(0); }

        .divider {
            display: flex; align-items: center; gap: 12px;
            margin: 22px 0;
            color: var(--text-muted); font-size: .75rem;
            letter-spacing: .04em; text-transform: uppercase;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px; background: var(--border);
        }

        .login-prompt {
            text-align: center;
            font-size: .8125rem; color: var(--text-secondary);
        }
        .link {
            color: var(--accent); text-decoration: none; font-weight: 500; font-size: .8125rem;
        }
        .link:hover { text-decoration: underline; }

        .page-footer {
            text-align: center; margin-top: 24px;
            font-size: .75rem; color: var(--text-muted);
        }

        @media (max-width: 520px) {
            .card { padding: 28px 22px; }
            .form-row-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="page-wrap">

    <div class="brand">
        <div class="brand-mark">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="brand-name">CowascoWaters</div>
        <div class="brand-tagline">Start managing billing in minutes</div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Create your account</div>
            <div class="card-subtitle">Join thousands of teams streamlining their billing</div>
        </div>

        <div class="steps">
            <div class="step active">
                <div class="step-num">1</div>
                <div class="step-label">Details</div>
            </div>
            <div class="step active">
                <div class="step-num">2</div>
                <div class="step-label">Security</div>
            </div>
            <div class="step active">
                <div class="step-num">3</div>
                <div class="step-label">Confirm</div>
            </div>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= e($flash['type']) ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <?= e($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate id="register-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <!-- Full name -->
            <div class="form-group">
                <label for="full_name">Full name</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input type="text" id="full_name" name="full_name"
                        value="<?= e($old['full_name']) ?>"
                        placeholder="Jane Doe"
                        autocomplete="name"
                        class="<?= !empty($errors['full_name']) ? 'is-error' : '' ?>"
                        required>
                </div>
                <?php if (!empty($errors['full_name'])): ?>
                    <div class="field-error">↑ <?= e($errors['full_name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-row-2">
                <!-- Username -->
                <div class="form-group">
                    <label for="username">Username <span class="label-note">public</span></label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input type="text" id="username" name="username"
                            value="<?= e($old['username']) ?>"
                            placeholder="janedoe"
                            autocomplete="username"
                            class="<?= !empty($errors['username']) ? 'is-error' : '' ?>"
                            maxlength="30" required>
                    </div>
                    <?php if (!empty($errors['username'])): ?>
                        <div class="field-error">↑ <?= e($errors['username']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email address</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        </span>
                        <input type="email" id="email" name="email"
                            value="<?= e($old['email']) ?>"
                            placeholder="jane@example.com"
                            autocomplete="email"
                            class="<?= !empty($errors['email']) ? 'is-error' : '' ?>"
                            required>
                    </div>
                    <?php if (!empty($errors['email'])): ?>
                        <div class="field-error">↑ <?= e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input type="password" id="password" name="password"
                        placeholder="Min. 8 chars"
                        autocomplete="new-password"
                        class="<?= !empty($errors['password']) ? 'is-error' : '' ?>"
                        required oninput="checkStrength(this.value)">
                    <button type="button" class="toggle-pwd" onclick="togglePassword('password',this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <div class="strength-wrap">
                    <div class="strength-bars">
                        <div class="strength-bar" id="bar1"></div>
                        <div class="strength-bar" id="bar2"></div>
                        <div class="strength-bar" id="bar3"></div>
                        <div class="strength-bar" id="bar4"></div>
                    </div>
                    <div class="strength-label" id="strength-label">Enter a password</div>
                </div>
                <div class="pwd-reqs">
                    <div class="req" id="req-len"><span class="req-dot"></span>8+ characters</div>
                    <div class="req" id="req-upper"><span class="req-dot"></span>Uppercase letter</div>
                    <div class="req" id="req-lower"><span class="req-dot"></span>Lowercase letter</div>
                    <div class="req" id="req-num"><span class="req-dot"></span>Number</div>
                    <div class="req" id="req-special"><span class="req-dot"></span>Special character</div>
                </div>
                <?php if (!empty($errors['password'])): ?>
                    <div class="field-error" style="margin-top:8px">↑ <?= e($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Confirm password -->
            <div class="form-group">
                <label for="password_confirm">Confirm password</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </span>
                    <input type="password" id="password_confirm" name="password_confirm"
                        placeholder="Re-enter password"
                        autocomplete="new-password"
                        class="<?= !empty($errors['password_confirm']) ? 'is-error' : '' ?>"
                        required>
                    <button type="button" class="toggle-pwd" onclick="togglePassword('password_confirm',this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <?php if (!empty($errors['password_confirm'])): ?>
                    <div class="field-error">↑ <?= e($errors['password_confirm']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Terms -->
            <div class="terms-row <?= !empty($errors['agree_terms']) ? 'error-border' : '' ?>">
                <input type="checkbox" id="agree_terms" name="agree_terms" value="1"
                    <?= !empty($_POST['agree_terms']) ? 'checked' : '' ?>>
                <div class="terms-text">
                    <label for="agree_terms">
                        I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
                        I understand my data will be processed per these agreements.
                    </label>
                </div>
            </div>
            <?php if (!empty($errors['agree_terms'])): ?>
                <div class="field-error" style="margin-top:-10px; margin-bottom:14px">↑ <?= e($errors['agree_terms']) ?></div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                Create Account
            </button>

            <div class="divider">or</div>
            <div class="login-prompt">
                Already have an account? <a href="login.php" class="link">Sign in</a>
            </div>
        </form>
    </div>

    <div class="page-footer">© <?= date('Y') ?> CowascoWaters. All rights reserved.</div>
</div>

<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('svg');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
}

function checkStrength(pw) {
    const bars   = [1,2,3,4].map(i => document.getElementById('bar'+i));
    const label  = document.getElementById('strength-label');
    const colors = { weak:'#ef4444', fair:'#f97316', good:'#eab308', strong:'#22c55e' };

    const reqs = {
        len:     pw.length >= 8,
        upper:   /[A-Z]/.test(pw),
        lower:   /[a-z]/.test(pw),
        num:     /[0-9]/.test(pw),
        special: /[\W_]/.test(pw),
    };

    ['len','upper','lower','num','special'].forEach(r => {
        const el = document.getElementById('req-'+r);
        el.classList.toggle('met', reqs[r]);
    });

    const score = Object.values(reqs).filter(Boolean).length;
    const map = [
        { s:0, c:'#e5e7eb', t:'Enter a password' },
        { s:1, c:colors.weak,   t:'Weak' },
        { s:2, c:colors.fair,   t:'Fair' },
        { s:3, c:colors.good,   t:'Good' },
        { s:4, c:colors.good,   t:'Good' },
        { s:5, c:colors.strong, t:'Strong' },
    ];
    const { c, t } = map[score];
    bars.forEach((b, i) => b.style.background = i < score ? c : '#e5e7eb');
    label.textContent = t;
    label.style.color = c;
}
</script>
</body>
</html>