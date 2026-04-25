<?php
session_start();

if (isset($_SESSION['user_app_id'])) {
    header('Location: index.php');
    exit();
}

$errors = [];
$login_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login'] ?? '');
    $password_input = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password_input)) {
        $errors[] = 'Заполните оба поля.';
    } else {
        // Подключение к БД
        function dbConnect() {
            static $db = null;
            if ($db === null) {
                $host = 'localhost';
                $user = 'u82358';
                $pass = '8445612';   
                $name = 'u82358';
                try {
                    $db = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass);
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (PDOException $e) {
                    die("Ошибка БД: " . $e->getMessage());
                }
            }
            return $db;
        }

        $db = dbConnect();
        $stmt = $db->prepare("SELECT id, login, password_hash FROM application WHERE login = ?");
        $stmt->execute([$login_input]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password_input, $user['password_hash'])) {
            $_SESSION['user_app_id'] = $user['id'];
            $_SESSION['user_login'] = $user['login'];
            // Удаляем все куки формы, чтобы они не мешали
            $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];
            foreach ($fields as $f) {
                setcookie($f . '_err', '', 1);
                setcookie($f . '_val', '', 1);
            }
            setcookie('languages_val', '', 1);
            setcookie('contract_accepted_val', '', 1);
            setcookie('save_success', '', 1);
            header('Location: index.php');
            exit();
        } else {
            $errors[] = 'Неверный логин или пароль.';
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
    <style>
        /* небольшие правки для формы входа */
        form {
            max-width: 500px;
            margin: 0 auto;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="form-container">
    <header>
        <h1>Вход в систему</h1>
        <p class="subtitle">Введите логин и пароль, которые были выданы при первой отправке формы</p>
    </header>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $err): ?>
                <div><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
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

    <div class="back-link">
        <a href="index.php">← Вернуться к анкете</a>
        <a href="v.php">📊 Просмотреть анкеты</a>
    </div>
</div>
</body>
</html>