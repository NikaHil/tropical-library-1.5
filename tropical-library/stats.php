<?php
require_once 'config/database.php';
requireAuth();

$userId = getCurrentUserId();

// Общая статистика (только для текущего пользователя)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = ?");
$stmt->execute([$userId]);
$total = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = ? AND status = 'read'");
$stmt->execute([$userId]);
$read = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = ? AND status = 'reading'");
$stmt->execute([$userId]);
$reading = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = ? AND status = 'want'");
$stmt->execute([$userId]);
$want = $stmt->fetchColumn();

// Статистика по жанрам
$stmt = $pdo->prepare("SELECT genre, COUNT(*) as count FROM books WHERE user_id = ? AND genre != '' GROUP BY genre ORDER BY count DESC LIMIT 5");
$stmt->execute([$userId]);
$genreStats = $stmt->fetchAll();

// Средний рейтинг
$stmt = $pdo->prepare("SELECT AVG(rating) FROM books WHERE user_id = ? AND rating IS NOT NULL");
$stmt->execute([$userId]);
$avgRating = $stmt->fetchColumn();
$avgRating = $avgRating ? round($avgRating, 1) : 0;

// Самый читаемый автор
$stmt = $pdo->prepare("SELECT author, COUNT(*) as count FROM books WHERE user_id = ? GROUP BY author ORDER BY count DESC LIMIT 1");
$stmt->execute([$userId]);
$topAuthor = $stmt->fetch();

// Ачивки
$achievements = getUserAchievements($pdo, $userId);
$progress = getAchievementProgress($pdo, $userId);

