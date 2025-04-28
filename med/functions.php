<?php
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Генерация CSRF токена
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Проверка CSRF токена
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Проверка аутентификации пользователя
function is_authenticated() {
    return isset($_SESSION['user_id']);
}

// Получение информации о текущем пользователе
function get_current_application_user() {
    global $pdo;
    
    if (!is_authenticated()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, email, phone 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function sendConfirmationEmail($to, $name, $confirmationLink) {
    $subject = 'Подтверждение регистрации в Центре Семейной Медицины';
    
    $message = "
        <html>
        <head>
            <title>Подтверждение регистрации</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .button {
                    display: inline-block; padding: 10px 20px; 
                    background-color: #2a7fba; color: white; 
                    text-decoration: none; border-radius: 5px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Здравствуйте, $name!</h2>
                <p>Благодарим вас за регистрацию в Центре Семейной Медицины.</p>
                <p>Для завершения регистрации, пожалуйста, подтвердите ваш email:</p>
                <p><a href='$confirmationLink' class='button'>Подтвердить Email</a></p>
                <p>Если вы не регистрировались у нас, пожалуйста, проигнорируйте это письмо.</p>
                <p>С уважением,<br>Команда Центра Семейной Медицины</p>
            </div>
        </body>
        </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: Центр Семейной Медицины <no-reply@familymed.ru>\r\n";
    
    return mail($to, $subject, $message, $headers);
}

?>