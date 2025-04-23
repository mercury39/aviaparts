<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Перевірка авторизації
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Перевірка на POST запит
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../forum.php");
    exit;
}

// Отримання даних з форми
$title = cleanInput($_POST['title']);
$content = cleanInput($_POST['content']);
$userId = $_SESSION['user_id'];

// Валідація даних
if (empty($title) || empty($content)) {
    header("Location: ../create_topic.php?error=empty_fields");
    exit;
}

// Початок транзакції
$conn->begin_transaction();

try {
    // Додавання нової теми
    $sql = "INSERT INTO forum_topics (title, user_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $title, $userId);
    $stmt->execute();
    
    // Отримання ID новоствореної теми
    $topicId = $conn->insert_id;
    
    // Додавання першого повідомлення
    $sql = "INSERT INTO forum_posts (topic_id, user_id, content) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $topicId, $userId, $content);
    $stmt->execute();
    
    // Підтвердження транзакції
    $conn->commit();
    
    // Перенаправлення на сторінку теми
    header("Location: ../forum_topic.php?id=$topicId&success=topic_created");
    exit;
} catch (Exception $e) {
    // Відкат транзакції у випадку помилки
    $conn->rollback();
    header("Location: ../create_topic.php?error=db_error");
    exit;
}