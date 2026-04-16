<?php
require_once 'config/database.php';
requireAuth();

$userId = getCurrentUserId();
$id = $_GET['id'] ?? 0;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $id, ':user_id' => $userId]);
}

header('Location: index.php');
exit;
?>