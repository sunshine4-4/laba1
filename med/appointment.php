<?php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Получение доступных специалистов, услуг и времени
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        
        try {
            switch ($action) {
                case 'get_specialists':
                    // Получение специалистов по отделению
                    if (!isset($_GET['department'])) {
                        $response['message'] = 'Не указано отделение';
                        break;
                    }
                    
                    $department = clean_input($_GET['department']);
                    $stmt = $pdo->prepare("
                        SELECT id, CONCAT(last_name, ' ', first_name, ' ', middle_name) AS full_name, 
                               specialization, department, photo
                        FROM specialists 
                        WHERE department = ?
                        ORDER BY last_name, first_name
                    ");
                    $stmt->execute([$department]);
                    $specialists = $stmt->fetchAll();
                    
                    $response['success'] = true;
                    $response['data'] = $specialists;
                    break;
                    
                case 'get_services':
                    // Получение услуг по специалисту
                    if (!isset($_GET['specialist_id'])) {
                        $response['message'] = 'Не указан специалист';
                        break;
                    }
                    
                    $specialistId = (int)$_GET['specialist_id'];
                    $stmt = $pdo->prepare("
                        SELECT s.id, s.name, s.description, s.price, s.duration
                        FROM services s
                        JOIN specialist_services ss ON s.id = ss.service_id
                        WHERE ss.specialist_id = ?
                        ORDER BY s.name
                    ");
                    $stmt->execute([$specialistId]);
                    $services = $stmt->fetchAll();
                    
                    $response['success'] = true;
                    $response['data'] = $services;
                    break;
                    
                case 'get_available_time':
                    // Получение доступного времени для записи
                    if (!isset($_GET['specialist_id']) || !isset($_GET['date'])) {
                        $response['message'] = 'Не указаны необходимые параметры';
                        break;
                    }
                    
                    $specialistId = (int)$_GET['specialist_id'];
                    $date = clean_input($_GET['date']);
                    
                    // Проверка формата даты
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                        $response['message'] = 'Некорректный формат даты';
                        break;
                    }
                    
                    // Проверка, что дата не в прошлом
                    $today = date('Y-m-d');
                    if ($date < $today) {
                        $response['message'] = 'Нельзя записаться на прошедшую дату';
                        break;
                    }
                    
                    // Получение рабочего графика специалиста
                    $stmt = $pdo->prepare("
                        SELECT work_schedule FROM specialists WHERE id = ?
                    ");
                    $stmt->execute([$specialistId]);
                    $schedule = $stmt->fetchColumn();
                    $schedule = json_decode($schedule, true);
                    
                    if (!$schedule) {
                        $response['message'] = 'Не удалось получить график специалиста';
                        break;
                    }
                    
                    // Определение дня недели для выбранной даты
                    $dayOfWeek = date('N', strtotime($date)); // 1-7 (Понедельник-Воскресенье)
                    $dayKey = 'day' . $dayOfWeek;
                    
                    if (!isset($schedule[$dayKey]) || !$schedule[$dayKey]['working']) {
                        $response['message'] = 'Специалист не работает в выбранный день';
                        break;
                    }
                    
                    $workStart = $schedule[$dayKey]['start'];
                    $workEnd = $schedule[$dayKey]['end'];
                    $breakStart = $schedule[$dayKey]['break_start'] ?? null;
                    $breakEnd = $schedule[$dayKey]['break_end'] ?? null;
                    
                    // Получение длительности приема (берем первую услугу, предполагая одинаковую длительность)
                    $stmt = $pdo->prepare("
                        SELECT s.duration FROM services s
                        JOIN specialist_services ss ON s.id = ss.service_id
                        WHERE ss.specialist_id = ?
                        LIMIT 1
                    ");
                    $stmt->execute([$specialistId]);
                    $duration = (int)$stmt->fetchColumn();
                    
                    if (!$duration) {
                        $response['message'] = 'Не удалось определить длительность приема';
                        break;
                    }
                    
                    // Получение занятого времени
                    $stmt = $pdo->prepare("
                        SELECT time FROM appointments
                        WHERE specialist_id = ? AND date = ? AND status != 'cancelled'
                        ORDER BY time
                    ");
                    $stmt->execute([$specialistId, $date]);
                    $busyTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Генерация доступных временных слотов
                    $availableTimes = [];
                    $currentTime = strtotime($workStart);
                    $endTime = strtotime($workEnd);
                    
                    while ($currentTime + $duration * 60 <= $endTime) {
                        // Проверка на перерыв
                        $skip = false;
                        if ($breakStart && $breakEnd) {
                            $breakStartTime = strtotime($breakStart);
                            $breakEndTime = strtotime($breakEnd);
                            
                            if ($currentTime >= $breakStartTime && $currentTime < $breakEndTime) {
                                $currentTime = $breakEndTime;
                                $skip = true;
                            }
                        }
                        
                        if (!$skip) {
                            $timeSlot = date('H:i', $currentTime);
                            
                            // Проверка, не занято ли время
                            if (!in_array($timeSlot, $busyTimes)) {
                                $availableTimes[] = $timeSlot;
                            }
                            
                            $currentTime += $duration * 60; // Добавляем длительность приема
                        }
                    }
                    
                    $response['success'] = true;
                    $response['data'] = $availableTimes;
                    break;
                    
                default:
                    $response['message'] = 'Неизвестное действие';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка записи на прием
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Для записи необходимо авторизоваться';
        echo json_encode($response);
        exit;
    }
    
    $requiredFields = ['specialist_id', 'service_id', 'date', 'time', 'notes'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $response['message'] = 'Все обязательные поля должны быть заполнены';
            echo json_encode($response);
            exit;
        }
    }
    
    $userId = $_SESSION['user_id'];
    $specialistId = (int)$_POST['specialist_id'];
    $serviceId = (int)$_POST['service_id'];
    $date = clean_input($_POST['date']);
    $time = clean_input($_POST['time']);
    $notes = clean_input($_POST['notes']);
    
    // Валидация даты и времени
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $response['message'] = 'Некорректный формат даты';
        echo json_encode($response);
        exit;
    }
    
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        $response['message'] = 'Некорректный формат времени';
        echo json_encode($response);
        exit;
    }
    
    // Проверка, что дата не в прошлом
    $today = date('Y-m-d');
    if ($date < $today) {
        $response['message'] = 'Нельзя записаться на прошедшую дату';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Проверка существования специалиста и услуги
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM specialist_services
            WHERE specialist_id = ? AND service_id = ?
        ");
        $stmt->execute([$specialistId, $serviceId]);
        
        if ($stmt->fetchColumn() == 0) {
            $response['message'] = 'Выбранная услуга не предоставляется выбранным специалистом';
            echo json_encode($response);
            exit;
        }
        
        // Проверка доступности времени
        $stmt = $pdo->prepare("
            SELECT id FROM appointments
            WHERE specialist_id = ? AND date = ? AND time = ? AND status != 'cancelled'
        ");
        $stmt->execute([$specialistId, $date, $time]);
        
        if ($stmt->rowCount() > 0) {
            $response['message'] = 'Выбранное время уже занято';
            echo json_encode($response);
            exit;
        }
        
        // Получение информации об услуге для цены
        $stmt = $pdo->prepare("SELECT price, duration FROM services WHERE id = ?");
        $stmt->execute([$serviceId]);
        $service = $stmt->fetch();
        
        if (!$service) {
            $response['message'] = 'Не удалось получить информацию об услуге';
            echo json_encode($response);
            exit;
        }
        
        // Создание записи
        $stmt = $pdo->prepare("
            INSERT INTO appointments (
                user_id, specialist_id, service_id, date, time, 
                price, duration, notes, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([
            $userId, $specialistId, $serviceId, $date, $time,
            $service['price'], $service['duration'], $notes
        ]);
        
        $appointmentId = $pdo->lastInsertId();
        
        // Отправка уведомлений (можно реализовать через email или SMS)
        // ...
        
        $response['success'] = true;
        $response['message'] = 'Запись успешно создана';
        $response['appointment_id'] = $appointmentId;
    } catch (PDOException $e) {
        $response['message'] = 'Ошибка базы данных: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>