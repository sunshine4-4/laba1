<?php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

$response = [
    'success' => false, 
    'message' => '',
    'errors' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Очистка и получение данных из формы
    $firstName = clean_input($_POST['firstName'] ?? '');
    $lastName = clean_input($_POST['lastName'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $agree = isset($_POST['agree']) && $_POST['agree'] === 'on';

    // Валидация данных
    if (empty($firstName)) {
        $response['errors']['firstName'] = 'Поле "Имя" обязательно для заполнения';
    } elseif (strlen($firstName) < 2) {
        $response['errors']['firstName'] = 'Имя должно содержать минимум 2 символа';
    }

    if (empty($lastName)) {
        $response['errors']['lastName'] = 'Поле "Фамилия" обязательно для заполнения';
    } elseif (strlen($lastName) < 2) {
        $response['errors']['lastName'] = 'Фамилия должна содержать минимум 2 символа';
    }

    if (empty($email)) {
        $response['errors']['email'] = 'Поле "Email" обязательно для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = 'Некорректный формат email';
    }

    if (empty($phone)) {
        $response['errors']['phone'] = 'Поле "Телефон" обязательно для заполнения';
    } elseif (!preg_match('/^\+?[0-9\s\-\(\)]{10,20}$/', $phone)) {
        $response['errors']['phone'] = 'Некорректный формат телефона';
    }

    if (empty($password)) {
        $response['errors']['password'] = 'Поле "Пароль" обязательно для заполнения';
    } elseif (strlen($password) < 8) {
        $response['errors']['password'] = 'Пароль должен содержать минимум 8 символов';
    } elseif (!preg_match('/[A-ZА-Я]/', $password)) {
        $response['errors']['password'] = 'Пароль должен содержать хотя бы одну заглавную букву';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $response['errors']['password'] = 'Пароль должен содержать хотя бы одну цифру';
    }

    if ($password !== $confirmPassword) {
        $response['errors']['confirmPassword'] = 'Пароли не совпадают';
    }

    if (!$agree) {
        $response['errors']['agree'] = 'Необходимо согласие с обработкой персональных данных';
    }

    // Если есть ошибки - возвращаем их
    if (!empty($response['errors'])) {
        $response['message'] = 'Пожалуйста, исправьте ошибки в форме';
        echo json_encode($response);
        exit;
    }

    try {
        // Проверка уникальности email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $response['errors']['email'] = 'Пользователь с таким email уже зарегистрирован';
            $response['message'] = 'Пользователь с таким email уже зарегистрирован';
            echo json_encode($response);
            exit;
        }

        // Проверка уникальности телефона
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        
        if ($stmt->rowCount() > 0) {
            $response['errors']['phone'] = 'Пользователь с таким телефоном уже зарегистрирован';
            $response['message'] = 'Пользователь с таким телефоном уже зарегистрирован';
            echo json_encode($response);
            exit;
        }

        // Хеширование пароля
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Генерация токена подтверждения email
        $emailToken = bin2hex(random_bytes(32));
        $emailTokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Создание пользователя в базе данных
        $stmt = $pdo->prepare("
            INSERT INTO users 
            (first_name, last_name, email, phone, password, email_token, email_token_expires, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $firstName,
            $lastName,
            $email,
            $phone,
            $hashedPassword,
            $emailToken,
            $emailTokenExpires
        ]);

        // Получение ID нового пользователя
        $userId = $pdo->lastInsertId();

        // Отправка email с подтверждением (в реальном проекте)
        $confirmLink = "https://ваш-сайт.ru/php/confirm-email.php?token=$emailToken";
        // sendConfirmationEmail($email, $firstName, $confirmLink);

        // Успешная регистрация
        $response['success'] = true;
        $response['message'] = 'Регистрация прошла успешно! На ваш email отправлено письмо с подтверждением.';
        
        // Автоматический вход после регистрации (опционально)
        session_start();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'patient';

    } catch (PDOException $e) {
        error_log('Registration error: ' . $e->getMessage());
        $response['message'] = 'Произошла ошибка при регистрации. Пожалуйста, попробуйте позже.';
    }
} else {
    $response['message'] = 'Некорректный метод запроса';
}

echo json_encode($response);
?>

