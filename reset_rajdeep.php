<?php
require_once 'config/config.php';

$username = 'rajdeep';
$new_password = '1234';
$hashed_password = md5($new_password); // The app uses MD5 currently based on User.php

try {
    $sql = "UPDATE users SET password = :password WHERE username = :username OR full_name LIKE '%rajdeep%' OR email LIKE '%rajdeep%'";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':username', $username);
    
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo "Success: Password for rajdeep updated to 1234.";
        } else {
            echo "Error: User rajdeep not found in the database.";
        }
    } else {
        echo "Error: Could not execute query.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Delete this file automatically after running for security
@unlink(__FILE__);
?>
