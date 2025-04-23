<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Перевірка авторизації
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Перевірка наявності ID повідомлення
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../forum.php");
    exit;
}

$postId = (int)$_GET['id'];

// Отримання даних повідомлення
$sql = "SELECT p.*, t.topic_id FROM forum_posts p 
        JOIN forum_topics t ON p.topic_id = t.topic_id 
        WHERE p.post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../forum.php");
    exit;
}

$post = $result->fetch_assoc();
$topicId = $post['topic_id'];

// Перевірка прав на видалення (автор або адміністратор)
if ($_SESSION['user_id'] != $post['user_id'] && !isAdmin()) {
    header("Location: ../forum_topic.php?id=$topicId");
    exit;
}

// Видалення повідомлення
$sql = "DELETE FROM forum_posts WHERE post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $postId);

if ($stmt->execute()) {
    // Перевірка, чи є інші повідомлення в темі
    $sql = "SELECT COUNT(*) as count FROM forum_posts WHERE topic_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $topicId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Якщо немає повідомлень, видаляємо тему
    if ($row['count'] == 0) {
        $sql = "DELETE FROM forum_topics WHERE topic_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $topicId);
        $stmt->execute();
        
        header("Location: ../forum.php?success=post_deleted");
        exit;
    }
    
    header("Location: ../forum_topic.php?id=$topicId&success=post_deleted");
    exit;
} else {
    header("Location: ../forum_topic.php?id=$topicId&error=db_error");
    exit;
}