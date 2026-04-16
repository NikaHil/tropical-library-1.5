<?php
require_once 'config/database.php';
requireAuth();

$userId = getCurrentUserId();
$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = :id AND user_id = :user_id");
$stmt->execute([':id' => $id, ':user_id' => $userId]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: index.php');
    exit;
}

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
        $sql = "UPDATE books SET 
                title = :title,
                author = :author,
                genre = :genre,
                year = :year,
                status = :status,
                rating = :rating,
                review = :review
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':author' => $author,
            ':genre' => $genre,
            ':year' => $year,
            ':status' => $status,
            ':rating' => $rating,
            ':review' => $review,
            ':id' => $id,
            ':user_id' => $userId
        ]);
        
        $achResult = checkAndUpdateAchievements($pdo, $userId);
        if (!empty($achResult['new_achievements'])) {
            setNewAchievements($achResult['new_achievements']);
        }
        
        $success = 'Книга обновлена! 🌺';
        
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $book = $stmt->fetch();
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
    <title>✏️ Редактировать книгу</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="leaves-bg"></div>
    <div class="container">
        <header>
            <h1>✏️ Редактировать книгу</h1>
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
                    <input type="text" name="title" value="<?= htmlspecialchars($book['title']) ?>" required>
                </div>
                <div class="form-group">
                    <label>✍️ Автор *</label>
                    <input type="text" name="author" value="<?= htmlspecialchars($book['author']) ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>🎭 Жанр</label>
                    <select name="genre">
<option value="">🌺 Выбери жанр</option>
<?php
$genres = [
    'Классика' => '📜',
    'Фантастика' => '🚀',
    'Детектив' => '🔍',
    'Роман' => '💕',
    'Поэзия' => '🎭',
    'Научная литература' => '🔬',
    'Психология' => '🧠',
    'Биография' => '📖',
    'Приключения' => '🗺️',
    'Фэнтези' => '🐉',
    'Ужасы' => '👻',
    'Триллер' => '🔪',
    'История' => '🏛️',
    'Философия' => '💭'
];
foreach ($genres as $genreName => $icon):
?>
    <option value="<?= $genreName ?>" <?= $book['genre'] == $genreName ? 'selected' : '' ?>>
        <?= $icon ?> <?= $genreName ?>
    </option>
<?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>📅 Год издания</label>
                    <input type="number" name="year" value="<?= $book['year'] ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>📖 Статус</label>
                    <select name="status">
                        <option value="want" <?= $book['status'] == 'want' ? 'selected' : '' ?>>🥥 Хочу прочитать</option>
                        <option value="reading" <?= $book['status'] == 'reading' ? 'selected' : '' ?>>🌺 Читаю сейчас</option>
                        <option value="read" <?= $book['status'] == 'read' ? 'selected' : '' ?>>🍍 Прочитано</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>⭐ Оценка (0-10)</label>
                    <input type="number" name="rating" step="0.5" min="0" max="10" value="<?= $book['rating'] ?>">
                </div>
            </div>
            <div class="form-group">
                <label>💭 Мой отзыв / заметки</label>
                <textarea name="review" rows="4"><?= htmlspecialchars($book['review']) ?></textarea>
            </div>
            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">💾 Сохранить изменения</button>
                <a href="index.php" class="btn btn-link">Отмена</a>
            </div>
        </form>
    </div>
    <script src="js/script.js"></script>
</body>
</html>