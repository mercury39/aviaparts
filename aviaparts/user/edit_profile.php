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
$successMessage = '';
$errorMessage = '';

// Обробка форми зміни профілю
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Обробка зміни email
    if (isset($_POST['email'])) {
        $email = cleanInput($_POST['email']);
        
        if (empty($email)) {
            $errorMessage = "Email не може бути порожнім.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Невірний формат електронної пошти.";
        } else {
            // Перевірка, чи існує користувач з таким email (окрім поточного користувача)
            $sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $email, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errorMessage = "Користувач з таким email вже існує.";
            } else {
                // Оновлення email
                $sql = "UPDATE users SET email = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $email, $userId);
                
                if ($stmt->execute()) {
                    $successMessage = "Email успішно оновлено.";
                    // Оновлюємо дані користувача
                    $user = getCurrentUser();
                } else {
                    $errorMessage = "Помилка при оновленні email. Спробуйте ще раз.";
                }
            }
        }
    }
    
    // Обробка зміни пароля
    if (isset($_POST['current_password']) && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = "Всі поля для зміни пароля повинні бути заповнені.";
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = "Нові паролі не співпадають.";
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = "Новий пароль повинен містити не менше 6 символів.";
        } else {
            // Перевірка поточного пароля
            $sql = "SELECT password FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $userData = $result->fetch_assoc();
                
                if (!password_verify($currentPassword, $userData['password'])) {
                    $errorMessage = "Поточний пароль невірний.";
                } else {
                    // Хешування нового пароля
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    // Оновлення пароля
                    $sql = "UPDATE users SET password = ? WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $hashedPassword, $userId);
                    
                    if ($stmt->execute()) {
                        $successMessage = "Пароль успішно змінено.";
                    } else {
                        $errorMessage = "Помилка при зміні пароля. Спробуйте ще раз.";
                    }
                }
            } else {
                $errorMessage = "Помилка при отриманні даних користувача.";
            }
        }
    }
}

// Заголовок сторінки
$pageTitle = "Редагування профілю: " . htmlspecialchars($user['username']);
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
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Мій профіль
                    </a>
                    <a href="edit_profile.php" class="list-group-item list-group-item-action active">
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
        
        <!-- Основний контент редагування профілю -->
        <div class="col-md-9">
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
            
            <!-- Редагування інформації профілю -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Редагування профілю</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Ім'я користувача</label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled readonly>
                            <small class="text-muted">Ім'я користувача не може бути змінено.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Оновити інформацію</button>
                    </form>
                </div>
            </div>
            
            <!-- Зміна пароля -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Зміна пароля</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Поточний пароль</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Новий пароль</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <small class="text-muted">Пароль повинен містити не менше 6 символів.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Підтвердження нового пароля</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Змінити пароль</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>