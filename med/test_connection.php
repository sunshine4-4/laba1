<?php
header('Content-Type: text/plain');
try {
    $pdo = new PDO('mysql:host=localhost;dbname=family_medicine_center', 'fmc_user', 'ваш_пароль', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    echo "✅ Подключение успешно!\n";
    
    $stmt = $pdo->query("SELECT email FROM users LIMIT 1");
    echo "✅ Тестовый запрос выполнен. Первый email: " . $stmt->fetchColumn();
} catch (PDOException $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Проверьте:\n";
    echo "1. Сервер MySQL запущен\n";
    echo "2. Логин/пароль в скрипте совпадает с config/db.php\n";
    echo "3. Пользователь fmc_user имеет права на БД\n";
}