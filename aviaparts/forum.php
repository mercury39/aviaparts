<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
include 'includes/header.php';

// Перевірка дозволу на доступ до форуму
if (!isLoggedIn()) {
    header("Location: login.php?redirect=forum.php");
    exit;
}

// Отримання всіх тем з бази даних
$sql = "SELECT t.*, u.username, 
        (SELECT COUNT(*) FROM forum_posts WHERE topic_id = t.topic_id) as post_count,
        (SELECT MAX(created_at) FROM forum_posts WHERE topic_id = t.topic_id) as last_activity
        FROM forum_topics t 
        LEFT JOIN users u ON t.user_id = u.user_id 
        ORDER BY last_activity DESC, t.created_at DESC";
$result = $conn->query($sql);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Форум</h1>
                <?php if (isLoggedIn()): ?>
                <a href="create_topic.php" class="btn btn-primary">Створити нову тему</a>
                <?php endif; ?>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    if ($_GET['success'] == 'topic_created') {
                        echo "Тему успішно створено!";
                    } elseif ($_GET['success'] == 'post_added') {
                        echo "Повідомлення додано!";
                    } elseif ($_GET['success'] == 'post_deleted') {
                        echo "Повідомлення видалено!";
                    } elseif ($_GET['success'] == 'post_updated') {
                        echo "Повідомлення оновлено!";
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header bg-light">
                    <div class="row">
                        <div class="col-md-6">Тема</div>
                        <div class="col-md-2">Автор</div>
                        <div class="col-md-2">Відповіді</div>
                        <div class="col-md-2">Остання активність</div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if ($result->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <a href="forum_topic.php?id=<?php echo $row['topic_id']; ?>" class="fw-bold text-decoration-none">
                                                <?php echo htmlspecialchars($row['title']); ?>
                                            </a>
                                        </div>
                                        <div class="col-md-2">
                                            <?php echo htmlspecialchars($row['username']); ?>
                                        </div>
                                        <div class="col-md-2">
                                            <?php echo $row['post_count']; ?> відповідей
                                        </div>
                                        <div class="col-md-2">
                                            <?php 
                                            if ($row['last_activity']) {
                                                echo date('d.m.Y H:i', strtotime($row['last_activity']));
                                            } else {
                                                echo date('d.m.Y H:i', strtotime($row['created_at']));
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="p-4 text-center">
                            <p>Немає тем для обговорення. Будьте першим, хто створить тему!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>