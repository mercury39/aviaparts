<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Перевірка ролі користувача
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'editor' && $_SESSION['user_type'] !== 'admin')) {
    header('Location: ../login.php');
    exit();
}

// Оголошення змінних для повідомлень
$successMessage = "";
$errorMessage = "";

// Обробка додавання нової категорії
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = cleanInput($_POST['name']);
    $desc = cleanInput($_POST['description']);
    $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $name, $desc);
    
    if ($stmt->execute()) {
        $successMessage = "Категорію успішно додано!";
    } else {
        $errorMessage = "Помилка додавання категорії: " . $conn->error;
    }
}

// Обробка редагування категорії
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = (int)$_POST['category_id'];
    $name = cleanInput($_POST['name']);
    $desc = cleanInput($_POST['description']);
    
    $sql = "UPDATE categories SET name = ?, description = ? WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $name, $desc, $id);
    
    if ($stmt->execute()) {
        $successMessage = "Категорію успішно оновлено!";
    } else {
        $errorMessage = "Помилка оновлення категорії: " . $conn->error;
    }
}

// Обробка видалення - лише для перегляду (редактор не може видаляти категорії)
if (isset($_GET['delete'])) {
    $errorMessage = "Редактор не може видаляти категорії. Зверніться до адміністратора.";
}

// Отримання даних для редагування, якщо передано ID
$editMode = false;
$categoryToEdit = null;

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $categoryToEdit = $stmt->get_result()->fetch_assoc();
    if ($categoryToEdit) {
        $editMode = true;
    }
}

// Отримуємо список категорій з підрахунком комплектуючих
$categoriesQuery = "SELECT c.*, COUNT(p.part_id) as parts_count 
                    FROM categories c 
                    LEFT JOIN parts p ON c.category_id = p.category_id 
                    GROUP BY c.category_id 
                    ORDER BY c.name";
$result = $conn->query($categoriesQuery);
$categories = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-folder"></i> Керування категоріями</h1>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Назад до панелі
                </a>
            </div>
            <hr>
        </div>
    </div>

    <!-- Відображення повідомлень про успіх або помилку -->
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= $errorMessage ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= $successMessage ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Форма додавання/редагування -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <?php if ($editMode): ?>
                            <i class="fas fa-edit"></i> Редагування категорії
                        <?php else: ?>
                            <i class="fas fa-plus-circle"></i> Додавання нової категорії
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($editMode): ?>
                            <input type="hidden" name="category_id" value="<?= $categoryToEdit['category_id'] ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="name"><i class="fas fa-tag"></i> Назва категорії</label>
                            <input type="text" id="name" name="name" class="form-control" required
                                   value="<?= $editMode ? htmlspecialchars($categoryToEdit['name']) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="description"><i class="fas fa-align-left"></i> Опис</label>
                            <textarea id="description" name="description" class="form-control" rows="4"><?= $editMode ? htmlspecialchars($categoryToEdit['description']) : '' ?></textarea>
                        </div>

                        <?php if ($editMode): ?>
                        <div class="form-group">
                            <label><i class="fas fa-cubes"></i> Кількість комплектуючих</label>
                            <?php
                            // Отримуємо кількість комплектуючих для цієї категорії
                            $partsCountQuery = $conn->prepare("SELECT COUNT(*) as count FROM parts WHERE category_id = ?");
                            $partsCountQuery->bind_param('i', $categoryToEdit['category_id']);
                            $partsCountQuery->execute();
                            $partsCount = $partsCountQuery->get_result()->fetch_assoc()['count'];
                            ?>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?= $partsCount ?>" readonly>
                                <div class="input-group-append">
                                    <a href="parts.php?category=<?= $categoryToEdit['category_id'] ?>" class="btn btn-info">
                                        <i class="fas fa-eye"></i> Переглянути
                                    </a>
                                </div>
                            </div>
                            <small class="form-text text-muted">Кількість комплектуючих, які належать до цієї категорії</small>
                        </div>
                        <?php endif; ?>

                        <div class="form-group text-right">
                            <?php if ($editMode): ?>
                                <a href="categories.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Скасувати
                                </a>
                                <button type="submit" name="edit_category" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Оновити категорію
                                </button>
                            <?php else: ?>
                                <button type="submit" name="add_category" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Додати категорію
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Список категорій -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-list"></i> Список категорій</h5>
                        <span class="badge badge-primary"><?= count($categories) ?> категорій</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Категорії ще не створені
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="25%">Назва</th>
                                        <th width="40%">Опис</th>
                                        <th width="15%">Кількість деталей</th>
                                        <th width="15%">Дії</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><?= $cat['category_id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($cat['name']) ?></strong>
                                        </td>
                                        <td>
                                            <?php if (!empty($cat['description'])): ?>
                                                <?= htmlspecialchars($cat['description']) ?>
                                            <?php else: ?>
                                                <span class="text-muted"><em>Опис відсутній</em></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-pill badge-info">
                                                <?= $cat['parts_count'] ?> комплектуючих
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="?edit=<?= $cat['category_id'] ?>" class="btn btn-outline-primary" title="Редагувати">
                                                    <i class="fas fa-edit"></i> Редагувати
                                                </a>
                                                <!-- Кнопка видалення прихована для редакторів -->
                                                <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                                <a href="?delete=<?= $cat['category_id'] ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Ви впевнені, що хочете видалити категорію <?= htmlspecialchars($cat['name']) ?>?')"
                                                   title="Видалити"
                                                   <?= $cat['parts_count'] > 0 ? 'disabled' : '' ?>>
                                                    <i class="fas fa-trash-alt"></i> Видалити
                                                </a>
                                                <?php endif; ?>
                                            </div>
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

<!-- Custom JS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Автоматичне приховування повідомлень про успіх
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-success');
        alerts.forEach(function(alert) {
            const closeButton = alert.querySelector('button.close');
            if (closeButton) {
                closeButton.click();
            }
        });
    }, 5000);
    
    // Фокус на поле назви категорії при завантаженні сторінки
    document.getElementById('name').focus();
});
</script>

<?php include '../includes/footer.php'; ?>