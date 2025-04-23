<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
include 'includes/header.php';

// Перевірка авторизації
if (!isLoggedIn()) {
    header("Location: login.php?redirect=forum.php");
    exit;
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="forum.php">Форум</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Створення нової теми</li>
                </ol>
            </nav>
            
            <div class="card">
                <div class="card-header bg-light">
                    <h2>Створення нової теми</h2>
                </div>
                <div class="card-body">
                    <form action="actions/add_topic.php" method="post">
                        <div class="mb-3">
                            <label for="title" class="form-label">Назва теми</label>
                            <input type="text" class="form-control" id="title" name="title" required minlength="5" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Текст першого повідомлення</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Створити тему</button>
                            <a href="forum.php" class="btn btn-outline-secondary">Скасувати</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>