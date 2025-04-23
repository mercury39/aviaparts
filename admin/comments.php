<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Перевірка ролі користувача
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Видалення коментаря, якщо отримано відповідний запит
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $comment_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM comments WHERE comment_id = ?");
    $stmt->bind_param("i", $comment_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Коментар успішно видалено";
    } else {
        $_SESSION['error_message'] = "Помилка при видаленні коментаря";
    }
    
    header('Location: comments.php');
    exit();
}

// Перевіряємо, чи існує колонка ban_reason в таблиці users
$check_ban_reason = $conn->query("SHOW COLUMNS FROM users LIKE 'ban_reason'");
if ($check_ban_reason->num_rows == 0) {
    // Якщо колонки немає, додаємо її
    $conn->query("ALTER TABLE users ADD ban_reason TEXT NULL");
}

// Обробка бану користувача з причиною
if (isset($_POST['ban_user']) && !empty($_POST['username_email'])) {
    $username_email = $_POST['username_email'];
    $ban_reason = !empty($_POST['ban_reason']) ? $_POST['ban_reason'] : NULL;
    
    // Шукаємо користувача за ім'ям користувача або email
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username_email, $username_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];
        
        // Перевіряємо, щоб адмін не забанив сам себе
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error_message'] = "Ви не можете забанити власний аккаунт!";
        } else {
            // Баним користувача і зберігаємо причину
            $stmt = $conn->prepare("UPDATE users SET status = 'banned', ban_reason = ? WHERE user_id = ?");
            $stmt->bind_param("si", $ban_reason, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Користувача успішно заблоковано" . ($ban_reason ? " з вказаною причиною" : "");
            } else {
                $_SESSION['error_message'] = "Помилка при блокуванні користувача";
            }
        }
    } else {
        $_SESSION['error_message'] = "Користувача не знайдено";
    }
    
    header('Location: comments.php');
    exit();
}

// Обробка розблокування користувача
if (isset($_POST['unban_user']) && !empty($_POST['username_email'])) {
    $username_email = $_POST['username_email'];
    
    // Шукаємо користувача за ім'ям користувача або email
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username_email, $username_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];
        
        // Розблоковуємо користувача
        $stmt = $conn->prepare("UPDATE users SET status = 'active', ban_reason = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Користувача успішно розблоковано";
        } else {
            $_SESSION['error_message'] = "Помилка при розблокуванні користувача";
        }
    } else {
        $_SESSION['error_message'] = "Користувача не знайдено";
    }
    
    header('Location: comments.php');
    exit();
}

// Перевіряємо наявність поля status у таблиці users
$check_field = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
if ($check_field->num_rows == 0) {
    // Якщо поля немає, додаємо його
    $conn->query("ALTER TABLE users ADD status ENUM('active', 'banned') NOT NULL DEFAULT 'active'");
}

// Формування запиту з урахуванням фільтрів
$query = "
    SELECT c.*, p.name as part_name, u.username, u.email, u.user_id, u.status, u.ban_reason 
    FROM comments c
    LEFT JOIN parts p ON c.part_id = p.part_id
    LEFT JOIN users u ON c.user_id = u.user_id
    WHERE 1=1
";

// Параметри для підготовленого запиту
$params = [];
$types = "";

// Обробка фільтрів пошуку
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $query .= " AND (u.username LIKE ? OR u.email LIKE ? OR c.comment LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "sss";
}

$query .= " ORDER BY c.created_at DESC";

// Виконання підготовленого запиту
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $comments = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-comments"></i> Управління коментарями</h1>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Назад до панелі
                </a>
            </div>
            <hr>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Форма для бану користувачів -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-ban"></i> Заблокувати/Розблокувати користувача</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="comments.php" class="row g-3">
                <div class="col-md-4">
                    <label for="username_email" class="form-label">Нік користувача або Email</label>
                    <input type="text" name="username_email" id="username_email" class="form-control" required 
                           placeholder="Введіть нік або email користувача">
                </div>
                <div class="col-md-4">
                    <label for="ban_reason" class="form-label">Причина блокування</label>
                    <input type="text" name="ban_reason" id="ban_reason" class="form-control"
                           placeholder="Вкажіть причину блокування">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="ban_user" class="btn btn-warning w-100">
                        <i class="fas fa-ban"></i> Заблокувати
                    </button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="unban_user" class="btn btn-success w-100">
                        <i class="fas fa-unlock"></i> Розблокувати
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Пошук -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Пошук коментарів</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="comments.php" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Пошук за іменем користувача, електронною поштою або текстом коментаря" 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Пошук
                    </button>
                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                        <a href="comments.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Скинути
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Всі коментарі</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Комплектуюча</th>
                            <th>Автор</th>
                            <th>Email</th>
                            <th>Коментар</th>
                            <th>Створено</th>
                            <th>Статус</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($comments)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Коментарі відсутні</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($comment['comment_id']); ?></td>
                                    <td><?php echo htmlspecialchars($comment['part_name']); ?></td>
                                    <td><?php echo htmlspecialchars($comment['username']); ?></td>
                                    <td><?php echo htmlspecialchars($comment['email']); ?></td>
                                    <td><?php echo htmlspecialchars($comment['comment']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></td>
                                    <td>
                                        <?php if (isset($comment['status']) && $comment['status'] === 'banned'): ?>
                                            <span class="badge bg-danger" title="<?php echo htmlspecialchars($comment['ban_reason'] ?? 'Не вказано'); ?>">
                                                Заблокований 
                                                <i class="fas fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top" 
                                                   title="<?php echo htmlspecialchars($comment['ban_reason'] ?? 'Не вказано'); ?>"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Активний</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="<?php echo 'comments.php?delete=' . $comment['comment_id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Ви впевнені, що хочете видалити цей коментар?')">
                                                <i class="fas fa-trash"></i> Видалити
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Ініціалізація підказок для іконок інформації про причину бану
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
</script>

<?php include '../includes/footer.php'; ?>