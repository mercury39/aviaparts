<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Перевірка авторизації
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Отримання інформації про поточного користувача
$user = getCurrentUser();
$userId = $user['user_id'];

// Отримання дозволів користувача
$permissions = [];
$sql = "SELECT permission, value FROM user_permissions WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $permissions[$row['permission']] = (bool)$row['value'];
    }
}

// Отримання коментарів користувача
$comments = [];
$sql = "SELECT c.*, p.name as part_name FROM comments c 
        JOIN parts p ON c.part_id = p.part_id 
        WHERE c.user_id = ? 
        ORDER BY c.created_at DESC 
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
}

// Заголовок сторінки
$pageTitle = "Профіль користувача: " . htmlspecialchars($user['username']);
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Бічне меню користувача -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Меню користувача</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="profile.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-user me-2"></i> Мій профіль
                    </a>
                    <a href="edit_profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-edit me-2"></i> Редагувати профіль
                    </a>
                    
                    <?php if (isAdmin()): ?>
                    <a href="../admin/index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tools me-2"></i> Панель адміністратора
                    </a>
                    <?php elseif (isEditor()): ?>
                    <a href="../editor/index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tools me-2"></i> Панель редактора
                    </a>
                    <?php endif; ?>
                    
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Вийти
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Основний контент профілю -->
        <div class="col-md-9">
            <!-- Інформація про користувача -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Інформація про користувача</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <!-- Тип користувача (бейдж) -->
                            <div class="badge bg-<?php echo ($user['user_type'] === 'admin') ? 'danger' : (($user['user_type'] === 'editor') ? 'warning' : 'info'); ?> mb-2">
                                <?php 
                                echo ($user['user_type'] === 'admin') ? 'Адміністратор' : 
                                    (($user['user_type'] === 'editor') ? 'Редактор' : 'Користувач'); 
                                ?>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <table class="table table-hover">
                                <tbody>
                                    <tr>
                                        <th scope="row">Ім'я користувача:</th>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Email:</th>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Тип користувача:</th>
                                        <td>
                                            <?php 
                                            echo ($user['user_type'] === 'admin') ? 'Адміністратор' : 
                                                (($user['user_type'] === 'editor') ? 'Редактор' : 'Користувач'); 
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Дата реєстрації:</th>
                                        <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Дозволи користувача -->
            <?php if (!empty($permissions)): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Мої дозволи</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($permissions as $permission => $value): ?>
                            <div class="col-md-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-<?php echo $value ? 'check text-success' : 'times text-danger'; ?> me-2"></i>
                                    <span>
                                        <?php 
                                        switch ($permission) {
                                            case 'add_parts':
                                                echo 'Додавання комплектуючих';
                                                break;
                                            case 'edit_parts':
                                                echo 'Редагування комплектуючих';
                                                break;
                                            case 'delete_parts':
                                                echo 'Видалення комплектуючих';
                                                break;
                                            case 'manage_categories':
                                                echo 'Управління категоріями';
                                                break;
                                            case 'access_forum':
                                                echo 'Доступ до форуму';
                                                break;
                                            case 'comment':
                                                echo 'Коментування';
                                                break;
                                            default:
                                                echo htmlspecialchars($permission);
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Останні коментарі користувача -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Мої останні коментарі</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($comments)): ?>
                        <p class="text-muted">У вас ще немає коментарів.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Комплектуюча</th>
                                        <th>Коментар</th>
                                        <th>Дата</th>
                                        <th>Дії</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comments as $comment): ?>
                                        <tr>
                                            <td>
                                                <a href="../part.php?id=<?php echo $comment['part_id']; ?>">
                                                    <?php echo htmlspecialchars($comment['part_name']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars(mb_substr($comment['comment'], 0, 50)) . (mb_strlen($comment['comment']) > 50 ? '...' : ''); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></td>
                                            <td>
                                                <a href="../delete_comment.php?id=<?php echo $comment['comment_id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Ви впевнені, що хочете видалити цей коментар?');">
                                                    <i class="fas fa-trash"></i> Видалити
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>