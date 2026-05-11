<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../database/config.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bp_code    = trim($_POST['period_code'] ?? '');

    // Convert MM/DD/YYYY → YYYY-MM-DD for storage
    $start_date_obj = DateTime::createFromFormat('m/d/Y', $_POST['date_from'] ?? '');
    $end_date_obj   = DateTime::createFromFormat('m/d/Y', $_POST['date_to']   ?? '');
    $start_date     = $start_date_obj ? $start_date_obj->format('Y-m-d') : null;
    $end_date       = $end_date_obj   ? $end_date_obj->format('Y-m-d')   : null;

    $opened_by  = $_SESSION['user_id'] ?? 1;
    $created_at = date('Y-m-d H:i:s');

    // Duration in months: 1 = monthly, 3 = quarterly, 6 = semi-annual, 12 = annual
    // We cast to int and restrict to known valid values for safety.
    $raw_duration = (int)($_POST['duration'] ?? 1);
    $duration     = in_array($raw_duration, [1, 3, 6, 12], true) ? $raw_duration : 1;

    if ($bp_code && $start_date && $end_date) {

        $sql = "INSERT INTO bill_periods (bp_code, start_date, end_date, status, opened_by, created_at, updated_at) 
                VALUES (:bp_code, :start_date, :end_date, 'open', :opened_by, :created_at, :updated_at)";

        try {
            // --- Insert the current (requested) period ---
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':bp_code'    => $bp_code,
                ':start_date' => $start_date,
                ':end_date'   => $end_date,
                ':opened_by'  => $opened_by,
                ':created_at' => $created_at,
                ':updated_at' => $created_at,
            ]);

            // --- Auto-calculate and insert the NEXT period ---
            // Uses the same duration interval the user selected.
            // e.g. quarterly: next start = current end, next end = current end + 3 months
            $next_start = $end_date;
            $next_end   = addCalendarMonths($end_date, $duration);
            $next_code  = generateBpCode($next_start, $next_end);

            try {
                $stmt2 = $pdo->prepare($sql);
                $stmt2->execute([
                    ':bp_code'    => $next_code,
                    ':start_date' => $next_start,
                    ':end_date'   => $next_end,
                    ':opened_by'  => $opened_by,
                    ':created_at' => $created_at,
                    ':updated_at' => $created_at,
                ]);

                $_SESSION['success_msg'] = "Billing period created successfully. "
                    . "Next period ({$next_code}) was also auto-created: "
                    . date('m/d/Y', strtotime($next_start))
                    . " → "
                    . date('m/d/Y', strtotime($next_end)) . ".";

            } catch (PDOException $e) {
                // Next period already exists — skip silently.
                $_SESSION['success_msg'] = "Billing period created successfully. "
                    . "(Next period {$next_code} already exists — skipped.)";
            }

        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Error creating period: " . $e->getMessage();
        }

    } else {
        $_SESSION['error_msg'] = "Please fill in all required fields.";
    }

    header("Location: ../views/bill_period.php");
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// Helper: adds N calendar months to a YYYY-MM-DD date.
// Handles month-end overflow (e.g. Jan 31 + 1 month → Feb 28, not Mar 3).
// Returns YYYY-MM-DD string.
// ─────────────────────────────────────────────────────────────────────────────
function addCalendarMonths(string $dateStr, int $months): string {
    $d   = new DateTime($dateStr);
    $day = (int)$d->format('d');

    $d->modify("+{$months} months");

    // If the day shifted (overflow), roll back to end of the target month
    if ((int)$d->format('d') !== $day) {
        $d->modify('last day of last month');
    }

    return $d->format('Y-m-d');
}

// ─────────────────────────────────────────────────────────────────────────────
// Helper: generates bp_code from two YYYY-MM-DD dates → MMDDYY_MMDDYY
// ─────────────────────────────────────────────────────────────────────────────
function generateBpCode(string $start, string $end): string {
    $f = explode('-', $start);
    $t = explode('-', $end);
    return $f[1] . $f[2] . substr($f[0], 2)
         . '_'
         . $t[1] . $t[2] . substr($t[0], 2);
}