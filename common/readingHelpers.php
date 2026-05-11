<?php
/**
 * common/readingHelpers.php
 * Shared helper functions for the Bill Reading module.
 * Requires: database/config.php  (provides $pdo + requireLogin())
 */

/* ===================================================================
   WATER RATE CALCULATOR
   =================================================================== */
//ALL GOOD
function getWaterRates(PDO $pdo): array {
    $stmt = $pdo->query(
        "SELECT rate_id, from_cb, to_cb, amount, bill_type
         FROM water_rates
         ORDER BY from_cb ASC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Calculate water charge for a given consumption (m³).
 * FIXED bracket = flat fee; VARIABLE bracket = per-m³ rate.
 */
function computeWaterCharge(float $consumption, array $rates): float {
    if ($consumption <= 0) return 0.0;

    $charge    = 0.0;
    $remaining = $consumption;

    foreach ($rates as $rate) {
        if ($remaining <= 0) break;

        $from = (int)   $rate['from_cb'];
        $to   = $rate['to_cb'] !== null ? (int)$rate['to_cb'] : null;
        $amt  = (float) $rate['amount'];
        $type = $rate['bill_type']; // FIXED | VARIABLE

        if ($to !== null) {
            $bracketSize = $to - $from + 1;
            $consumed    = min($remaining, $bracketSize);
        } else {
            $consumed = $remaining; // unlimited bracket
        }

        $charge    += ($type === 'FIXED') ? $amt : ($consumed * $amt);
        $remaining -= $consumed;
    }

    return round($charge, 2);
}

/**
 * Compute discount amount for a member.
 * Returns array with discount meta + amount (caller subtracts from base).
 */
/* ===================================================================
   BILLING PERIOD
   =================================================================== */

function getActivePeriod(PDO $pdo): ?array {
    $stmt = $pdo->query(
        "SELECT period_id, bp_code, start_date, end_date, status
         FROM bill_periods
         WHERE status = 'OPEN'
         ORDER BY period_id DESC
         LIMIT 1"
    );
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/* ===================================================================
   MEMBER LOOKUP
   =================================================================== */

function searchMembers(PDO $pdo, string $query, int $limit = 10): array {
    $q    = "%{$query}%";
    $stmt = $pdo->prepare(
        "SELECT m.pkey,
                CONCAT(IFNULL(m.salutation,''), ' ', m.lastname, ', ', m.firstname,
                       IF(m.middlename IS NOT NULL AND m.middlename NOT IN ('', '.', 'N/A'),
                          CONCAT(' ', LEFT(m.middlename,1), '.'), '')) AS full_name,
                m.lastname, m.firstname, m.middlename,
                a.street, a.housebldg,
                (SELECT dm.dm_id FROM discounted_members dm WHERE dm.member_id = m.pkey LIMIT 1) AS dm_id
         FROM members m
         LEFT JOIN memberaddress ma ON ma.member_key = m.pkey
                                   AND ma.address_type_key = 1
                                   AND ma.status = 'A'
         LEFT JOIN addresses a ON a.pkey = ma.address_key
         WHERE m.status = 'A'
           AND (m.lastname  LIKE ?
             OR m.firstname LIKE ?
             OR CAST(m.pkey AS CHAR) LIKE ?)
         ORDER BY m.lastname, m.firstname
         LIMIT ?"
    );
    $stmt->execute([$q, $q, $q, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMemberById(PDO $pdo, int $pkey): ?array {
    $stmt = $pdo->prepare(
        "SELECT m.pkey, m.account_number,
                CONCAT(IFNULL(m.salutation,''), ' ', m.lastname, ', ', m.firstname,
                       IF(m.middlename IS NOT NULL AND m.middlename NOT IN ('', '.', 'N/A'),
                          CONCAT(' ', LEFT(m.middlename,1), '.'), '')) AS full_name,
                m.lastname, m.firstname, m.middlename,
                m.zone,
                rc.rc_code,
                rc.rc_name,
                a.street, a.housebldg, a.zippostal_code,
                (SELECT d.discount_type FROM discounted_members dm
                 JOIN discounts d ON d.discount_id = dm.discount_id
                 WHERE dm.member_id = m.pkey LIMIT 1) AS discount_type,
                (SELECT d.discount_rate FROM discounted_members dm
                 JOIN discounts d ON d.discount_id = dm.discount_id
                 WHERE dm.member_id = m.pkey LIMIT 1) AS discount_rate
         FROM members m
         LEFT JOIN memberaddress ma ON ma.member_key = m.pkey
                                   AND ma.address_type_key = 1
                                   AND ma.status = 'A'
         LEFT JOIN addresses a  ON a.pkey  = ma.address_key
         LEFT JOIN rate_code rc ON rc.rc_id = m.rc_id
         WHERE m.pkey = ? AND m.status = 'A'"
    );
    $stmt->execute([$pkey]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function getMemberDiscounts(PDO $pdo, int $memberId): array {
    $discounts = [];

    // 1. Rate-code discount (only when active_discount != 'none')
    $stmt = $pdo->prepare(
        "SELECT rc.rc_code, rc.rc_name,
                rc.discount_percent, rc.discount_value, rc.active_discount
         FROM members m
         JOIN rate_code rc ON rc.rc_id = m.rc_id
         WHERE m.pkey = ? AND m.status = 'A'
           AND rc.active_discount != 'none'"
    );
    $stmt->execute([$memberId]);
    $rc = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($rc) {
        $discounts[] = [
            'id'     => 'rc_' . $rc['rc_code'],
            'source' => 'rate_code',
            'label'  => 'Rate Code ' . $rc['rc_code'] . ' — ' . $rc['rc_name'],
            'type'   => $rc['active_discount'],           // 'percent' | 'value'
            'value'  => $rc['active_discount'] === 'percent'
                            ? (float)$rc['discount_percent']
                            : (float)$rc['discount_value'],
        ];
    }

    // 2. Special discounts (Senior Citizen, PWD, etc.)
    $stmt = $pdo->prepare(
        "SELECT d.discount_id, d.discount_type, d.discount_rate
         FROM discounted_members dm
         JOIN discounts d ON d.discount_id = dm.discount_id
         WHERE dm.member_id = ?"
    );
    $stmt->execute([$memberId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $discounts[] = [
            'id'     => 'dm_' . $row['discount_id'],
            'source' => 'special',
            'label'  => $row['discount_type'],
            'type'   => 'percent',
            'value'  => (float)$row['discount_rate'],
        ];
    }

    return $discounts;
}



function getPreviousReading(PDO $pdo, int $memberId, int $currentPeriodId): ?float {
    $stmt = $pdo->prepare(
        "SELECT br.pres_reading
         FROM bill_readings br
         WHERE br.member_id = ?
           AND br.period_id < ?
         ORDER BY br.period_id DESC
         LIMIT 1"
    );
    $stmt->execute([$memberId, $currentPeriodId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (float)$row['pres_reading'] : null;
}

function getCurrentReading(PDO $pdo, int $memberId, int $periodId): ?array {
    $stmt = $pdo->prepare(
        "SELECT reading_id, prev_reading, pres_reading, consumption,
                is_billed, is_edited_override, entry_mode, encoded_by,
                created_at, updated_at
         FROM bill_readings
         WHERE member_id = ? AND period_id = ?
         LIMIT 1"
    );
    $stmt->execute([$memberId, $periodId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function getMembersByStreet(PDO $pdo, string $street, int $periodId): array {
   $stmt = $pdo->prepare("
    SELECT m.pkey,
           CONCAT(m.lastname, ', ', m.firstname,
                  IF(m.middlename IS NOT NULL AND m.middlename NOT IN ('', '.', 'N/A'),
                     CONCAT(' ', LEFT(m.middlename,1), '.'), '')) AS full_name,
           a.street, a.housebldg,
           br.reading_id, br.prev_reading, br.pres_reading,
           br.consumption, br.is_billed, br.is_edited_override,
           (SELECT d.discount_type 
            FROM discounted_members dm
            JOIN discounts d ON d.discount_id = dm.discount_id
            WHERE dm.member_id = m.pkey LIMIT 1) AS discount_type
    FROM members m
    JOIN memberaddress ma ON ma.member_key = m.pkey
                          AND ma.address_type_key = 1
                          AND ma.status = 'A'
    JOIN addresses a
        ON a.pkey = ma.address_key
    LEFT JOIN bill_readings br
        ON br.member_id = m.pkey AND br.period_id = ?
    WHERE m.status = 'A'
      AND LOWER(TRIM(a.street)) = LOWER(TRIM(?))
    ORDER BY m.lastname, m.firstname
");
    $stmt->execute([$periodId, $street]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStreetList(PDO $pdo): array {
    $stmt = $pdo->query(
        "SELECT DISTINCT street
         FROM addresses
         WHERE status = 'A' AND street IS NOT NULL AND street != ''
         ORDER BY street ASC"
    );
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/* ===================================================================
   FLASH MESSAGES
   =================================================================== */
if (!function_exists('setFlash')) {
    function setFlash(string $type, string $msg): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
    }
}

if (!function_exists('flash')) {
    function flash(): ?array {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['flash'])) return null;
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
}

/* ===================================================================
   MISC UTILS
   =================================================================== */
function initials(string $name): string {
    $parts = preg_split('/[\s,]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
    $init  = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $init .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $init ?: '??';
}

function fmtPeso(float $amount): string {
    return '₱ ' . number_format($amount, 2);
}

function fmtCubic(float $m3): string {
    return number_format($m3, 2) . ' m³';
}

function getZoneList(PDO $pdo): array {
    $stmt = $pdo->query(
        "SELECT DISTINCT zone FROM members WHERE status='A' AND zone IS NOT NULL ORDER BY zone"
    );
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}