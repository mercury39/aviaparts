<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Перевірка, чи користувач вже увійшов
if (isLoggedIn()) {
    if ($_SESSION['user_type'] === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// Обробка помилки про блокування
$error = "";
$ban_reason = "";

if (isset($_GET['error']) && $_GET['error'] === 'banned') {
    $error = "Ваш обліковий запис було заблоковано адміністратором.";
    
    // Перевіряємо, чи є збережена причина блокування в сесії
    if (isset($_SESSION['ban_reason'])) {
        $ban_reason = $_SESSION['ban_reason'];
        unset($_SESSION['ban_reason']); // Очищуємо після використання
    }
}

// Обробка форми логіну
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Будь ласка, заповніть всі поля.";
    } else {
        // Оновлений запит, щоб отримати також статус користувача та причину блокування
        $sql = "SELECT user_id, username, password, user_type, status, ban_reason FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Перевірка чи не заблокований користувач
                if (isset($user['status']) && $user['status'] === 'banned') {
                    $error = "Ваш обліковий запис заблоковано адміністратором.";
                    $ban_reason = $user['ban_reason'] ?? 'Причина не вказана';
                } else {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = $user['user_type'];

                    // Редирект в залежності від ролі
                    if ($user['user_type'] === 'admin') {
                        header("Location: admin/index.php");
                    } else {
                        header("Location: index.php?success=login");
                    }
                    exit;
                }
            } else {
                $error = "Невірний пароль.";
            }
        } else {
            $error = "Користувача не знайдено.";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Вхід до системи</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                            <?php if (!empty($ban_reason)): ?>
                                <hr>
                                <p class="mb-0"><strong>Причина:</strong> <?php echo htmlspecialchars($ban_reason); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Ім'я користувача</label>
                            <input type="text" class="form-control" name="username" id="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" class="form-control" name="password" id="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Увійти</button>
                    </form>
                    <div class="mt-3">
                        <p>Ще не маєте аккаунту? <a href="register.php">Зареєструватися</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>