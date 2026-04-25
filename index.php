<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// Определяем авторизацию
$user_is_logged_in = isset($_SESSION['user_app_id']);
$current_user_id = $user_is_logged_in ? $_SESSION['user_app_id'] : null;

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

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

// Генерация уникального логина
function createUniqueLogin($db) {
    do {
        $login = 'user_' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
        $stmt = $db->prepare("SELECT id FROM application WHERE login = ?");
        $stmt->execute([$login]);
    } while ($stmt->fetch());
    return $login;
}

// Генерация пароля
function createRandomPassword($len = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $len);
}

// Белые списки
$valid_languages = [
    'Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python',
    'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'
];
$valid_genders = ['male', 'female'];

// ====================== GET ======================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $msg_list = [];
    $field_errors = [];
    $field_values = [];

    $all_fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];

    if (!$user_is_logged_in) {
        // Неавторизованный – читаем cookies
        foreach ($all_fields as $f) {
            $field_errors[$f] = !empty($_COOKIE[$f . '_err']);
        }
        if ($field_errors['full_name']) $msg_list[] = 'ФИО должно содержать только буквы и пробелы (макс. 150 символов).';
        if ($field_errors['phone']) $msg_list[] = 'Телефон должен содержать от 6 до 12 цифр, допускаются +, -, (, ), пробел.';
        if ($field_errors['email']) $msg_list[] = 'Введите корректный email.';
        if ($field_errors['birth_date']) $msg_list[] = 'Дата рождения: формат ГГГГ-ММ-ДД, не позже сегодня.';
        if ($field_errors['gender']) $msg_list[] = 'Выберите пол.';
        if ($field_errors['biography']) $msg_list[] = 'Биография не более 10000 символов.';
        if ($field_errors['contract_accepted']) $msg_list[] = 'Необходимо подтвердить согласие.';
        if ($field_errors['languages']) $msg_list[] = 'Выберите хотя бы один язык программирования.';

        foreach ($all_fields as $f) {
            $field_values[$f] = empty($_COOKIE[$f . '_val']) ? '' : $_COOKIE[$f . '_val'];
        }
        if (!empty($_COOKIE['languages_val'])) {
            $field_values['languages'] = explode(',', $_COOKIE['languages_val']);
        } else {
            $field_values['languages'] = [];
        }
        $field_values['contract_accepted'] = !empty($_COOKIE['contract_accepted_val']);

        // Сообщение об успешном сохранении новой анкеты
        if (!empty($_COOKIE['save_success'])) {
            setcookie('save_success', '', 1);
            $msg_list[] = '<div class="success">Данные успешно сохранены!</div>';
        }
        // Показ сгенерированных логина/пароля
        if (!empty($_COOKIE['tmp_login']) && !empty($_COOKIE['tmp_pass'])) {
            $tmp_login = $_COOKIE['tmp_login'];
            $tmp_pass = $_COOKIE['tmp_pass'];
            setcookie('tmp_login', '', 1);
            setcookie('tmp_pass', '', 1);
            $msg_list[] = '<div class="credentials">
                <strong>Форма успешно отправлена!</strong><br>
                Ваш логин: <strong>' . htmlspecialchars($tmp_login) . '</strong><br>
                Ваш пароль: <strong>' . htmlspecialchars($tmp_pass) . '</strong><br>
                <small>Сохраните их! Они больше никогда не будут показаны.</small>
            </div>';
        }
    } else {
        // Авторизованный – загружаем данные из БД
        $db = dbConnect();
        $stmt = $db->prepare("SELECT * FROM application WHERE id = ?");
        $stmt->execute([$current_user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $field_values['full_name'] = $user['full_name'];
            $field_values['phone'] = $user['phone'];
            $field_values['email'] = $user['email'];
            $field_values['birth_date'] = $user['birth_date'];
            $field_values['gender'] = $user['gender'];
            $field_values['biography'] = $user['biography'];
            $field_values['contract_accepted'] = (bool)$user['contract_accepted'];

            $lang_stmt = $db->prepare("
                SELECT l.name FROM application_language al
                JOIN language l ON al.language_id = l.id
                WHERE al.application_id = ?
            ");
            $lang_stmt->execute([$current_user_id]);
            $field_values['languages'] = $lang_stmt->fetchAll(PDO::FETCH_COLUMN);
            $msg_list[] = '<div class="success">Вы вошли как ' . htmlspecialchars($_SESSION['user_login']) . '. Вы можете редактировать свои данные.</div>';
        } else {
            session_destroy();
            header('Location: login.php');
            exit();
        }
    }

    // Список языков для select
    $db = dbConnect();
    $language_options = $db->query("SELECT name FROM language ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($language_options)) $language_options = $valid_languages;

    include 'anketa.php';
    exit();
}

// ====================== POST ======================
else {
    $has_error = false;

    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;
    $languages = $_POST['languages'] ?? [];

    // Валидация
    if (empty($full_name) || !preg_match('/^[а-яА-Яa-zA-Z\s]+$/u', $full_name) || strlen($full_name) > 150) {
        setcookie('full_name_err', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('full_name_val', $full_name, time() + 2592000);

    if (empty($phone) || !preg_match('/^[\d\s\-\+\(\)]{6,12}$/', $phone)) {
        setcookie('phone_err', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('phone_val', $phone, time() + 2592000);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setcookie('email_err', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('email_val', $email, time() + 2592000);

    if (empty($birth_date)) {
        setcookie('birth_date_err', '1', time() + 86400);
        $has_error = true;
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$date || $date->format('Y-m-d') !== $birth_date || $date > new DateTime('today')) {
            setcookie('birth_date_err', '1', time() + 86400);
            $has_error = true;
        }
    }
    setcookie('birth_date_val', $birth_date, time() + 2592000);

    if (empty($gender) || !in_array($gender, $valid_genders)) {
        setcookie('gender_err', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('gender_val', $gender, time() + 2592000);

    if (strlen($biography) > 10000) {
        setcookie('biography_err', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('biography_val', $biography, time() + 2592000);

    if (!$contract_accepted) {
        setcookie('contract_accepted_err', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('contract_accepted_val', $contract_accepted ? '1' : '0', time() + 2592000);

    if (empty($languages)) {
        setcookie('languages_err', '1', time() + 86400);
        $has_error = true;
    } else {
        foreach ($languages as $lang) {
            if (!in_array($lang, $valid_languages)) {
                setcookie('languages_err', '1', time() + 86400);
                $has_error = true;
                break;
            }
        }
    }
    setcookie('languages_val', implode(',', $languages), time() + 2592000);

    if ($has_error) {
        header('Location: index.php');
        exit();
    }

    // Сохранение в БД
    try {
        $db = dbConnect();
        $db->beginTransaction();

        if ($user_is_logged_in) {
            // Обновление
            $stmt = $db->prepare("
                UPDATE application 
                SET full_name = :fn, phone = :ph, email = :em, birth_date = :bd,
                    gender = :gd, biography = :bio, contract_accepted = :ca
                WHERE id = :id
            ");
            $stmt->execute([
                ':fn' => $full_name, ':ph' => $phone, ':em' => $email, ':bd' => $birth_date,
                ':gd' => $gender, ':bio' => $biography, ':ca' => $contract_accepted, ':id' => $current_user_id
            ]);
            $app_id = $current_user_id;
            $db->prepare("DELETE FROM application_language WHERE application_id = ?")->execute([$app_id]);
            setcookie('updated_flag', '1', time() + 86400);
        } else {
            // Новая анкета
            $login = createUniqueLogin($db);
            $plain_pass = createRandomPassword();
            $pass_hash = password_hash($plain_pass, PASSWORD_DEFAULT);

            $stmt = $db->prepare("
                INSERT INTO application 
                (full_name, phone, email, birth_date, gender, biography, contract_accepted, login, password_hash)
                VALUES (:fn, :ph, :em, :bd, :gd, :bio, :ca, :lg, :phash)
            ");
            $stmt->execute([
                ':fn' => $full_name, ':ph' => $phone, ':em' => $email, ':bd' => $birth_date,
                ':gd' => $gender, ':bio' => $biography, ':ca' => $contract_accepted,
                ':lg' => $login, ':phash' => $pass_hash
            ]);
            $app_id = $db->lastInsertId();

            setcookie('tmp_login', $login, time() + 3600);
            setcookie('tmp_pass', $plain_pass, time() + 3600);
            setcookie('save_success', '1', time() + 86400);
        }

        // Сохраняем языки
        $lang_id_map = [];
        $stmt = $db->query("SELECT id, name FROM language");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lang_id_map[$row['name']] = $row['id'];
        }
        $ins = $db->prepare("INSERT INTO application_language (application_id, language_id) VALUES (?, ?)");
        foreach ($languages as $lang_name) {
            if (isset($lang_id_map[$lang_name])) {
                $ins->execute([$app_id, $lang_id_map[$lang_name]]);
            }
        }

        $db->commit();

        // Удаляем куки ошибок
        $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];
        foreach ($fields as $f) {
            setcookie($f . '_err', '', 1);
        }

        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        setcookie('db_error', '1', time() + 86400);
        header('Location: index.php');
        exit();
    }
}