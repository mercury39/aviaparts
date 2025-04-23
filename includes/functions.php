<?php
// Розпочати сесію лише якщо вона ще не активна
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Функція перевірки чи користувач заблокований
function checkIfUserBanned() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $userId = $_SESSION['user_id'];
    $sql = "SELECT status, ban_reason FROM users WHERE user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (isset($user['status']) && $user['status'] === 'banned') {
            // Зберігаємо причину бану в сесії, щоб показати її користувачу після перенаправлення
            if (isset($user['ban_reason']) && !empty($user['ban_reason'])) {
                $_SESSION['ban_reason'] = $user['ban_reason'];
            }
            
            // Очищаємо сесію
            session_unset();
            session_destroy();
            
            // Створюємо нову сесію для зберігання причини бану
            session_start();
            if (isset($user['ban_reason']) && !empty($user['ban_reason'])) {
                $_SESSION['ban_reason'] = $user['ban_reason'];
            }
            
            // Перенаправляємо на сторінку логіну з повідомленням
            header("Location: /aviaparts/login.php?error=banned");
            exit;
        }
    }
    
    return false;
}

// Функція перевірки авторизації користувача
function isLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        // Перевіряємо, чи не заблокований користувач
        checkIfUserBanned();
        return true;
    }
    return false;
}

// Функція перевірки типу користувача
function getUserType() {
    return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'guest';
}

// Функція для перевірки чи користувач є адміністратором
function isAdmin() {
    return getUserType() === 'admin';
}

// Функція для перевірки чи користувач є редактором
function isEditor() {
    return getUserType() === 'editor';
}

// Функція для перевірки прав доступу
function checkAccess($requiredType) {
    if (!isLoggedIn()) {
        header("Location: /aviaparts/login.php");
        exit;
    }
    
    $userType = getUserType();
    
    // Перевірка прав доступу
    $accessLevels = [
        'admin' => 3,
        'editor' => 2,
        'user' => 1,
        'guest' => 0
    ];
    
    if ($accessLevels[$userType] < $accessLevels[$requiredType]) {
        header("Location: /aviaparts/index.php?error=access_denied");
        exit;
    }
    
    return true;
}

// Функція для очищення вхідних даних
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Функція для генерації повідомлення про помилку або успіх
function showMessage($type, $message) {
    return "<div class='alert alert-{$type}'>{$message}</div>";
}

// Функція для отримання інформації про поточного користувача
function getCurrentUser() {
    global $conn;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $userId = $_SESSION['user_id'];
    $sql = "SELECT user_id, username, email, user_type, created_at FROM users WHERE user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Функція для отримання всіх категорій
function getAllCategories() {
    global $conn;
    
    $sql = "SELECT * FROM categories ORDER BY name";
    $result = $conn->query($sql);
    
    $categories = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

// Функція для отримання комплектуючих за категорією
function getPartsByCategory($categoryId) {
    global $conn;
    
    $sql = "SELECT * FROM parts WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $parts = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $parts[] = $row;
        }
    }
    
    return $parts;
}

// Функція для отримання деталі за ID
function getPartById($partId) {
    global $conn;
    
    $sql = "SELECT p.*, c.name as category_name FROM parts p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.part_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $partId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Отримує подібні комплектуючі з тієї ж категорії, але виключає поточну комплектуючу
 * 
 * @param int $categoryId ID категорії
 * @param int $currentPartId ID поточної комплектуючої, яку потрібно виключити
 * @param int $limit Кількість результатів, які потрібно повернути
 * @return array Масив подібних комплектуючих
 */
function getSimilarParts($categoryId, $currentPartId, $limit = 3) {
    global $conn;
    
    $sql = "SELECT part_id, name, image 
            FROM parts 
            WHERE category_id = ? AND part_id != ? 
            ORDER BY RAND() 
            LIMIT ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $categoryId, $currentPartId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $similarParts = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $similarParts[] = $row;
        }
    }
    
    return $similarParts;
}

/**
 * Повертає клас бейджа для відображення статусу комплектуючої
 * 
 * @param string $status Статус комплектуючої
 * @return string CSS клас для бейджа
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'available':
            return 'bg-success';
        case 'reserved':
            return 'bg-warning';
        case 'sold':
            return 'bg-danger';
        case 'repair':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

/**
 * Повертає текстову мітку для статусу комплектуючої
 * 
 * @param string $status Статус комплектуючої
 * @return string Текстова мітка статусу
 */
function getStatusLabel($status) {
    switch ($status) {
        case 'available':
            return 'В наявності';
        case 'reserved':
            return 'Зарезервовано';
        case 'sold':
            return 'Продано';
        case 'repair':
            return 'На ремонті';
        default:
            return 'Не визначено';
    }
}