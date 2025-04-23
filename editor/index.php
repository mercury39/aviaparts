<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Перевірка ролі користувача
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'editor' && $_SESSION['user_type'] !== 'admin')) {
    header('Location: ../login.php');
    exit();
}

// Отримуємо статистику
$partsCount = $conn->query("SELECT COUNT(*) as count FROM parts")->fetch_assoc()['count'];
$categoriesCount = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
$commentsCount = $conn->query("SELECT COUNT(*) as count FROM comments")->fetch_assoc()['count'];

// Останні додані комплектуючі
$latestParts = $conn->query("SELECT p.*, c.name as category_name FROM parts p 
                            LEFT JOIN categories c ON p.category_id = c.category_id 
                            ORDER BY p.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Останні коментарі
$latestComments = $conn->query("
    SELECT c.*, p.name as part_name, u.username 
    FROM comments c
    LEFT JOIN parts p ON c.part_id = p.part_id
    LEFT JOIN users u ON c.user_id = u.user_id
    ORDER BY c.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-edit"></i> Панель редактора AviaParts</h1>
                <a href="../index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> На головну
                </a>
            </div>
            <hr>
        </div>
    </div>
    
    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Комплектуючі</h5>
                            <h2><?php echo $partsCount; ?></h2>
                        </div>
                        <i class="fas fa-cog fa-3x"></i>
                    </div>
                    <a href="parts.php" class="text-white">Керувати <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Категорії</h5>
                            <h2><?php echo $categoriesCount; ?></h2>
                        </div>
                        <i class="fas fa-folder fa-3x"></i>
                    </div>
                    <a href="categories.php" class="text-white">Керувати <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Коментарі</h5>
                            <h2><?php echo $commentsCount; ?></h2>
                        </div>
                        <i class="fas fa-comments fa-3x"></i>
                    </div>
                    <a href="comments.php" class="text-dark">Переглянути <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Швидкі посилання -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Швидкі дії</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="parts.php" class="btn btn-outline-success btn-block">
                                <i class="fas fa-cog"></i> Комплектуючі
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="categories.php" class="btn btn-outline-info btn-block">
                                <i class="fas fa-folder"></i> Категорії
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="comments.php" class="btn btn-outline-warning btn-block">
                                <i class="fas fa-comments"></i> Коментарі
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Блок з вкладками для останніх даних -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <ul class="nav nav-tabs card-header-tabs" id="dataTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="parts-tab" data-bs-toggle="tab" data-bs-target="#parts" type="button" role="tab" aria-controls="parts" aria-selected="true">
                                <i class="fas fa-cog"></i> Останні комплектуючі
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab" aria-controls="comments" aria-selected="false">
                                <i class="fas fa-comments"></i> Останні коментарі
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content" id="dataTabsContent">
                        <!-- Вкладка комплектуючих -->
                        <div class="tab-pane fade show active" id="parts" role="tabpanel" aria-labelledby="parts-tab">
                            <div class="d-flex justify-content-between align-items-center p-3 bg-light border-bottom">
                                <h5 class="mb-0">Останні комплектуючі</h5>
                                <a href="parts.php" class="btn btn-sm btn-primary">Усі комплектуючі</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Назва</th>
                                            <th>Категорія</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($latestParts)): ?>
                                            <tr>
                                                <td colspan="2" class="text-center">Комплектуючі відсутні</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($latestParts as $part): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($part['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($part['category_name'] ?? 'Не визначено'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Вкладка коментарів -->
                        <div class="tab-pane fade" id="comments" role="tabpanel" aria-labelledby="comments-tab">
                            <div class="d-flex justify-content-between align-items-center p-3 bg-light border-bottom">
                                <h5 class="mb-0">Останні коментарі</h5>
                                <a href="comments.php" class="btn btn-sm btn-warning">Усі коментарі</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Комплектуюча</th>
                                            <th>Автор</th>
                                            <th>Фрагмент</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($latestComments)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center">Коментарі відсутні</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($latestComments as $comment): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($comment['part_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($comment['username']); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($comment['comment'], 0, 30)) . (strlen($comment['comment']) > 30 ? '...' : ''); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Додатковий JavaScript для роботи вкладок (якщо потрібно) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ініціалізація вкладок, якщо Bootstrap JS не підключений
        const tabs = document.querySelectorAll('#dataTabs .nav-link');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function(event) {
                event.preventDefault();
                
                // Деактивація всіх вкладок
                tabs.forEach(t => {
                    t.classList.remove('active');
                    t.setAttribute('aria-selected', 'false');
                });
                
                // Деактивація всіх панелей
                tabPanes.forEach(pane => {
                    pane.classList.remove('show', 'active');
                });
                
                // Активація натиснутої вкладки
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');
                
                // Активація відповідної панелі
                const targetPaneId = this.getAttribute('data-bs-target').substring(1);
                const targetPane = document.getElementById(targetPaneId);
                if (targetPane) {
                    targetPane.classList.add('show', 'active');
                }
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>