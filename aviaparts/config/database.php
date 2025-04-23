<?php
// Параметри підключення до бази даних
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'aviaparts');

// Встановлення з'єднання з базою даних
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Перевірка з'єднання
if($conn->connect_error) {
    die("Помилка підключення до бази даних: " . $conn->connect_error);
}

// Встановлення кодування UTF-8
$conn->set_charset("utf8");
?>