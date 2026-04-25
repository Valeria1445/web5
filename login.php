<?php
session_start();
if (isset($_SESSION['application_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$login_input = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($login) || empty($password)) {
        $error = 'Заполните оба поля.';
    } else {
        function getDB() {
            static $pdo = null;
            if ($pdo === null) {
                $db_host = 'localhost';
                $db_user = 'u82358';
                $db_pass = '8445612';
                $db_name = 'u82358';
                try {
                    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (PDOException $e) {
                    die("Ошибка подключения к БД: " . $e->getMessage());
                }
            }
            return $pdo;
        }
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, login, password_hash FROM application WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['application_id'] = $user['id'];
            $_SESSION['login'] = $user['login'];
            // Очищаем cookies, чтобы не смешивались с данными авторизованного
            $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];
            foreach ($fields as $field) {
                setcookie($field . '_error', '', 1);
                setcookie($field . '_value', '', 1);
            }
            setcookie('languages_value', '', 1);
            setcookie('contract_accepted_value', '', 1);
            setcookie('save', '', 1);
            header('Location: index.php');
            exit();
        } else {
            $error = 'Неверный логин или пароль.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход – Лабораторная работа №5</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <header>
        <h1>Вход в систему</h1>
        <p class="subtitle">Введите логин и пароль, которые были выданы при первой отправке формы</p>
    </header>

    <?php if ($error): ?>
        <div class="errors"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" style="max-width: 500px;">
        <div class="form-group">
            <label>Логин</label>
            <input type="text" name="login" value="<?= htmlspecialchars($login_input) ?>" required>
        </div>
        <div class="form-group">
            <label>Пароль</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Войти</button>
    </form>

    <div class="footer-links">
        <a href="index.php">← Вернуться к анкете</a>
        <a href="v.php">📊 Просмотреть анкеты</a>
    </div>
</div>
</body>
</html>