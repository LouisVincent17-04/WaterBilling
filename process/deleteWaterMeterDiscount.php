<?php
session_start();
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDB();
    try {
        $stmt = $pdo->prepare("DELETE FROM water_meter_discounts WHERE wmdiscount_id = :id");
        $stmt->execute([':id' => $_POST['wmdiscount_id']]);
        $_SESSION['flash']['success'] = "Discount configuration deleted.";
    } catch (PDOException $e) {
        $_SESSION['flash']['error'] = "Failed to delete discount configuration.";
    }
}
header('Location: ../views/water_meter.php');
exit;