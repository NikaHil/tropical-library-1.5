<?php
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $stmt->execute([':username' => $username, ':email' => $email]);
        
        if ($stmt->fetch()) {
            $error = 'Логин или email уже заняты';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :hash)");
            $stmt->execute([':username' => $username, ':email' => $email, ':hash' => $password_hash]);
            
            $userId = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_key, achievement_name, achievement_desc, icon, required_books, required_genres) VALUES
                (:user_id, 'warmup', 'Разминка', 'Прочитать 1 книгу', '🌱', 1, NULL),
                (:user_id, 'reader', 'Чтец', 'Прочитать 5 книг', '📚', 5, NULL),
                (:user_id, 'master', 'Магистр книг', 'Прочитать 20 книг', '🎓', 20, NULL),
                (:user_id, 'legend', 'Легенда книг', 'Прочитать 50 книг', '👑', 50, NULL),
                (:user_id, 'versatile', 'Разносторонний', 'Прочитать 5+ разных жанров', '🎭', NULL, 5)");
            $stmt->execute([':user_id' => $userId]);
            
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            header('Location: index.php');
            exit;
        }
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
    <title>Регистрация - Тропическая библиотека</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .auth-container {
            max-width: 400px;
            margin: 80px auto;
            background: white;
            border-radius: 30px;
            padding: 35px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .auth-container input {
            width: 100%;
            padding: 14px;
            margin-bottom: 15px;
            border: 2px solid #ffe0b5;
            border-radius: 50px;
        }
        .auth-container button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2ECC71, #27AE60);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
        }
        .auth-link {
            margin-top: 20px;
            display: block;
            color: #FF6B35;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="leaves-bg"></div>
    <div class="auth-container">
        <h1>🌱 Регистрация</h1>
        <p>📚 Создай свой тропический уголок</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Логин" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">🌿 Зарегистрироваться</button>
        </form>
        <a href="login.php" class="auth-link">Уже есть аккаунт? Войти</a>
    </div>
</body>
</html>