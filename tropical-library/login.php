<?php
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :login OR email = :login");
    $stmt->execute([':login' => $login]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
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
    <title>Вход - Тропическая библиотека</title>
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
            background: linear-gradient(135deg, #FF6B35, #FF8C42);
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
        <h1>🌴 Тропическая библиотека</h1>
        <p>📚 Вход в систему</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="login" placeholder="Логин или Email" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">🍍 Войти</button>
        </form>
        <a href="register.php" class="auth-link">Нет аккаунта? Зарегистрироваться</a>
    </div>
</body>
</html>