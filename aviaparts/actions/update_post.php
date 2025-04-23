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
$postId = (int)$_POST['post_id'];
$topicId = (int)$_POST['topic_id'];
$content = cleanInput($_POST['content']);

// Валідація даних
if (empty($content)) {
    header("Location: ../edit_post.php?id=$postId&error=empty_content");
    exit;
}

// Отримання даних повідомлення для перевірки прав
$sql = "SELECT * FROM forum_posts WHERE post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../forum.php");
    exit;
}

$post = $result->fetch_assoc();

// Перевірка прав на редагування (автор або адміністратор)
if ($_SESSION['user_id'] != $post['user_id'] && !isAdmin()) {
    header("Location: ../forum_topic.php?id=$topicId");
    exit;
}

// Оновлення повідомлення
$sql = "UPDATE forum_posts SET content = ? WHERE post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $content, $postId);

if ($stmt->execute()) {
    header("Location: ../forum_topic.php?id=$topicId&success=post_updated#post-$postId");
    exit;
} else {
    header("Location: ../edit_post.php?id=$postId&error=db_error");
    exit;
}