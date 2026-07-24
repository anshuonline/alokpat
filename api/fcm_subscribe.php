<?php
/**
 * Firebase FCM Token Subscribe API
 */
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$token = trim($input['token'] ?? '');

if (empty($token)) {
    echo json_encode(['success' => false, 'error' => 'Token is required']);
    exit;
}

try {
    global $db;
    
    // Check if token exists
    $stmt = $db->prepare("SELECT id FROM fcm_subscribers WHERE token = ?");
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() === 0) {
        // Insert new token
        $insert = $db->prepare("INSERT INTO fcm_subscribers (token) VALUES (?)");
        $insert->execute([$token]);
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
