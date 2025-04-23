<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Перевірка авторизації та прав доступу
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit;
}

$successMessage = $errorMessage = '';

// Обробка видалення теми
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $topic_id = intval($_GET['id']);
    
    // Перевірка, чи існує тема
    $check_stmt = $conn->prepare("SELECT topic_id FROM forum_topics WHERE topic_id = ?");
    $check_stmt->bind_param("i", $topic_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $errorMessage = "Теми з таким ID не існує";
    } else {
        try {
            // Починаємо транзакцію
            $conn->begin_transaction();
            
            // Видалення всіх повідомлень у темі
            $stmt = $conn->prepare("DELETE FROM forum_posts WHERE topic_id = ?");
            $stmt->bind_param("i", $topic_id);
            $stmt->execute();
            
            // Видалення самої теми
            $stmt = $conn->prepare("DELETE FROM forum_topics WHERE topic_id = ?");
            $stmt->bind_param("i", $topic_id);
            $stmt->execute();
            
            // Завершення транзакції
            $conn->commit();
            
            $successMessage = "Тему успішно видалено";
        } catch (Exception $e) {
            // Відкат у разі помилки
            $conn->rollback();
            $errorMessage = "Помилка при видаленні теми. Спробуйте пізніше.";
            // Логування помилки для адміністратора
            error_log("Помилка при видаленні теми #$topic_id: " . $e->getMessage());
        }
    }
}

// Створення нової теми (для адміністраторів)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_topic'])) {
    $title = cleanInput($_POST['title']);
    
    if (empty($title)) {
        $errorMessage = "Назва теми не може бути порожньою";
    } else {
        try {
            $user_id = $_SESSION['user_id'];
            
            $stmt = $conn->prepare("INSERT INTO forum_topics (title, user_id) VALUES (?, ?)");
            $stmt->bind_param("si", $title, $user_id);
            
            if ($stmt->execute()) {
                $successMessage = "Нову тему успішно створено";
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $errorMessage = "Помилка при створенні теми. Спробуйте пізніше.";
            error_log("Помилка при створенні теми: " . $e->getMessage());
        }
    }
}

// Отримання всіх тем форуму
try {
    // Оптимізований запит для отримання тем форуму
    $query = "SELECT t.topic_id, t.title, t.created_at, u.username, 
             (SELECT COUNT(*) FROM forum_posts WHERE topic_id = t.topic_id) as post_count
             FROM forum_topics t 
             LEFT JOIN users u ON t.user_id = u.user_id 
             ORDER BY t.created_at DESC";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $errorMessage = "Помилка при завантаженні тем форуму. Спробуйте пізніше.";
    error_log("Помилка SQL запиту: " . $e->getMessage());
    $result = false;
}

$title = "Управління форумом | Aviaparts Admin";
include '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Управління форумом</h1>
    
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Створити нову тему форуму</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="form-group">
                    <label for="title">Назва теми</label>
                    <input type="text" class="form-control" id="title" name="title" required maxlength="255">
                </div>
                <button type="submit" name="create_topic" class="btn btn-primary mt-3">Створити тему</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Список тем форуму</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Назва теми</th>
                            <th>Автор</th>
                            <th>Дата створення</th>
                            <th>Кількість повідомлень</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['topic_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td><?php echo $row['post_count']; ?></td>
                                    <td>
                                        <a href="../forum_topic.php?id=<?php echo $row['topic_id']; ?>" class="btn btn-sm btn-info" target="_blank">Переглянути</a>
                                        <a href="forum.php?action=delete&id=<?php echo $row['topic_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Ви впевнені, що хочете видалити цю тему разом з усіма повідомленнями?')">Видалити</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Теми форуму відсутні</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>