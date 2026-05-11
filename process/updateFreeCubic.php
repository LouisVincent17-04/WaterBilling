<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../database/config.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $free_cubics = $_POST['free_cubic'] ?? [];
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE rate_code SET free_cubic = :fc WHERE rc_id = :id");
        
        foreach ($free_cubics as $rc_id => $val) {
            $stmt->execute([
                ':fc' => (int)$val,
                ':id' => (int)$rc_id
            ]);
        }
        $pdo->commit();
        $_SESSION['flash']['success'] = 'Free cubic meters updated successfully.';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['flash']['error'] = 'Failed to update free cubic meters.';
    }
}

header('Location: ../views/water_rates.php');
exit;