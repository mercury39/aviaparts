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
$topicId = (int)$_POST['topic_id'];
$content = cleanInput($_POST['content']);
$userId = $_SESSION['user_id'];

// Валідація даних
if (empty($content)) {
    header("Location: ../forum_topic.php?id=$topicId&error=empty_content");
    exit;
}

// Перевірка існування теми
$sql = "SELECT * FROM forum_topics WHERE topic_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $topicId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../forum.php");
    exit;
}

// Додавання нового повідомлення
$sql = "INSERT INTO forum_posts (topic_id, user_id, content) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $topicId, $userId, $content);

if ($stmt->execute()) {
    header("Location: ../forum_topic.php?id=$topicId&success=post_added#post-" . $conn->insert_id);
    exit;
} else {
    header("Location: ../forum_topic.php?id=$topicId&error=db_error");
    exit;
}