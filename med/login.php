<?php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

session_start();

$response = ['success' => false, 'message' => '', 'redirect' => ''];

error_log("Login attempt: " . $email); // Логи в error_log
if (!$user) {
    error_log("User not found: " . $email);
}

error_log("Input password: " . $password);
error_log("Stored hash: " . $user['password']);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) && $_POST['remember'] === '1';
    
    // Валидация данных
    if (empty($email) || empty($password)) {
        $response['message'] = 'Все поля обязательны для заполнения';
        echo json_encode($response);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Некорректный формат email';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Поиск пользователя в базе данных
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $response['message'] = 'Пользователь с таким email не найден';
            echo json_encode($response);
            exit;
        }
        
        // Проверка пароля
        if (!password_verify($password, $user['password'])) {
            $response['message'] = 'Неверный пароль';
        }
        
        // Проверка, подтвержден ли email (если требуется)
        if (isset($user['email_verified']) && !$user['email_verified']) {
            $response['message'] = 'Пожалуйста, подтвердите ваш email';
            echo json_encode($response);
            exit;
        }
        
        // Успешная авторизация
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'] ?? 'patient';
        
        // Запомнить пользователя (если выбрано)
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Сохраняем токен в базе данных
            $stmt = $pdo->prepare("
                INSERT INTO remember_tokens (user_id, token, expires_at) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user['id'], $token, $expires]);
            
            // Устанавливаем cookie
            setcookie('remember_token', $token, [
                'expires' => strtotime('+30 days'),
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        
        // Определяем куда перенаправлять пользователя
        $redirect = '../index.html';
        if ($_SESSION['user_role'] === 'admin') {
            $redirect = 'admin/dashboard.html';
        } elseif ($_SESSION['user_role'] === 'doctor') {
            $redirect = 'doctor/appointments.html';
        }
        
        $response['success'] = true;
        $response['message'] = 'Успешный вход';
        $response['redirect'] = $redirect;
        
    } catch (PDOException $e) {
        $response['message'] = 'Ошибка базы данных: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>

