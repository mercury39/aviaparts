<?php
// Включаємо файл функцій
require_once 'includes/functions.php';

// Знищуємо всі дані сесії
session_destroy();

// Перенаправляємо на головну сторінку
header("Location: index.php");
exit;
?>