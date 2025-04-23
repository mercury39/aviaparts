<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Перевірка ролі користувача
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'editor' && $_SESSION['user_type'] !== 'admin')) {
    header('Location: ../login.php');
    exit();
}

// Видалення коментаря, якщо отримано відповідний запит (тільки для редактора та адміна)
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

// Формування запиту з урахуванням фільтрів
$query = "
    SELECT c.*, p.name as part_name, u.username, u.email, u.user_id, u.status
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
    $query .= " AND (u.username LIKE ? OR u.email LIKE ? OR c.comment LIKE ? OR p.name LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "ssss";
}

// Фільтр за комплектуючою
if (isset($_GET['part_id']) && !empty($_GET['part_id'])) {
    $query .= " AND c.part_id = ?";
    $params[] = $_GET['part_id'];
    $types .= "i";
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

// Отримання списку комплектуючих для фільтра
$parts = $conn->query("SELECT part_id, name FROM parts ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

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

    <!-- Пошук та фільтрація -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Пошук та фільтрація коментарів</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="comments.php" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Пошук за автором, текстом або назвою комплектуючої" 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <div class="col-md-5">
                    <select name="part_id" class="form-select">
                        <option value="">-- Всі комплектуючі --</option>
                        <?php foreach ($parts as $part): ?>
                            <option value="<?php echo $part['part_id']; ?>" 
                                    <?php echo (isset($_GET['part_id']) && $_GET['part_id'] == $part['part_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($part['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Пошук
                    </button>
                </div>
                <?php if ((isset($_GET['search']) && !empty($_GET['search'])) || (isset($_GET['part_id']) && !empty($_GET['part_id']))): ?>
                    <div class="col-md-12 mt-2">
                        <a href="comments.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Скинути фільтри
                        </a>
                    </div>
                <?php endif; ?>
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
                            <th>Коментар</th>
                            <th>Створено</th>
                            <th>Статус</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($comments)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Коментарі відсутні</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($comment['comment_id']); ?></td>
                                    <td><?php echo htmlspecialchars($comment['part_name']); ?></td>
                                    <td><?php echo htmlspecialchars($comment['username']); ?></td>
                                    <td><?php echo htmlspecialchars($comment['comment']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></td>
                                    <td>
                                        <?php if (isset($comment['status']) && $comment['status'] === 'banned'): ?>
                                            <span class="badge bg-danger">Заблокований</span>
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

<?php include '../includes/footer.php'; ?>