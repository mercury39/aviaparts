<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
include 'includes/header.php';

// Перевірка авторизації
if (!isLoggedIn()) {
    header("Location: login.php?redirect=forum.php");
    exit;
}

// Перевірка наявності ID теми
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: forum.php");
    exit;
}

$topicId = (int)$_GET['id'];

// Отримання інформації про тему
$sql = "SELECT t.*, u.username FROM forum_topics t 
        LEFT JOIN users u ON t.user_id = u.user_id 
        WHERE t.topic_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $topicId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: forum.php");
    exit;
}

$topic = $result->fetch_assoc();

// Отримання повідомлень теми
$sql = "SELECT p.*, u.username, u.avatar FROM forum_posts p 
        LEFT JOIN users u ON p.user_id = u.user_id 
        WHERE p.topic_id = ? 
        ORDER BY p.created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $topicId);
$stmt->execute();
$posts = $stmt->get_result();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="forum.php">Форум</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($topic['title']); ?></li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?php echo htmlspecialchars($topic['title']); ?></h1>
                <a href="forum.php" class="btn btn-outline-secondary">Назад до форуму</a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    if ($_GET['success'] == 'post_added') {
                        echo "Повідомлення додано!";
                    } elseif ($_GET['success'] == 'post_deleted') {
                        echo "Повідомлення видалено!";
                    } elseif ($_GET['success'] == 'post_updated') {
                        echo "Повідомлення оновлено!";
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Повідомлення теми -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Автор теми: <strong><?php echo htmlspecialchars($topic['username']); ?></strong></span>
                        <span>Створено: <?php echo date('d.m.Y H:i', strtotime($topic['created_at'])); ?></span>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if ($posts->num_rows > 0): ?>
                        <?php while ($post = $posts->fetch_assoc()): ?>
                            <div class="card mb-3" id="post-<?php echo $post['post_id']; ?>">
                                <div class="card-header bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if ($post['avatar']): ?>
                                                <img src="<?php echo htmlspecialchars($post['avatar']); ?>" alt="Avatar" class="rounded-circle" width="30" height="30">
                                            <?php else: ?>
                                                <i class="bi bi-person-circle"></i>
                                            <?php endif; ?>
                                            <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                                        </div>
                                        <div>
                                            <?php echo date('d.m.Y H:i', strtotime($post['created_at'])); ?>
                                            
                                            <?php if ($_SESSION['user_id'] == $post['user_id'] || isAdmin()): ?>
                                                <div class="btn-group btn-group-sm ms-2">
                                                    <a href="edit_post.php?id=<?php echo $post['post_id']; ?>" class="btn btn-outline-secondary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="actions/delete_post.php?id=<?php echo $post['post_id']; ?>" 
                                                       class="btn btn-outline-danger" 
                                                       onclick="return confirm('Ви впевнені, що хочете видалити це повідомлення?');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Поки що немає повідомлень у цій темі.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Форма для додавання нового повідомлення -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Додати повідомлення</h5>
                </div>
                <div class="card-body">
                    <form action="actions/add_post.php" method="post">
                        <input type="hidden" name="topic_id" value="<?php echo $topicId; ?>">
                        <div class="mb-3">
                            <label for="content" class="form-label">Текст повідомлення</label>
                            <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Відправити</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>