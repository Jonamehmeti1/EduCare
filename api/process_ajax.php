<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['name']) || !isset($input['email']) || !isset($input['subject'])) {
    echo json_encode(['success' => false, 'message' => 'Të dhëna të paplota!']);
    exit;
}

try {
    $pdo->beginTransaction();

    $default_password = password_hash('password123', PASSWORD_BCRYPT); 
    
    $stmt1 = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt1->execute([$input['name'], $input['email'], $default_password, $input['role']]);
    $userId = $pdo->lastInsertId();

    $stmt2 = $pdo->prepare("INSERT INTO teachers (user_id, subject, status) VALUES (?, ?, 'Active')");
    $stmt2->execute([$userId, $input['subject']]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Profesor u shtua me sukses përmes AJAX!']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Gabim gjatë transaksionit: ' . $e->getMessage()]);
}
?>