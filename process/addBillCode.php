<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// TODO: Include your actual database connection
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDB(); 
    // Sanitize and collect inputs
    $code           = strtoupper(trim(filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING)));
    $description    = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
    $type   = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
    $default_amount = !empty($_POST['default_amount']) ? filter_input(INPUT_POST, 'default_amount', FILTER_VALIDATE_FLOAT) : 0.00;
    $modified_by    = $_SESSION['user_id'] ?? 1; 

    // GL Account logic (If empty, set to NULL to respect the foreign key constraint)
    $gl_account = !empty($_POST['gl_account']) ? filter_input(INPUT_POST, 'gl_account', FILTER_VALIDATE_INT) : null;

    if (empty($code) || empty($type)) {
        $_SESSION['error_msg'] = "Code and Account Type are required fields.";
        header("Location: ../views/bill_codes.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO bill_codes (code, gl_account, description, type, default_amount, modified_by, created_at, updated_at) 
            VALUES (:code, :gl_account, :description, :type, :default_amount, :modified_by, NOW(), NOW())
        ");
        
        $stmt->execute([
            ':code'           => $code,
            ':description'    => $description,
            ':type'           => $type,
            ':gl_account'     => $gl_account,
            ':default_amount' => $default_amount,
            ':modified_by'    => $modified_by
        ]);

        $_SESSION['success_msg'] = "Bill Code '{$code}' added successfully!";
        
    } catch (PDOException $e) {
        // Catch Duplicate Entry error (1062) for the UNIQUE constraint on `code`
        if ($e->getCode() == 23000) {
            $_SESSION['error_msg'] = "A Bill Code with the name '{$code}' already exists.";
        } else {
            $_SESSION['error_msg'] = "Database error. Failed to save code.".$e->getMessage();
            error_log("Add Bill Code Error: " . $e->getMessage());
        }
    }

    header("Location: ../views/bill_codes.php?CODE={$code}&GL_ACCOUNT={$gl_account}&DESCRIPTION={$description}&TYPE={$type}&DEFAULT_AMOUNT={$default_amount}");
    exit();
} else {
    header("Location: ../views/bill_codes.php");
    exit();
}