<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// Функция подключения к БД
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

$allowed_languages = [
    'Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python',
    'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'
];
$allowed_genders = ['male', 'female'];

// Определяем, авторизован ли пользователь
$is_logged_in = isset($_SESSION['application_id']);
$user_id = $is_logged_in ? $_SESSION['application_id'] : null;

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// ====================== GET-ЗАПРОС ======================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $messages = [];
    $errors = [];
    $values = [];
    $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];

    if (!$is_logged_in) {
        // Неавторизованный – читаем cookies (как в лабе 4)
        foreach ($fields as $field) {
            $errors[$field] = !empty($_COOKIE[$field . '_error']);
        }
        // Сообщения об ошибках (только текст)
        if ($errors['full_name']) $messages[] = 'ФИО должно содержать только буквы и пробелы (макс. 150 символов).';
        if ($errors['phone']) $messages[] = 'Телефон должен содержать от 6 до 12 цифр, допускаются символы +, -, (, ), пробел.';
        if ($errors['email']) $messages[] = 'Введите корректный email.';
        if ($errors['birth_date']) $messages[] = 'Дата рождения должна быть в формате ГГГГ-ММ-ДД и не позже сегодняшнего дня.';
        if ($errors['gender']) $messages[] = 'Выберите пол.';
        if ($errors['biography']) $messages[] = 'Биография не должна превышать 10000 символов.';
        if ($errors['contract_accepted']) $messages[] = 'Необходимо подтвердить согласие.';
        if ($errors['languages']) $messages[] = 'Выберите хотя бы один язык программирования из списка.';

        // Значения из cookies
        foreach ($fields as $field) {
            $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : $_COOKIE[$field . '_value'];
        }
        if (!empty($_COOKIE['languages_value'])) {
            $values['languages'] = explode(',', $_COOKIE['languages_value']);
        } else {
            $values['languages'] = [];
        }
        $values['contract_accepted'] = !empty($_COOKIE['contract_accepted_value']) ? true : false;

        // Сообщение об успешном сохранении (для новой анкеты)
        if (!empty($_COOKIE['save'])) {
            setcookie('save', '', 1);
            $messages[] = '<div class="success-message">Данные успешно сохранены!</div>';
        }
        // Показ логина/пароля после первой отправки
        if (!empty($_COOKIE['generated_login']) && !empty($_COOKIE['generated_password'])) {
            $login = $_COOKIE['generated_login'];
            $pass = $_COOKIE['generated_password'];
            setcookie('generated_login', '', 1);
            setcookie('generated_password', '', 1);
            $messages[] = '<div class="credentials"><strong>Форма успешно отправлена!</strong><br>Ваш логин: <strong>' . htmlspecialchars($login) . '</strong><br>Ваш пароль: <strong>' . htmlspecialchars($pass) . '</strong><br><small>Сохраните их! Они больше никогда не будут показаны.</small></div>';
        }
    } else {
        // Авторизованный – загружаем данные из БД, игнорируем cookies
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM application WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $values['full_name'] = $user['full_name'];
            $values['phone'] = $user['phone'];
            $values['email'] = $user['email'];
            $values['birth_date'] = $user['birth_date'];
            $values['gender'] = $user['gender'];
            $values['biography'] = $user['biography'];
            $values['contract_accepted'] = (bool)$user['contract_accepted'];

            // Языки
            $lang_stmt = $pdo->prepare("
                SELECT l.name FROM application_language al 
                JOIN language l ON al.language_id = l.id 
                WHERE al.application_id = ?
            ");
            $lang_stmt->execute([$user_id]);
            $values['languages'] = $lang_stmt->fetchAll(PDO::FETCH_COLUMN);
            $messages[] = '<div class="success">Вы вошли как ' . htmlspecialchars($_SESSION['login']) . '. Можете редактировать свои данные.</div>';
        } else {
            // Ошибка: пользователь не найден – выходим
            session_destroy();
            header('Location: login.php');
            exit();
        }
    }

    // Получаем список языков для выпадающего списка
    $pdo = getDB();
    $languages_from_db = $pdo->query("SELECT name FROM language ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($languages_from_db)) {
        $languages_from_db = $allowed_languages;
    }

    include 'anketa.php';
    exit();
}

