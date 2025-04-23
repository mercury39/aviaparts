<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
include 'includes/header.php';

// Перевірка авторизації
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Перевірка наявності ID повідомлення
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: forum.php");
    exit;
}

$postId = (int)$_GET['id'];

// Отримання даних повідомлення
$sql = "SELECT p.*, t.topic_id, t.title as topic_title 
        FROM forum_posts p 
        JOIN forum_topics t ON p.topic_id = t.topic_id 
        WHERE p.post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: forum.php");
    exit;
}

$post = $result->fetch_assoc();

// Перевірка прав на редагування (автор або адміністратор)
if ($_SESSION['user_id'] != $post['user_id'] && !isAdmin()) {
    header("Location: forum_topic.php?id=" . $post['topic_id']);
    exit;
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="forum.php">Форум</a></li>
                    <li class="breadcrumb-item"><a href="forum_topic.php?id=<?php echo $post['topic_id']; ?>"><?php echo htmlspecialchars($post['topic_title']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Редагування повідомлення</li>
                </ol>
            </nav>
            
            <div class="card">
                <div class="card-header bg-light">
                    <h2>Редагування повідомлення</h2>
                </div>
                <div class="card-body">
                    <form action="actions/update_post.php" method="post">
                        <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                        <input type="hidden" name="topic_id" value="<?php echo $post['topic_id']; ?>">
                        <div class="mb-3">
                            <label for="content" class="form-label">Текст повідомлення</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Зберегти зміни</button>
                            <a href="forum_topic.php?id=<?php echo $post['topic_id']; ?>" class="btn btn-outline-secondary">Скасувати</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>