<?php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email']);
    
    // Валидация email
    if (empty($email)) {
        $response['message'] = 'Пожалуйста, введите email';
        echo json_encode($response);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Некорректный формат email';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Проверяем, есть ли пользователь с таким email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 0) {
            $response['message'] = 'Пользователь с таким email не найден';
            echo json_encode($response);
            exit;
        }
        
        // Генерируем токен для сброса пароля
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Токен действует 1 час
        
        // Сохраняем токен в базе данных
        $stmt = $pdo->prepare("
            INSERT INTO password_resets (email, token, expires_at) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
        ");
        $stmt->execute([$email, $token, $expires, $token, $expires]);
        
        // Отправляем письмо с инструкциями (в реальном приложении)
        $resetLink = "https://ваш-сайт.ru/pages/reset-password.html?token=$token";
        
        // Здесь должен быть код для отправки email
        // mail($email, 'Восстановление пароля', "Для сброса пароля перейдите по ссылке: $resetLink");
        
        // Для примера просто логируем ссылку
        error_log("Password reset link for $email: $resetLink");
        
        $response['success'] = true;
        $response['message'] = 'Инструкции по восстановлению пароля отправлены на ваш email';
    } catch (PDOException $e) {
        $response['message'] = 'Ошибка базы данных: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>