// ====================== POST-ЗАПРОС ======================
else {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;
    $languages = $_POST['languages'] ?? [];

    $errors = false;

    // Валидация (единая для всех)
    if (empty($full_name) || !preg_match('/^[а-яА-Яa-zA-Z\s]+$/u', $full_name) || strlen($full_name) > 150) {
        setcookie('full_name_error', '1', time() + 24*3600);
        $errors = true;
    }
    setcookie('full_name_value', $full_name, time() + 30*24*3600);

    if (empty($phone) || !preg_match('/^[\d\s\-\+\(\)]{6,12}$/', $phone)) {
        setcookie('phone_error', '1', time() + 24*3600);
        $errors = true;
    }
    setcookie('phone_value', $phone, time() + 30*24*3600);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 24*3600);
        $errors = true;
    }
    setcookie('email_value', $email, time() + 30*24*3600);

    if (empty($birth_date)) {
        setcookie('birth_date_error', '1', time() + 24*3600);
        $errors = true;
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$date || $date->format('Y-m-d') !== $birth_date || $date > new DateTime('today')) {
            setcookie('birth_date_error', '1', time() + 24*3600);
            $errors = true;
        }
    }
    setcookie('birth_date_value', $birth_date, time() + 30*24*3600);

    if (empty($gender) || !in_array($gender, $allowed_genders)) {
        setcookie('gender_error', '1', time() + 24*3600);
        $errors = true;
    }
    setcookie('gender_value', $gender, time() + 30*24*3600);

    if (strlen($biography) > 10000) {
        setcookie('biography_error', '1', time() + 24*3600);
        $errors = true;
    }
    setcookie('biography_value', $biography, time() + 30*24*3600);

    if (!$contract_accepted) {
        setcookie('contract_accepted_error', '1', time() + 24*3600);
        $errors = true;
    }
    setcookie('contract_accepted_value', $contract_accepted ? '1' : '0', time() + 30*24*3600);

    if (empty($languages)) {
        setcookie('languages_error', '1', time() + 24*3600);
        $errors = true;
    } else {
        foreach ($languages as $lang) {
            if (!in_array($lang, $allowed_languages)) {
                setcookie('languages_error', '1', time() + 24*3600);
                $errors = true;
                break;
            }
        }
    }
    setcookie('languages_value', implode(',', $languages), time() + 30*24*3600);

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    // ---- Сохранение в БД ----
    try {
        $pdo = getDB();
        $pdo->beginTransaction();

        $is_logged_in = isset($_SESSION['application_id']);

        if ($is_logged_in) {
            // Обновление существующей записи
            $stmt = $pdo->prepare("
                UPDATE application SET
                    full_name = ?, phone = ?, email = ?, birth_date = ?,
                    gender = ?, biography = ?, contract_accepted = ?
                WHERE id = ?
            ");
            $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, $contract_accepted, $_SESSION['application_id']]);
            $application_id = $_SESSION['application_id'];

            // Удаляем старые языки
            $pdo->prepare("DELETE FROM application_language WHERE application_id = ?")->execute([$application_id]);

            setcookie('updated', '1', time() + 24*3600);
        } else {
            // Новая запись – генерируем логин и пароль
            $login = 'user_' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
            while (true) {
                $stmt = $pdo->prepare("SELECT id FROM application WHERE login = ?");
                $stmt->execute([$login]);
                if (!$stmt->fetch()) break;
                $login = 'user_' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
            }
            $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*'), 0, 8);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO application 
                (full_name, phone, email, birth_date, gender, biography, contract_accepted, login, password_hash)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, $contract_accepted, $login, $password_hash]);
            $application_id = $pdo->lastInsertId();

            setcookie('generated_login', $login, time() + 3600);
            setcookie('generated_password', $password, time() + 3600);
            setcookie('save', '1', time() + 24*3600);
        }

        // Вставка языков (для обоих случаев)
        $lang_map = [];
        $stmt = $pdo->query("SELECT id, name FROM language");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lang_map[$row['name']] = $row['id'];
        }
        $stmt = $pdo->prepare("INSERT INTO application_language (application_id, language_id) VALUES (?, ?)");
        foreach ($languages as $lang_name) {
            if (isset($lang_map[$lang_name])) {
                $stmt->execute([$application_id, $lang_map[$lang_name]]);
            }
        }

        $pdo->commit();

        // Удаляем куки ошибок
        $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];
        foreach ($fields as $field) {
            setcookie($field . '_error', '', 1);
        }

        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        setcookie('db_error', '1', time() + 24*3600);
        header('Location: index.php');
        exit();
    }
}