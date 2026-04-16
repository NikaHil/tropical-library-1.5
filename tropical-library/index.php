<?php
require_once 'config/database.php';
requireAuth();

$userId = getCurrentUserId();
$username = getCurrentUsername();

$status = $_GET['status'] ?? 'all';
$genre = $_GET['genre'] ?? '';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM books WHERE user_id = :user_id";
$params = [':user_id' => $userId];

if ($status != 'all') {
    $sql .= " AND status = :status";
    $params[':status'] = $status;
}
if ($genre != '') {
    $sql .= " AND genre LIKE :genre";
    $params[':genre'] = "%$genre%";
}
if ($search != '') {
    $sql .= " AND (title LIKE :search OR author LIKE :search)";
    $params[':search'] = "%$search%";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

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

$stmt = $pdo->prepare("SELECT DISTINCT genre FROM books WHERE genre != '' AND user_id = ? ORDER BY genre");
$stmt->execute([$userId]);
$genres = $stmt->fetchAll();

$achievements = getUserAchievements($pdo, $userId);
$earnedCount = 0;
foreach ($achievements as $ach) {
    if ($ach['earned']) $earnedCount++;
}
$totalCount = count($achievements);
$newAchs = getNewAchievements();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="theme-color" content="#1a472a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>...</title>
    ...
    <title>🌺 Тропическая библиотека</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .new-achievement-toast {
            position: fixed; bottom: 30px; right: 30px;
            background: linear-gradient(135deg, #2ECC71, #27AE60);
            color: white; padding: 15px 25px; border-radius: 50px;
            display: flex; align-items: center; gap: 12px;
            z-index: 1000;
            animation: slideIn 0.5s ease, fadeOut 0.5s ease 4s forwards;
        }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { to { transform: translateX(100%); opacity: 0; visibility: hidden; } }
        .btn-logout { background: #e74c3c; color: white; }
        .btn-logout:hover { background: #c0392b; }
        
        /* Стили для модального окна */
        .delete-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .delete-modal.show {
            display: flex;
        }
        .modal-window {
            background: linear-gradient(135deg, #fff5e6, #ffe8d6);
            border-radius: 30px;
            padding: 35px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            border: 2px solid #FFB347;
            animation: modalFadeIn 0.3s ease;
        }
        @keyframes modalFadeIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-icon { font-size: 50px; margin-bottom: 15px; }
        .modal-title { color: #1a472a; margin-bottom: 15px; font-size: 24px; }
        .modal-book-title { 
            font-weight: bold; color: #FF6B35; 
            background: rgba(255,107,53,0.1); 
            padding: 5px 15px; border-radius: 50px; 
            display: inline-block; margin: 10px 0;
        }
        .modal-warning { font-size: 12px; color: #e74c3c; margin-bottom: 25px; }
        .modal-buttons { display: flex; gap: 15px; justify-content: center; }
        .modal-btn {
            padding: 12px 30px;
            border-radius: 50px;
            border: none;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        .modal-btn-confirm { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; }
        .modal-btn-confirm:hover { transform: translateY(-2px); }
        .modal-btn-cancel { background: linear-gradient(135deg, #95a5a6, #7f8c8d); color: white; }
        .modal-btn-cancel:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="leaves-bg"></div>
    
    <?php foreach ($newAchs as $ach): ?>
        <div class="new-achievement-toast">
            <span style="font-size: 30px;">🏆</span>
            <div>
                <div>Новое достижение!</div>
                <div><?= $ach['icon'] ?> <?= htmlspecialchars($ach['achievement_name']) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <div class="container">
        <header>
            <div class="header-title">
                <h1>🌴 Тропическая библиотека</h1>
                <p>Привет, <?= htmlspecialchars($username) ?>! 📚</p>
            </div>
            <div class="header-buttons">
                <a href="stats.php" class="btn btn-stats">📊 Статистика</a>
                <a href="add.php" class="btn btn-primary">🍍 Добавить книгу</a>
                <a href="logout.php" class="btn btn-logout">🚪 Выйти</a>
            </div>
        </header>

      <div class="stats-cards">
    <a href="?status=all" class="stat-card">
        <div style="font-size: 64px; margin-bottom: 10px;">🌴</div>
        <div class="stat-number"><?= $total ?></div>
        <div class="stat-label">Всего книг</div>
    </a>
    <a href="?status=read" class="stat-card">
        <div style="font-size: 64px; margin-bottom: 10px;">🍍</div>
        <div class="stat-number"><?= $read ?></div>
        <div class="stat-label">Прочитано</div>
    </a>
    <a href="?status=reading" class="stat-card">
        <div style="font-size: 64px; margin-bottom: 10px;">🌺</div>
        <div class="stat-number"><?= $reading ?></div>
        <div class="stat-label">Читаю</div>
    </a>
    <a href="?status=want" class="stat-card">
        <div style="font-size: 64px; margin-bottom: 10px;">🥥</div>
        <div class="stat-number"><?= $want ?></div>
        <div class="stat-label">Хочу</div>
    </a>
</div>
        
        <div class="achievements-widget" style="background: linear-gradient(135deg, #2d6a4f, #1a472a); border-radius: 20px; padding: 15px 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="font-size: 40px;">🏆</span>
                <div><h3 style="color: white; margin: 0;">Твои достижения</h3><p style="color: #FFB347; margin: 0;"><?= $earnedCount ?> из <?= $totalCount ?> ачивок</p></div>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php $displayed = 0; foreach ($achievements as $ach): if ($ach['earned'] && $displayed++ < 3): ?>
                    <span style="background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 50px; color: white;"><?= $ach['icon'] ?> <?= htmlspecialchars($ach['achievement_name']) ?></span>
                <?php endif; endforeach; ?>
                <?php if ($earnedCount == 0): ?><span style="color: #FFB347;">📖 Читай, чтобы получать ачивки!</span><?php elseif ($earnedCount > 3): ?><span style="color: #FFB347;">+ ещё <?= $earnedCount - 3 ?></span><?php endif; ?>
            </div>
            <a href="stats.php" style="background: #FF6B35; color: white; padding: 8px 20px; border-radius: 50px; text-decoration: none;">📊 Все ачивки →</a>
        </div>

        <div class="filters">
            <form method="GET" class="filter-form">
                <select name="status" class="filter-select">
                    <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>🌊 Все статусы</option>
                    <option value="read" <?= $status == 'read' ? 'selected' : '' ?>>🍍 Прочитано</option>
                    <option value="reading" <?= $status == 'reading' ? 'selected' : '' ?>>🌺 Читаю</option>
                    <option value="want" <?= $status == 'want' ? 'selected' : '' ?>>🥥 Хочу</option>
                </select>
                <select name="genre" class="filter-select">
                    <option value="">🌿 Все жанры</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?= htmlspecialchars($g['genre']) ?>" <?= $genre == $g['genre'] ? 'selected' : '' ?>><?= htmlspecialchars($g['genre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" placeholder="🔍 Поиск..." value="<?= htmlspecialchars($search) ?>" class="search-input">
                <button type="submit" class="btn btn-secondary">🌿 Найти</button>
                <a href="index.php" class="btn btn-link">Сбросить</a>
            </form>
        </div>

        <div class="books-grid">
            <?php if (count($books) == 0): ?>
                <div class="empty-state"><div class="empty-icon">🍍📖🌺</div><h3>В твоей библиотеке пока пусто</h3><p>Добавь первую книгу и начинай читать!</p><a href="add.php" class="btn btn-primary">➕ Добавить книгу</a></div>
            <?php else: foreach ($books as $book): ?>
                <div class="book-card">
                    <div class="book-status status-<?= $book['status'] ?>"><?= ['read'=>'🍍 Прочитано','reading'=>'🌺 Читаю','want'=>'🥥 Хочу'][$book['status']] ?></div>
                    <div class="book-content">
                        <h3 class="book-title"><?= htmlspecialchars($book['title']) ?></h3>
                        <p class="book-author">✍️ <?= htmlspecialchars($book['author']) ?></p>
                        <div class="book-details">
                            <?php if ($book['genre']): ?>
                                <span class="book-tag"><?= getGenreIcon($book['genre'], $genreIcons) ?> <?= htmlspecialchars($book['genre']) ?></span>
                            <?php endif; ?>
                            <?php if ($book['year']): ?>
                                <span class="book-tag">📅 <?= $book['year'] ?></span>
                            <?php endif; ?>
                            <?php if ($book['rating']): ?>
                                <span class="book-rating">⭐ <?= $book['rating'] ?>/10</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($book['review']): ?><p class="book-review">💭 <?= htmlspecialchars(mb_substr($book['review'], 0, 100)) ?>...</p><?php endif; ?>
                    </div>
                    <div class="book-actions">
                        <a href="edit.php?id=<?= $book['id'] ?>" class="btn-icon btn-edit">✏️ Редактировать</a>
                        <a href="delete.php?id=<?= $book['id'] ?>" class="btn-icon btn-delete">🗑️ Удалить</a>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div id="deleteModal" class="delete-modal">
        <div class="modal-window">
            <div class="modal-icon">🗑️🌺</div>
            <h3 class="modal-title">Удалить книгу?</h3>
            <p>Ты точно хочешь удалить книгу</p>
            <div id="modalBookTitle" class="modal-book-title"></div>
            <div class="modal-warning">⚠️ Это действие нельзя отменить</div>
            <div class="modal-buttons">
                <button id="modalConfirmBtn" class="modal-btn modal-btn-confirm">🗑️ Да, удалить</button>
                <button id="modalCancelBtn" class="modal-btn modal-btn-cancel">🌿 Отмена</button>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
    // Модальное окно для удаления
    (function() {
        var modal = document.getElementById('deleteModal');
        var modalBookTitle = document.getElementById('modalBookTitle');
        var confirmBtn = document.getElementById('modalConfirmBtn');
        var cancelBtn = document.getElementById('modalCancelBtn');
        var currentDeleteUrl = '';
        
        function showModal(bookTitle, deleteUrl) {
            modalBookTitle.textContent = bookTitle;
            currentDeleteUrl = deleteUrl;
            modal.classList.add('show');
        }
        
        function hideModal() {
            modal.classList.remove('show');
            currentDeleteUrl = '';
        }
        
        // Находим все кнопки удаления
        var deleteButtons = document.querySelectorAll('.btn-delete');
        
        for (var i = 0; i < deleteButtons.length; i++) {
            deleteButtons[i].addEventListener('click', function(e) {
                e.preventDefault();
                var bookTitle = 'эту книгу';
                var card = this.closest('.book-card');
                if (card) {
                    var titleElem = card.querySelector('.book-title');
                    if (titleElem) {
                        bookTitle = titleElem.textContent;
                    }
                }
                showModal(bookTitle, this.getAttribute('href'));
            });
        }
        
        // Подтверждение
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                if (currentDeleteUrl) {
                    window.location.href = currentDeleteUrl;
                }
            });
        }
        
        // Отмена
        if (cancelBtn) {
            cancelBtn.addEventListener('click', hideModal);
        }
        
        // Клик вне окна
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    hideModal();
                }
            });
        }
        
        // Клавиша ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                hideModal();
            }
        });
    })();
    </script>
</body>
</html>