<?php
// editor/parts.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Перевірка ролі користувача
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'editor') {
    header('Location: ../login.php');
    exit();
}

// Отримати всі категорії для dropdown
$categories = getAllCategories();

// Обробка видалення
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $part_id = (int)$_GET['delete'];
    
    // Починаємо транзакцію
    $conn->begin_transaction();
    
    try {
        // Отримуємо шлях до зображення, щоб видалити файл
        $stmt = $conn->prepare("SELECT image FROM parts WHERE part_id = ?");
        $stmt->bind_param('i', $part_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Якщо є зображення, видаляємо його з файлової системи
            if (!empty($row['image']) && file_exists('../' . $row['image'])) {
                unlink('../' . $row['image']);
            }
        }
        
        // Тепер видаляємо саму запчастину
        $stmt = $conn->prepare("DELETE FROM parts WHERE part_id = ?");
        $stmt->bind_param('i', $part_id);
        $stmt->execute();
        
        // Завершуємо транзакцію
        $conn->commit();
        
        header('Location: parts.php');
        exit;
    } catch (Exception $e) {
        // У випадку помилки відкочуємо всі зміни
        $conn->rollback();
        $error_message = "Помилка при видаленні: " . $e->getMessage();
    }
}

// Обробка форми додавання
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_part'])) {
    $name          = cleanInput($_POST['name']);
    $category_id   = (int)$_POST['category_id'];
    $description   = cleanInput($_POST['description']);
    $specs         = cleanInput($_POST['specifications']);
    $compatibility = cleanInput($_POST['compatibility']);

    // Обробка завантаження зображення
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = '../assets/images/';
        $filename  = time() . '_' . basename($_FILES['image']['name']);
        $target    = $targetDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $imagePath = 'assets/images/' . $filename;
        }
    }

    $sql = "INSERT INTO parts 
            (name, category_id, description, specifications, compatibility, image) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sissss',
        $name,
        $category_id,
        $description,
        $specs,
        $compatibility,
        $imagePath
    );
    $stmt->execute();
    header('Location: parts.php');
    exit;
}

// Обробка форми редагування
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_part'])) {
    $part_id       = (int)$_POST['part_id'];
    $name          = cleanInput($_POST['name']);
    $category_id   = (int)$_POST['category_id'];
    $description   = cleanInput($_POST['description']);
    $specs         = cleanInput($_POST['specifications']);
    $compatibility = cleanInput($_POST['compatibility']);

    // Якщо завантажили нове зображення — обробити
    if (!empty($_FILES['image']['name'])) {
        $targetDir = '../assets/images/';
        $filename  = time() . '_' . basename($_FILES['image']['name']);
        $target    = $targetDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $imagePath = 'assets/images/' . $filename;
            $sqlImg = ", image = ?";
        } else {
            $sqlImg = "";
        }
    } else {
        $sqlImg = "";
    }

    // Формуємо SQL-запит з (або без) оновлення картинки
    $sql = "UPDATE parts SET 
                name = ?, 
                category_id = ?, 
                description = ?, 
                specifications = ?, 
                compatibility = ?"
            . $sqlImg .
           " WHERE part_id = ?";
    $stmt = $conn->prepare($sql);

    if (isset($imagePath)) {
        $stmt->bind_param('sissssi',
            $name,
            $category_id,
            $description,
            $specs,
            $compatibility,
            $imagePath,
            $part_id
        );
    } else {
        $stmt->bind_param('sisssi',
            $name,
            $category_id,
            $description,
            $specs,
            $compatibility,
            $part_id
        );
    }

    $stmt->execute();
    header('Location: parts.php');
    exit;
}

// Якщо редагуємо — завантажуємо дані поточної запчастини
$editing = false;
if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    $editing = true;
    $current = getPartById((int)$_GET['edit']);
}

// Для списку всіх запчастин
$result = $conn->query("SELECT p.part_id, p.name, c.name AS category_name 
                        FROM parts p 
                        LEFT JOIN categories c ON p.category_id = c.category_id 
                        ORDER BY p.created_at DESC");
$parts = $result->fetch_all(MYSQLI_ASSOC);
?>
<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-cog"></i> Редагування комплектуючих</h1>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Назад до панелі
                </a>
            </div>
            <hr>
        </div>
    </div>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger">
        <?= $error_message ?>
    </div>
<?php endif; ?>

<?php if ($editing): ?>
    <h4>Редагувати: <?= htmlspecialchars($current['name']) ?></h4>
<?php else: ?>
    <h4>Додати нову запчастину</h4>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="mb-5">
    <?php if ($editing): ?>
        <input type="hidden" name="part_id" value="<?= $current['part_id'] ?>">
    <?php endif; ?>

    <div class="form-group">
        <label>Назва</label>
        <input type="text" name="name" class="form-control" required
               value="<?= $editing ? htmlspecialchars($current['name']) : '' ?>">
    </div>

    <div class="form-group">
        <label>Категорія</label>
        <select name="category_id" class="form-control" required>
            <option value="">— оберіть категорію —</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id'] ?>"
                    <?= $editing && $cat['category_id']==$current['category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Опис</label>
        <textarea name="description" class="form-control"><?= $editing ? htmlspecialchars($current['description']) : '' ?></textarea>
    </div>

    <div class="form-group">
        <label>Технічні характеристики</label>
        <textarea name="specifications" class="form-control"><?= $editing ? htmlspecialchars($current['specifications']) : '' ?></textarea>
    </div>

    <div class="form-group">
        <label>Сумісність</label>
        <textarea name="compatibility" class="form-control"><?= $editing ? htmlspecialchars($current['compatibility']) : '' ?></textarea>
    </div>

    <div class="form-group">
        <label>Зображення</label>
        <input type="file" name="image" class="form-control-file">
        <?php if ($editing && !empty($current['image'])): ?>
            <small>Поточне: <img src="../<?= $current['image'] ?>" height="50"></small>
        <?php endif; ?>
    </div>

    <div class="form-group mt-4">
        <button name="<?= $editing ? 'edit_part' : 'add_part' ?>"
                class="btn btn-<?= $editing ? 'primary' : 'success' ?> btn-lg">
            <?= $editing ? 'Оновити' : 'Додати' ?>
        </button>

        <?php if ($editing): ?>
            <a href="parts.php" class="btn btn-secondary btn-lg ml-2">Скасувати</a>
        <?php endif; ?>
    </div>
</form>

<hr>

<h4>Всі запчастини</h4>
<table class="table table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>Назва</th>
            <th>Категорія</th>
            <th class="text-right" style="min-width: 220px">Дії</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($parts as $p): ?>
        <tr>
            <td><?= $p['part_id'] ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['category_name']) ?></td>
            <td class="text-right">
                <a href="parts.php?edit=<?= $p['part_id'] ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Редагувати
                </a>
                <a href="parts.php?delete=<?= $p['part_id'] ?>" 
                   class="btn btn-danger ml-2" 
                   onclick="return confirm('Ви впевнені, що хочете видалити цю запчастину?')">
                    <i class="fas fa-trash"></i> Видалити
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>