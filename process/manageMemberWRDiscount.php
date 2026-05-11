<?php
/**
 * process/manage_member_discounts.php
 * Handles Assigning / Removing discounts using the water_meter_discounted_members table.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../database/config.php';

header('Content-Type: application/json');
$pdo = getDB();

$method = $_SERVER['REQUEST_METHOD'];

// ─── GET REQUESTS (Fetching Data) ──────────────────────────────────────────
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    try {
        // 1. List all members who are enrolled in the new table
        if ($action === 'list_assigned') {
            $stmt = $pdo->query("
                SELECT 
                    m.pkey, m.firstname, m.lastname, m.zone,
                    a.street, a.housebldg,
                    d.wmd_name, d.free_water_m3, d.active_discount, d.percent_discount, d.fixed_discount
                FROM water_meter_discounted_members md
                INNER JOIN members m ON md.member_id = m.pkey
                LEFT JOIN memberaddress ma ON m.pkey = ma.member_key
                LEFT JOIN addresses a ON ma.address_key = a.pkey
                INNER JOIN water_meter_discounts d ON md.wmdiscount_id = d.wmdiscount_id
                ORDER BY m.firstname ASC
            ");
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($results);
            exit;
        }

        // 2. Fetch the list of available discount programs (for the dropdown)
        if ($action === 'list_discounts') {
            $stmt = $pdo->query("SELECT wmdiscount_id, wmd_name FROM water_meter_discounts ORDER BY wmd_name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }

        // 3. Search for members (for the Assignment Modal)
        if ($action === 'search_members') {
            $q = trim($_GET['q'] ?? '');
            if (strlen($q) < 2) {
                echo json_encode([]);
                exit;
            }

            // Search by ID, Firstname, or Lastname
            $stmt = $pdo->prepare("
            SELECT pkey, firstname, lastname, zone 
            FROM members 
            WHERE pkey LIKE :q1 
            OR firstname LIKE :q2 
            OR lastname LIKE :q3
            ORDER BY firstname ASC 
            LIMIT 10
        ");

        $likeQ = '%' . $q . '%';

        $stmt->execute([
            ':q1' => $likeQ,
            ':q2' => $likeQ,
            ':q3' => $likeQ
        ]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// ─── POST REQUESTS (Assign or Remove) ──────────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['error' => 'Invalid JSON payload.']);
        exit;
    }

    $action    = $input['action'] ?? '';
    $member_id = (int)($input['member_id'] ?? 0);
    $user      = $_SESSION['username'] ?? 'system';
    $now       = date('Y-m-d H:i:s');

    if (!$member_id) {
        echo json_encode(['error' => 'Valid Member ID is required.']);
        exit;
    }

    try {
        // ASSIGN DISCOUNT TO MEMBER
        if ($action === 'assign') {
            $discount_id = (int)($input['wmdiscount_id'] ?? 0);
            if (!$discount_id) {
                echo json_encode(['error' => 'Discount ID is required.']);
                exit;
            }

            // Delete any existing discount for this member to prevent duplicates
            $stmtDel = $pdo->prepare("DELETE FROM water_meter_discounted_members WHERE member_id = ?");
            $stmtDel->execute([$member_id]);

            // Insert into the new enrollment table
            $stmtIns = $pdo->prepare("
                INSERT INTO water_meter_discounted_members 
                (member_id, wmdiscount_id, assigned_by, assigned_at) 
                VALUES (?, ?, ?, ?)
            ");
            $stmtIns->execute([$member_id, $discount_id, $user, $now]);

            echo json_encode(['success' => true]);
            exit;
        }

        // REMOVE DISCOUNT FROM MEMBER
        if ($action === 'remove') {
            $stmt = $pdo->prepare("DELETE FROM water_meter_discounted_members WHERE member_id = ?");
            $stmt->execute([$member_id]);

            echo json_encode(['success' => true]);
            exit;
        }

        echo json_encode(['error' => 'Unknown action requested.']);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}