<?php
$host = 'localhost';
$dbname = 'family_medicine_center'; // Имя вашей базы данных
$username = 'fmc_user'; // Имя пользователя базы данных
$password = 'gysev_xyiv2'; // Пароль пользователя

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>