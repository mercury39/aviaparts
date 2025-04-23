<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/aviaparts/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/aviaparts/includes/functions.php';

$userType = getUserType();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AviaParts - Класифікація та комплектуючі літаків</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="/aviaparts/assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="/aviaparts/">AviaParts</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/aviaparts/">Головна</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/aviaparts/catalog.php">Каталог</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/aviaparts/forum.php">Форум</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/aviaparts/about.php">Про сайт</a>
                        </li>
                        
                        <?php if ($userType === 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Адміністрування
                            </a>
                            <div class="dropdown-menu" aria-labelledby="adminDropdown">
                                <a class="dropdown-item" href="/aviaparts/admin/index.php">Панель адміністратора</a>
                            </div>
                        </li>
                        <?php elseif ($userType === 'editor'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="editorDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Редагування
                            </a>
                            <div class="dropdown-menu" aria-labelledby="editorDropdown">
                                <a class="dropdown-item" href="/aviaparts/editor/index.php">Панель редактора</a>
                                
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <form class="form-inline my-2 my-lg-0 mr-3" action="/aviaparts/catalog.php" method="GET">
                        <input class="form-control mr-sm-2" type="search" name="search" placeholder="Пошук комплектуючих" aria-label="Search">
                        <button class="btn btn-outline-light my-2 my-sm-0" type="submit">Пошук</button>
                    </form>
                    
                    <ul class="navbar-nav">
                        <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php echo htmlspecialchars($currentUser['username']); ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="/aviaparts/user/profile.php">Мій профіль</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="/aviaparts/logout.php">Вихід</a>
                            </div>
                        </li>
                        <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/aviaparts/login.php">Вхід</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/aviaparts/register.php">Реєстрація</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container my-4">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    $error = $_GET['error'];
                    if ($error === 'access_denied') {
                        echo 'У вас немає прав доступу до цієї сторінки.';
                    } elseif ($error === 'not_found') {
                        echo 'Запитаний ресурс не знайдено.';
                    } else {
                        echo 'Сталася помилка. Спробуйте ще раз.';
                    }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    $success = $_GET['success'];
                    if ($success === 'login') {
                        echo 'Ви успішно увійшли в свій аккаунт.';
                    } elseif ($success === 'register') {
                        echo 'Реєстрація пройшла успішно. Тепер ви можете увійти в свій аккаунт.';
                    } elseif ($success === 'update') {
                        echo 'Дані успішно оновлено.';
                    } elseif ($success === 'create') {
                        echo 'Запис успішно створено.';
                    } elseif ($success === 'delete') {
                        echo 'Запис успішно видалено.';
                    }
                ?>
            </div>
        <?php endif; ?>