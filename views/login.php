<?php
require_once '../database/config.php';

requireGuest(); // redirect if already logged in

// ==============================================
//  Login Validation & Processing
// ==============================================

$errors = [];
$old    = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. CSRF check
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    }

    // 2. Sanitise inputs
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);
    $old['email'] = $email;

    // 3. Field-level validation
    if (empty($email)) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    }

    // 4. Authenticate against DB
    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, email, full_name, username, password_hash, status, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors['general'] = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $errors['general'] = 'Your account is ' . $user['status'] . '. Please contact support.';
        } else {
            // ---- Success ----
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['email']  = $user['email'] ?? $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            // Update last_login_at
            $db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
               ->execute([$user['id']]);

            // Remember-me cookie
            if ($remember) {
                $token     = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + REMEMBER_ME_DAYS * 86400);
                $db->prepare(
                    'INSERT INTO user_sessions (user_id, token, user_agent, ip_address, expires_at, created_at)
                     VALUES (?, ?, ?, ?, ?, NOW())'
                )->execute([$user['id'], $token, $_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['REMOTE_ADDR'] ?? '', $expiresAt]);

                setcookie('remember_token', $token, [
                    'expires'  => time() + REMEMBER_ME_DAYS * 86400,
                    'path'     => '/',
                    'secure'   => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]);
            }

            redirect('dashboard.php', 'Welcome back, ' . e($user['full_name'] ?: $user['username']) . '!', 'success');
        }
    }
}

$flash = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — CowascoWaters</title>
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
            --accent-mid: #16213e;
            --accent-light: #e8f0fe;
            --success:    #059669;
            --success-bg: #ecfdf5;
            --error:      #dc2626;
            --error-bg:   #fef2f2;
            --warning:    #d97706;
            --radius-sm:  8px;
            --radius:     14px;
            --radius-lg:  20px;
            --shadow-sm:  0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow:     0 4px 16px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
            --shadow-lg:  0 20px 60px rgba(0,0,0,.10), 0 4px 20px rgba(0,0,0,.06);
        }

        html { height: 100%; font-size: 16px; }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--off-white);
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            color: var(--text-primary);
        }

        /* Background grid pattern */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: .5;
            pointer-events: none;
            z-index: 0;
        }

        .page-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            animation: slideUp .5s cubic-bezier(.16,1,.3,1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Logo / Brand */
        .brand {
            text-align: center;
            margin-bottom: 32px;
        }
        .brand-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px; height: 52px;
            background: var(--accent);
            border-radius: 14px;
            margin-bottom: 16px;
        }
        .brand-mark svg { width: 28px; height: 28px; }
        .brand-name {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -.02em;
            color: var(--text-primary);
        }
        .brand-tagline {
            font-size: .8125rem;
            color: var(--text-muted);
            margin-top: 4px;
            font-weight: 400;
        }

        /* Card */
        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 40px 36px;
        }

        .card-header {
            margin-bottom: 28px;
        }
        .card-title {
            font-size: 1.375rem;
            font-weight: 700;
            letter-spacing: -.02em;
            color: var(--text-primary);
        }
        .card-subtitle {
            font-size: .8125rem;
            color: var(--text-secondary);
            margin-top: 6px;
        }

        /* Alert */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            font-size: .8125rem;
            line-height: 1.5;
            margin-bottom: 20px;
            animation: fadeIn .3s ease both;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .alert-error   { background: var(--error-bg);   color: var(--error);   border: 1px solid #fecaca; }
        .alert-success { background: var(--success-bg); color: var(--success); border: 1px solid #a7f3d0; }
        .alert svg { flex-shrink: 0; width: 16px; height: 16px; margin-top: 1px; }

        /* Form */
        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            font-size: .8125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 7px;
            letter-spacing: -.01em;
        }

        .input-wrap { position: relative; }

        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            display: flex;
        }
        .input-icon svg { width: 17px; height: 17px; }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 11px 14px 11px 40px;
            font-family: inherit;
            font-size: .9rem;
            font-weight: 400;
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
        input.is-error {
            border-color: var(--error);
        }
        input.is-error:focus {
            box-shadow: 0 0 0 3px rgba(220,38,38,.08);
        }

        .toggle-pwd {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            display: flex;
            padding: 2px;
        }
        .toggle-pwd:hover { color: var(--text-secondary); }
        .toggle-pwd svg { width: 17px; height: 17px; }

        .field-error {
            font-size: .78rem;
            color: var(--error);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .8125rem;
            color: var(--text-secondary);
            cursor: pointer;
            user-select: none;
        }
        .checkbox-label input[type="checkbox"] {
            width: 16px; height: 16px;
            padding: 0;
            accent-color: var(--accent);
            cursor: pointer;
            border-radius: 4px;
        }

        .link {
            font-size: .8125rem;
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }
        .link:hover { text-decoration: underline; }

        /* Button */
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px 20px;
            font-family: inherit;
            font-size: .9rem;
            font-weight: 600;
            letter-spacing: -.01em;
            cursor: pointer;
            border: none;
            border-radius: var(--radius-sm);
            transition: transform .15s, box-shadow .15s, background .15s;
        }
        .btn-primary {
            background: var(--accent);
            color: var(--white);
            box-shadow: 0 2px 8px rgba(26,26,46,.25);
        }
        .btn-primary:hover {
            background: #0f0f1a;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(26,26,46,.30);
        }
        .btn-primary:active { transform: translateY(0); }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: var(--text-muted);
            font-size: .75rem;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1;
            height: 1px;
            background: var(--border);
        }

        .register-prompt {
            text-align: center;
            font-size: .8125rem;
            color: var(--text-secondary);
        }

        /* Footer */
        .page-footer {
            text-align: center;
            margin-top: 24px;
            font-size: .75rem;
            color: var(--text-muted);
        }

        @media (max-width: 480px) {
            .card { padding: 28px 22px; }
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
        <div class="brand-tagline">Billing management, simplified</div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Welcome back</div>
            <div class="card-subtitle">Sign in to your account to continue</div>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= e($flash['type']) ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <?= e($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= e($errors['general']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="form-group">
                <label for="email">Email address</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    </span>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= e($old['email']) ?>"
                        placeholder="you@example.com"
                        autocomplete="email"
                        class="<?= !empty($errors['email']) ? 'is-error' : '' ?>"
                        required
                    >
                </div>
                <?php if (!empty($errors['email'])): ?>
                    <div class="field-error">↑ <?= e($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        class="<?= !empty($errors['password']) ? 'is-error' : '' ?>"
                        required
                    >
                    <button type="button" class="toggle-pwd" aria-label="Toggle password visibility" onclick="togglePassword('password', this)">
                        <svg id="eye-icon-password" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <?php if (!empty($errors['password'])): ?>
                    <div class="field-error">↑ <?= e($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" value="1">
                    Keep me signed in
                </label>
                <a href="forgot-password.php" class="link">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Sign In
            </button>

            <div class="divider">or</div>

            <div class="register-prompt">
                Don't have an account?&nbsp;
                <a href="register.php" class="link">Create one — it's free</a>
            </div>
        </form>
    </div>

    <div class="page-footer">
        © <?= date('Y') ?> COWASCO Waters. All rights reserved.
    </div>
</div>

<script>
function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const icon  = btn.querySelector('svg');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
}
</script>
</body>
</html>