<?php
require_once 'config/database.php';
requireAuth();

$userId = getCurrentUserId();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $genre = $_POST['genre'] ?? '';
    $year = $_POST['year'] ?? null;
    $status = $_POST['status'] ?? 'want';
    $rating = $_POST['rating'] ?? null;
    $review = $_POST['review'] ?? '';
    
    if (empty($title) || empty($author)) {
        $error = 'Название и автор — обязательны!';
    } else {
        $sql = "INSERT INTO books (user_id, title, author, genre, year, status, rating, review) 
                VALUES (:user_id, :title, :author, :genre, :year, :status, :rating, :review)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':title' => $title,
            ':author' => $author,
            ':genre' => $genre,
            ':year' => $year,
            ':status' => $status,
            ':rating' => $rating,
            ':review' => $review
        ]);
        
        $achResult = checkAndUpdateAchievements($pdo, $userId);
        if (!empty($achResult['new_achievements'])) {
            setNewAchievements($achResult['new_achievements']);
        }
        
        $success = 'Книга добавлена! 🍍';
        header("refresh:2;url=index.php");
    }
}
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
    <title>🍍 Добавить книгу</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="leaves-bg"></div>
    <div class="container">
        <header>
            <h1>🍍 Добавить новую книгу</h1>
            <a href="index.php" class="btn btn-secondary">← На главную</a>
        </header>
        
        <?php if ($error): ?>
            <div class="alert alert-error">🌊 <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">🌺 <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="book-form">
            <div class="form-row">
                <div class="form-group">
                    <label>🌿 Название книги *</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>✍️ Автор *</label>
                    <input type="text" name="author" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>🎭 Жанр</label>
                    <select name="genre">
                        <option value="">🌺 Выбери жанр</option>
                        <option>Классика 📜</option><option>Фантастика 🚀</option><option>Детектив 🔍</option>
                        <option>Роман 💕</option><option>Поэзия 🎭</option><option>Психология 🧠</option>
                        <option>Приключения 🗺️</option><option>Фэнтези 🐉</option><option>Ужасы 👻</option>
                        <option>Триллер 🔪</option><option>История 🏛️</option><option>Философия 💭</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>📅 Год издания</label>
                    <input type="number" name="year" min="1000" max="2026" placeholder="2024">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>📖 Статус</label>
                    <select name="status">
                        <option value="want">🥥 Хочу прочитать</option>
                        <option value="reading">🌺 Читаю сейчас</option>
                        <option value="read">🍍 Прочитано</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>⭐ Оценка (0-10)</label>
                    <input type="number" name="rating" step="0.5" min="0" max="10" placeholder="8.5">
                </div>
            </div>
            <div class="form-group">
                <label>💭 Мой отзыв / заметки</label>
                <textarea name="review" rows="4" placeholder="Что понравилось? Что запомнилось?"></textarea>
            </div>
            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">🍍 Сохранить книгу</button>
                <a href="index.php" class="btn btn-link">Отмена</a>
            </div>
        </form>
    </div>
    <script src="js/script.js"></script>
</body>
</html>