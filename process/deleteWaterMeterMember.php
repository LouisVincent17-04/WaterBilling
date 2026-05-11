<?php
session_start();
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDB();
    try {
        $stmt = $pdo->prepare("DELETE FROM water_meter_members WHERE wmm_id = :id");
        $stmt->execute([':id' => $_POST['wmm_id']]);
        $_SESSION['flash']['success'] = "Rate tier deleted.";
    } catch (PDOException $e) {
        $_SESSION['flash']['error'] = "Failed to delete tier.";
    }
}
header('Location: ../views/water_meter.php');
exit;