$earnedCount = 0;
$totalCount = 0;
foreach ($achievements as $ach) {
    $totalCount++;
    if ($ach['earned']) $earnedCount++;
}
$percentComplete = $totalCount > 0 ? round(($earnedCount / $totalCount) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="theme-color" content="#1a472a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>📊 Статистика - Тропическая библиотека</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .achievements-section {
            background: linear-gradient(135deg, #fff5e6 0%, #ffe8d6 100%);
            border-radius: 20px;
            padding: 25px;
            margin-top: 35px;
            border: 2px solid #FFB347;
        }
        
        .achievements-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .achievements-header h2 {
            color: #1a472a;
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .progress-bar-container {
            background: #e0d6c8;
            border-radius: 30px;
            height: 12px;
            width: 100%;
            margin: 15px 0;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            background: linear-gradient(90deg, #FF6B35, #FFB347);
            width: <?= $percentComplete ?>%;
            height: 100%;
            border-radius: 30px;
            transition: width 0.5s ease;
        }
        
        .progress-stats {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #1a472a;
            font-weight: 500;
        }
        
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        
        .achievement-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid #ffe0b5;
            position: relative;
            overflow: hidden;
        }
        
        .achievement-card.earned {
            border-color: #FF6B35;
            background: linear-gradient(135deg, #fff 0%, #fff5e6 100%);
            box-shadow: 0 5px 20px rgba(255, 107, 53, 0.2);
        }
        
        .achievement-card.earned::before {
            content: "🏆";
            position: absolute;
            top: -10px;
            right: -10px;
            font-size: 50px;
            opacity: 0.15;
            transform: rotate(15deg);
        }
        
        .achievement-card.locked {
            opacity: 0.7;
        }
        
        .achievement-icon {
            font-size: 64px;
            margin-bottom: 12px;
        }
        
        .achievement-name {
            font-size: 18px;
            font-weight: 700;
            color: #1a472a;
            margin-bottom: 8px;
        }
        
        .achievement-desc {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .achievement-status {
            font-size: 13px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 50px;
            display: inline-block;
        }
        
        .status-earned {
            background: #2ECC71;
            color: white;
        }
        
        .status-locked {
            background: #e0d6c8;
            color: #8b7a66;
        }
        
        .achievement-progress {
            margin-top: 12px;
            font-size: 12px;
            color: #FF6B35;
            font-weight: 500;
        }
        
        .stats-row {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ffe0b5;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .stat-badge {
            background: #f0e8dd;
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 500;
            color: #1a472a;
        }
        
        /* ===== ГРАФИКИ - НОРМАЛЬНЫЙ РАЗМЕР НА ПК, АДАПТИВ НА ТЕЛЕФОНЕ ===== */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 35px;
        }
        
        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .chart-card h3 {
            color: #1a472a;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .chart-container {
            position: relative;
            width: 100%;
            min-height: 300px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .chart-container canvas {
            max-width: 100%;
            height: auto;
            max-height: 300px;
        }
        
        /* Телефоны */
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .chart-container {
                min-height: 250px;
            }
            
            .chart-container canvas {
                max-height: 250px;
            }
            
            .achievement-icon {
                font-size: 48px;
            }
        }
        
        @media (max-width: 480px) {
            .chart-container {
                min-height: 220px;
            }
            
            .chart-container canvas {
                max-height: 220px;
            }
            
            .achievement-icon {
                font-size: 40px;
            }
        }
    </style>
</head>
<body>

    <div class="leaves-bg"></div>
    
    <div class="container">
        <header>
            <h1>🌴📊 Тропическая статистика</h1>
            <a href="index.php" class="btn btn-secondary">← На главную</a>
        </header>
        
<div class="stats-cards">
    <div class="stat-card">
        <div class="stat-icon" style="font-size: 64px; margin-bottom: 10px;">📚</div>
        <div class="stat-number"><?= $total ?></div>
        <div class="stat-label">Всего книг</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="font-size: 64px; margin-bottom: 10px;">⭐</div>
        <div class="stat-number"><?= $avgRating ?></div>
        <div class="stat-label">Средний рейтинг</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="font-size: 64px; margin-bottom: 10px;">✍️</div>
        <div class="stat-number"><?= htmlspecialchars(mb_substr($topAuthor['author'] ?? '-', 0, 15)) ?></div>
        <div class="stat-label">Любимый автор</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="font-size: 64px; margin-bottom: 10px;">🍍</div>
        <div class="stat-number"><?= $read ?></div>
        <div class="stat-label">Прочитано книг</div>
    </div>
</div>
        
        
        
        <div class="charts-grid">
            <div class="chart-card">
                <h3>📖 Статус книг</h3>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h3>🎭 Топ 5 жанров</h3>
                <div class="chart-container">
                    <canvas id="genreChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="achievements-section">
            <div class="achievements-header">
                <h2>🏆 Достижения читателя</h2>
                <p>Открой все ачивки, читая книги разных жанров</p>
                
                <div class="progress-bar-container">
                    <div class="progress-bar-fill"></div>
                </div>
                <div class="progress-stats">
                    <span>📊 Прогресс: <?= $earnedCount ?> / <?= $totalCount ?> ачивок</span>
                    <span>🏆 <?= $percentComplete ?>% завершено</span>
                </div>
            </div>
            
            <div class="achievements-grid">
                <?php foreach ($achievements as $ach): 
                    $isEarned = $ach['earned'];
                    $progressText = '';
                    
                    if ($ach['required_books'] !== null) {
                        $current = $progress['total_read'];
                        $required = $ach['required_books'];
                        $progressText = "📚 $current / $required книг";
                    } elseif ($ach['required_genres'] !== null) {
                        $current = $progress['unique_genres'];
                        $required = $ach['required_genres'];
                        $progressText = "🎭 $current / $required жанров";
                    }
                ?>
                    <div class="achievement-card <?= $isEarned ? 'earned' : 'locked' ?>">
                        <div class="achievement-icon"><?= $ach['icon'] ?></div>
                        <div class="achievement-name"><?= htmlspecialchars($ach['achievement_name']) ?></div>
                        <div class="achievement-desc"><?= htmlspecialchars($ach['achievement_desc']) ?></div>
                        
                        <?php if ($isEarned): ?>
                            <span class="achievement-status status-earned">
                                ✅ Получено <?= date('d.m.Y', strtotime($ach['earned_at'])) ?>
                            </span>
                        <?php else: ?>
                            <span class="achievement-status status-locked">🔒 Не получено</span>
                            <div class="achievement-progress"><?= $progressText ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="stats-row">
                <div class="stat-badge">📖 Всего прочитано: <?= $progress['total_read'] ?> книг</div>
                <div class="stat-badge">🎭 Разных жанров: <?= $progress['unique_genres'] ?></div>
                <div class="stat-badge">🎯 Цель: 50 книг и 5 жанров</div>
            </div>
        </div>
    </div>
    
    <script>
    // График статуса книг
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['🍍 Прочитано', '🌺 Читаю', '🥥 Хочу'],
            datasets: [{
                data: [<?= $read ?>, <?= $reading ?>, <?= $want ?>],
                backgroundColor: ['#FF6B35', '#FFB347', '#2ECC71'],
                borderWidth: 0
            }]
        },
        options: { 
            responsive: true,
            maintainAspectRatio: true,
            plugins: { 
                legend: { 
                    position: 'bottom',
                    labels: { font: { size: 12 } }
                } 
            }
        }
    });
    
    // График топ-5 жанров
    new Chart(document.getElementById('genreChart'), {
        type: 'bar',
        data: {
            labels: [<?php foreach ($genreStats as $g) echo "'" . addslashes($g['genre']) . "',"; ?>],
            datasets: [{
                label: 'Количество книг',
                data: [<?php foreach ($genreStats as $g) echo $g['count'] . ','; ?>],
                backgroundColor: '#FF6B35',
                borderRadius: 8
            }]
        },
        options: { 
            responsive: true,
            maintainAspectRatio: true,
            plugins: { 
                legend: { display: false } 
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    ticks: { font: { size: 11 } } 
                },
                x: { 
                    ticks: { font: { size: 11 } } 
                }
            }
        }
    });
    </script>
    
    <script src="js/script.js"></script>
</body>
</html>