<?php
// Настройки подключения к БД
$db_user = 'u82358';
$db_pass = '8445612';       
$db_name = 'u82358';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("
        SELECT a.*, GROUP_CONCAT(l.name SEPARATOR ', ') AS languages
        FROM application a
        LEFT JOIN application_language al ON a.id = al.application_id
        LEFT JOIN language l ON al.language_id = l.id
        GROUP BY a.id
        ORDER BY a.id DESC
    ");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Ошибка: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сохранённые анкеты – Лабораторная работа №3</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fefcf9;
            border-radius: 20px;
            overflow: hidden;
        }
        th, td {
            border: 1px solid #e0d6f0;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #e8dfd9;
            color: #5f4b8b;
        }
        tr:hover {
            background: #f6f3fc;
        }
        .back-link {
            text-align: center;
            margin-top: 25px;
        }
        .back-link a {
            background: #e8dfd9;
            color: #5f4b8b;
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link a:hover {
            background: #d9cfe8;
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>Сохранённые анкеты</h1>
        <p class="subtitle">Всего записей: <?= count($applications) ?></p>
    </header>

    <div class="task" style="margin: 20px;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Дата рождения</th>
                    <th>Пол</th>
                    <th>Биография</th>
                    <th>Согласие</th>
                    <th>Языки</th>
                    <th>Дата создания</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?= htmlspecialchars($app['id']) ?></td>
                    <td><?= htmlspecialchars($app['full_name']) ?></td>
                    <td><?= htmlspecialchars($app['phone']) ?></td>
                    <td><?= htmlspecialchars($app['email']) ?></td>
                    <td><?= htmlspecialchars($app['birth_date']) ?></td>
                    <td><?= $app['gender'] === 'male' ? 'Мужской' : 'Женский' ?></td>
                    <td><?= nl2br(htmlspecialchars($app['biography'])) ?></td>
                    <td><?= $app['contract_accepted'] ? 'Да' : 'Нет' ?></td>
                    <td><?= htmlspecialchars($app['languages']) ?></td>
                    <td><?= htmlspecialchars($app['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="back-link">
            <a href="index.php">← Вернуться к форме</a>
        </div>
    </div>
</div>
</body>
</html>