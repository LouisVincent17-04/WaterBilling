<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// TODO: Include your actual database connection
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDB();
    $code_id = filter_input(INPUT_POST, 'code_id', FILTER_VALIDATE_INT);
    
    if (!$code_id) {
        $_SESSION['error_msg'] = "Invalid Bill Code ID.";
        header("Location: ../views/bill_codes.php");
        exit();
    }

    $code           = strtoupper(trim(filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING)));
    $description    = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
    $type   = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
    $default_amount = !empty($_POST['default_amount']) ? filter_input(INPUT_POST, 'default_amount', FILTER_VALIDATE_FLOAT) : 0.00;
    $modified_by    = $_SESSION['user_id'] ?? 1; 

    $gl_account = !empty($_POST['gl_account']) ? filter_input(INPUT_POST, 'gl_account', FILTER_VALIDATE_INT) : null;

    try {
        $stmt = $pdo->prepare("
            UPDATE bill_codes 
            SET code = :code, 
                description = :description, 
                type = :type, 
                gl_account = :gl_account, 
                default_amount = :default_amount, 
                modified_by = :modified_by,
                updated_at = NOW()
            WHERE code_id = :code_id
        ");
        
        $stmt->execute([
            ':code'           => $code,
            ':description'    => $description,
            ':type'   => $type,
            ':gl_account'     => $gl_account,
            ':default_amount' => $default_amount,
            ':modified_by'    => $modified_by,
            ':code_id'        => $code_id
        ]);

        $_SESSION['success_msg'] = "Bill Code '{$code}' updated successfully!";
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error_msg'] = "A Bill Code with the name '{$code}' already exists.";
        } else {
            $_SESSION['error_msg'] = "Database error. Failed to update code.";
        }
    }

    header("Location: ../views/bill_codes.php");
    exit();
}