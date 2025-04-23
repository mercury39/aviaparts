<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Перевірка авторизації
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Перевірка наявності необхідних параметрів
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['part_id']) || !is_numeric($_GET['part_id'])) {
    header("Location: catalog.php?error=invalid_params");
    exit;
}

$commentId = (int)$_GET['id'];
$partId = (int)$_GET['part_id'];

// Перевірка, чи користувач має право видалити коментар (адмін або автор коментаря)
$sql = "SELECT user_id FROM comments WHERE comment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $commentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: part.php?id=$partId&error=comment_not_found");
    exit;
}

$comment = $result->fetch_assoc();

// Перевірка прав доступу (видаляти можуть тільки адміністратори або автори коментарів)
if (!isAdmin() && $_SESSION['user_id'] != $comment['user_id']) {
    header("Location: part.php?id=$partId&error=permission_denied");
    exit;
}

// Видалення коментаря
$sql = "DELETE FROM comments WHERE comment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $commentId);

if ($stmt->execute()) {
    header("Location: part.php?id=$partId&success=comment_deleted");
} else {
    header("Location: part.php?id=$partId&error=delete_failed");
}
exit;
?>