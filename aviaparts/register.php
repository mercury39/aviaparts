<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Перевірка, чи користувач вже увійшов
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Обробка форми реєстрації
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $error = "";
    
    // Валідація полів
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Будь ласка, заповніть всі поля.";
    } elseif ($password !== $confirm_password) {
        $error = "Паролі не співпадають.";
    } elseif (strlen($password) < 6) {
        $error = "Пароль повинен містити не менше 6 символів.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Невірний формат електронної пошти.";
    } else {
        // Перевірка, чи існує користувач з таким ім'ям або email
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Користувач з таким ім'ям або email вже існує.";
        } else {
            // Хешування пароля
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Додавання нового користувача в базу даних
            $sql = "INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, 'user')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                // Успішна реєстрація
                header("Location: login.php?success=register");
                exit;
            } else {
                $error = "Помилка при реєстрації. Спробуйте ще раз.";
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Реєстрація</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="username">Ім'я користувача</label>
                        <input type="text" class="form-control" id="username" name="username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="form-text text-muted">Пароль повинен містити не менше 6 символів.</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Підтвердження пароля</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Зареєструватися</button>
                </form>
                
                <div class="mt-3">
                    <p>Вже маєте аккаунт? <a href="login.php">Увійти</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>