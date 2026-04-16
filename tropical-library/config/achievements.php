<?php
function getUserAchievements($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM user_achievements WHERE user_id = :user_id ORDER BY 
                           CASE 
                              WHEN required_books = 1 THEN 1
                              WHEN required_books = 5 THEN 2
                              WHEN required_books = 20 THEN 3
                              WHEN required_books = 50 THEN 4
                              ELSE 5
                           END");
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll();
}

function checkAndUpdateAchievements($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = :user_id AND status = 'read'");
    $stmt->execute([':user_id' => $userId]);
    $totalRead = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT genre) FROM books WHERE user_id = :user_id AND status = 'read' AND genre != ''");
    $stmt->execute([':user_id' => $userId]);
    $uniqueGenres = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT * FROM user_achievements WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $achievements = $stmt->fetchAll();
    
    $newEarned = [];
    
    foreach ($achievements as $ach) {
        if ($ach['earned']) continue;
        
        $shouldEarn = false;
        if ($ach['required_books'] !== null && $totalRead >= $ach['required_books']) {
            $shouldEarn = true;
        }
        if ($ach['required_genres'] !== null && $uniqueGenres >= $ach['required_genres']) {
            $shouldEarn = true;
        }
        
        if ($shouldEarn) {
            $update = $pdo->prepare("UPDATE user_achievements SET earned = TRUE, earned_at = NOW() 
                                     WHERE user_id = :user_id AND achievement_key = :key");
            $update->execute([':user_id' => $userId, ':key' => $ach['achievement_key']]);
            $newEarned[] = $ach;
        }
    }
    
    return [
        'total_read' => $totalRead,
        'unique_genres' => $uniqueGenres,
        'new_achievements' => $newEarned
    ];
}

function getAchievementProgress($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = :user_id AND status = 'read'");
    $stmt->execute([':user_id' => $userId]);
    $totalRead = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT genre) FROM books WHERE user_id = :user_id AND status = 'read' AND genre != ''");
    $stmt->execute([':user_id' => $userId]);
    $uniqueGenres = $stmt->fetchColumn();
    
    return ['total_read' => $totalRead, 'unique_genres' => $uniqueGenres];
}

function setNewAchievements($newAchs) {
    if (!isset($_SESSION['new_achievements'])) {
        $_SESSION['new_achievements'] = [];
    }
    foreach ($newAchs as $ach) {
        $_SESSION['new_achievements'][] = $ach;
    }
}

function getNewAchievements() {
    $newAchs = $_SESSION['new_achievements'] ?? [];
    $_SESSION['new_achievements'] = [];
    return $newAchs;
}
?>