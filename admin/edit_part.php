<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}


// Отримання категорій
$categories = $conn->query("SELECT * FROM categories");

// Ініціалізація змінних
$edit_mode = false;
$part_id = $name = $description = $specs = $compatibility = $image = $category_id = '';

// Якщо редагування
if (isset($_GET['id'])) {
    $edit_mode = true;
    $stmt = $conn->prepare("SELECT * FROM parts WHERE part_id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $part = $result->fetch_assoc();

    if ($part) {
        $part_id = $part['part_id'];
        $name = $part['name'];
        $description = $part['description'];
        $specs = $part['specifications'];
        $compatibility = $part['compatibility'];
        $image = $part['image'];
        $category_id = $part['category_id'];
    }
}

// Обробка форми
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $specs = $_POST['specifications'];
    $compatibility = $_POST['compatibility'];
    $category_id = $_POST['category_id'];

    // Обробка файлу
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . '_' . $_FILES['image']['name'];
        $image_tmp = $_FILES['image']['tmp_name'];
        move_uploaded_file($image_tmp, "../assets/images/" . $image_name);
    } else {
        $image_name = $image;
    }

    if ($edit_mode) {
        $stmt = $conn->prepare("UPDATE parts SET name=?, category_id=?, description=?, specifications=?, compatibility=?, image=? WHERE part_id=?");
        $stmt->bind_param("sissssi", $name, $category_id, $description, $specs, $compatibility, $image_name, $part_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO parts (name, category_id, description, specifications, compatibility, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissss", $name, $category_id, $description, $specs, $compatibility, $image_name);
        $stmt->execute();
    }

    header("Location: parts.php");
    exit();
}
?>

<?php include '../includes/header.php'; ?>
<h2><?= $edit_mode ? 'Редагування деталі' : 'Додавання нової деталі' ?></h2>

<form method="post" enctype="multipart/form-data">
    <label>Назва:</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required><br><br>

    <label>Категорія:</label><br>
    <select name="category_id" required>
        <option value="">-- Виберіть категорію --</option>
        <?php while ($cat = $categories->fetch_assoc()): ?>
            <option value="<?= $cat['category_id'] ?>" <?= ($cat['category_id'] == $category_id) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Опис:</label><br>
    <textarea name="description" rows="4"><?= htmlspecialchars($description) ?></textarea><br><br>

    <label>Характеристики:</label><br>
    <textarea name="specifications" rows="3"><?= htmlspecialchars($specs) ?></textarea><br><br>

    <label>Сумісність:</label><br>
    <textarea name="compatibility" rows="3"><?= htmlspecialchars($compatibility) ?></textarea><br><br>

    <label>Фото (jpg, png):</label><br>
    <?php if ($image): ?>
        <img src="../assets/images/<?= $image ?>" alt="part image" width="100"><br>
    <?php endif; ?>
    <input type="file" name="image"><br><br>

    <button type="submit"><?= $edit_mode ? 'Оновити' : 'Додати' ?></button>
</form>

<?php include '../includes/footer.php'; ?>
