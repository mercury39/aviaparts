<?php
require_once 'includes/header.php';
// Перевірка наявності ID комплектуючої
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: catalog.php?error=not_found");
    exit;
}
$partId = (int)$_GET['id'];
// Отримання даних про комплектуючу
$part = getPartById($partId);
if (!$part) {
    header("Location: catalog.php?error=not_found");
    exit;
}
// Обробка додавання коментаря
$commentAdded = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isLoggedIn()) {
    if (isset($_POST['comment']) && !empty($_POST['comment'])) {
        $comment = cleanInput($_POST['comment']);
        $userId = $_SESSION['user_id'];
        
        $sql = "INSERT INTO comments (part_id, user_id, comment) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $partId, $userId, $comment);
        
        if ($stmt->execute()) {
            $commentAdded = true;
        }
    }
}
// Отримання коментарів до цієї комплектуючої
$sql = "SELECT c.*, u.username FROM comments c 
        JOIN users u ON c.user_id = u.user_id 
        WHERE c.part_id = ? 
        ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $partId);
$stmt->execute();
$commentsResult = $stmt->get_result();
$comments = [];
if ($commentsResult->num_rows > 0) {
    while ($row = $commentsResult->fetch_assoc()) {
        $comments[] = $row;
    }
}
?>
<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <h1><?php echo htmlspecialchars($part['name']); ?></h1>
            <p class="text-muted">Категорія: <?php echo htmlspecialchars($part['category_name'] ?? 'Не визначено'); ?></p>
            
            <?php if (!empty($part['image'])): ?>
                <img src="<?php echo htmlspecialchars($part['image']); ?>" class="img-fluid mb-4" alt="<?php echo htmlspecialchars($part['name']); ?>">
            <?php else: ?>
                <img src="/aviaparts/assets/images/no-image.jpg" class="img-fluid mb-4" alt="Немає зображення">
            <?php endif; ?>
            
            <h3>Опис</h3>
            <div class="mb-4">
                <?php echo nl2br(htmlspecialchars($part['description'])); ?>
            </div>
            
            <?php if (!empty($part['specifications'])): ?>
                <h3>Технічні характеристики</h3>
                <div class="part-specifications mb-4">
                    <?php echo nl2br(htmlspecialchars($part['specifications'])); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($part['compatibility'])): ?>
                <h3>Сумісність</h3>
                <div class="part-compatibility mb-4">
                    <?php echo nl2br(htmlspecialchars($part['compatibility'])); ?>
                </div>
            <?php endif; ?>
            
            <h3>Коментарі</h3>
            <?php if ($commentAdded): ?>
                <div class="alert alert-success">
                    Ваш коментар успішно додано.
                </div>
            <?php endif; ?>
            
            <?php if (isLoggedIn()): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $partId); ?>">
                            <div class="form-group">
                                <label for="comment">Додати коментар:</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary mt-2">Відправити</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <a href="/aviaparts/login.php?redirect=part.php?id=<?php echo $partId; ?>">Увійдіть</a> або <a href="/aviaparts/register.php">зареєструйтеся</a>, щоб залишити коментар.
                </div>
            <?php endif; ?>
            
            <div class="comments-section mt-4">
                <?php if (!empty($comments)): ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                    <small class="text-muted ms-2">
                                        <?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?>
                                    </small>
                                </div>
                                <?php if (isAdmin() || (isLoggedIn() && $_SESSION['user_id'] == $comment['user_id'])): ?>
                                    <a href="/aviaparts/delete_comment.php?id=<?php echo $comment['comment_id']; ?>&part_id=<?php echo $partId; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Ви впевнені, що хочете видалити цей коментар?');">
                                        Видалити
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-secondary">
                        Поки немає коментарів. Будьте першим, хто залишить коментар!
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Інформація про комплектуючу</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><strong>Артикул:</strong> <?php echo htmlspecialchars($part['serial_number'] ?? 'Не вказано'); ?></li>
                        <li><strong>Виробник:</strong> <?php echo htmlspecialchars($part['manufacturer'] ?? 'Не вказано'); ?></li>
                        <li><strong>Модель:</strong> <?php echo htmlspecialchars($part['model'] ?? 'Не вказано'); ?></li>
                        <?php if (!empty($part['aircraft_name'])): ?>
                            <li><strong>Літак:</strong> <?php echo htmlspecialchars($part['aircraft_name']); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($part['status'])): ?>
                            <li>
                                <strong>Статус:</strong>
                                <span class="badge <?php echo getStatusBadgeClass($part['status']); ?>">
                                    <?php echo getStatusLabel($part['status']); ?>
                                </span>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($part['price'])): ?>
                            <li><strong>Ціна:</strong> <?php echo number_format($part['price'], 2); ?> грн</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php if (isLoggedIn()): ?>
                    <div class="card-footer">
                        <a href="/aviaparts/request_info.php?part_id=<?php echo $partId; ?>" class="btn btn-primary btn-block">Запросити інформацію</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($part['documents'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Документація</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php foreach (json_decode($part['documents'], true) as $doc): ?>
                                <li class="list-group-item">
                                    <a href="<?php echo htmlspecialchars($doc['path']); ?>" target="_blank">
                                        <i class="fas fa-file-pdf"></i> <?php echo htmlspecialchars($doc['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Подібні комплектуючі</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $similarParts = getSimilarParts($part['category_id'], $partId, 3);
                    if (!empty($similarParts)):
                    ?>
                        <div class="list-group">
                            <?php foreach ($similarParts as $similarPart): ?>
                                <a href="/aviaparts/part.php?id=<?php echo $similarPart['part_id']; ?>" class="list-group-item list-group-item-action">
                                    <?php echo htmlspecialchars($similarPart['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Подібних комплектуючих не знайдено.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>