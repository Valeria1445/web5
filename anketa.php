<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анкета – Лабораторная работа №5</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <header>
        <h1>Анкета</h1>
        <p class="subtitle">Заполните форму – при первой отправке будут сгенерированы логин и пароль</p>
    </header>

    <?php if ($is_logged_in): ?>
        <div class="success" style="background:#e2e6ea; color:#5f4b8b;">
            ✅ Вы авторизованы (ID: <?= htmlspecialchars($user_id) ?>)
            <a href="index.php?logout=1" style="color:#c97e5a; margin-left:15px;">Выйти</a>
        </div>
    <?php endif; ?>

    <!-- Вывод сообщений (ошибки, успех, логин/пароль) -->
    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $msg): ?>
            <?php if (strpos($msg, 'success-message') !== false || strpos($msg, 'credentials') !== false): ?>
                <div class="success"><?= $msg ?></div>
            <?php elseif (strpos($msg, 'error-message') !== false): ?>
                <div class="errors"><?= $msg ?></div>
            <?php else: ?>
                <div class="errors"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="post" action="index.php">
        <div class="form-group">
            <label for="full_name">ФИО</label>
            <input type="text" id="full_name" name="full_name"
                   value="<?= htmlspecialchars($values['full_name'] ?? '') ?>"
                   <?= !empty($errors['full_name']) ? 'class="error-field"' : '' ?>>
            <?php if (!empty($errors['full_name'])): ?>
                <span class="field-error">ФИО обязательно и должно содержать только буквы и пробелы.</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="phone">Телефон</label>
            <input type="tel" id="phone" name="phone"
                   value="<?= htmlspecialchars($values['phone'] ?? '') ?>"
                   <?= !empty($errors['phone']) ? 'class="error-field"' : '' ?>>
            <?php if (!empty($errors['phone'])): ?>
                <span class="field-error">Телефон должен содержать 6–12 цифр, разрешены +, -, (, ), пробел.</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($values['email'] ?? '') ?>"
                   <?= !empty($errors['email']) ? 'class="error-field"' : '' ?>>
            <?php if (!empty($errors['email'])): ?>
                <span class="field-error">Введите корректный email.</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="birth_date">Дата рождения</label>
            <input type="date" id="birth_date" name="birth_date"
                   value="<?= htmlspecialchars($values['birth_date'] ?? '') ?>"
                   <?= !empty($errors['birth_date']) ? 'class="error-field"' : '' ?>>
            <?php if (!empty($errors['birth_date'])): ?>
                <span class="field-error">Формат ГГГГ-ММ-ДД, дата не позже сегодняшнего дня.</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Пол</label>
            <div class="radio-group">
                <label>
                    <input type="radio" name="gender" value="male"
                        <?= ($values['gender'] ?? '') === 'male' ? 'checked' : '' ?>
                        <?= !empty($errors['gender']) ? 'class="error-field"' : '' ?>>
                    Мужской
                </label>
                <label>
                    <input type="radio" name="gender" value="female"
                        <?= ($values['gender'] ?? '') === 'female' ? 'checked' : '' ?>
                        <?= !empty($errors['gender']) ? 'class="error-field"' : '' ?>>
                    Женский
                </label>
            </div>
            <?php if (!empty($errors['gender'])): ?>
                <span class="field-error">Выберите пол.</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="languages">Любимые языки программирования (выберите один или несколько)</label>
            <select id="languages" name="languages[]" multiple size="6"
                    <?= !empty($errors['languages']) ? 'class="error-field"' : '' ?>>
                <?php foreach ($languages_from_db as $lang): ?>
                    <option value="<?= htmlspecialchars($lang) ?>" <?= in_array($lang, $values['languages'] ?? []) ? 'selected' : '' ?>><?= htmlspecialchars($lang) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['languages'])): ?>
                <span class="field-error">Выберите хотя бы один допустимый язык.</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="biography">Биография</label>
            <textarea id="biography" name="biography" rows="5"
                <?= !empty($errors['biography']) ? 'class="error-field"' : '' ?>><?= htmlspecialchars($values['biography'] ?? '') ?></textarea>
            <?php if (!empty($errors['biography'])): ?>
                <span class="field-error">Биография не должна превышать 10000 символов.</span>
            <?php endif; ?>
        </div>

        <div class="form-group checkbox">
            <label>
                <input type="checkbox" name="contract_accepted" value="1"
                    <?= !empty($values['contract_accepted']) ? 'checked' : '' ?>
                    <?= !empty($errors['contract_accepted']) ? 'class="error-field"' : '' ?>>
                Я ознакомлен(а) с контрактом
            </label>
            <?php if (!empty($errors['contract_accepted'])): ?>
                <span class="field-error">Необходимо подтвердить согласие.</span>
            <?php endif; ?>
        </div>

        <button type="submit">Сохранить</button>
    </form>

    <div class="footer-links">
        <a href="login.php">🔐 Войти (если уже есть логин/пароль)</a>
        <a href="v.php">📊 Просмотреть сохранённые анкеты</a>
    </div>
    <?php if (!$is_logged_in): ?>
        <div class="back-link" style="margin-top: 15px;">
            <small>Для редактирования данных нужна авторизация</small>
        </div>
    <?php endif; ?>
</div>
</body>
</html>