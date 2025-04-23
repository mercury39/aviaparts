<?php
require_once 'includes/header.php';

// Отримуємо останні додані комплектуючі
$sql = "SELECT p.*, c.name as category_name FROM parts p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        ORDER BY p.created_at DESC LIMIT 4";
$result = $conn->query($sql);
$latestParts = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $latestParts[] = $row;
    }
}

// Отримуємо категорії для відображення
$categories = getAllCategories();
?>

<div class="hero-section">
    <div class="container">
        <h1>Ласкаво просимо до AviaParts</h1>
        <p>Єдина платформа для класифікації та пошуку комплектуючих літаків</p>
        <a href="/aviaparts/catalog.php" class="btn btn-primary btn-lg">Переглянути каталог</a>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="feature-box">
                <i class="fas fa-search mb-3"></i>
                <h3>Зручний пошук</h3>
                <p>Знайдіть потрібні комплектуючі за допомогою нашої зручної системи пошуку та фільтрації</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="feature-box">
                <i class="fas fa-exchange-alt mb-3"></i>
                <h3>Порівняння деталей</h3>
                <p>Порівняйте різні комплектуючі, щоб вибрати оптимальний варіант для вашого літака</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="feature-box">
                <i class="fas fa-comments mb-3"></i>
                <h3>Спільнота експертів</h3>
                <p>Отримайте допомогу від спільноти професіоналів та ентузіастів авіації</p>
            </div>
        </div>
    </div>
    
    <h2 class="mt-5 mb-4">Останні додані комплектуючі</h2>
    <div class="row">
        <?php if (empty($latestParts)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    На даний момент в каталозі немає комплектуючих. Скоро вони з'являться!
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($latestParts as $part): ?>
                <div class="col-md-3">
                    <div class="card card-part h-100">
                        <?php if (!empty($part['image'])): ?>
                            <img src="<?php echo htmlspecialchars($part['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($part['name']); ?>">
                        <?php else: ?>
                            <img src="/aviaparts/assets/images/no-image.jpg" class="card-img-top" alt="Немає зображення">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($part['name']); ?></h5>
                            <p class="card-text text-muted">Категорія: <?php echo htmlspecialchars($part['category_name'] ?? 'Не визначено'); ?></p>
                            <p class="card-text"><?php echo mb_substr(htmlspecialchars($part['description']), 0, 100); ?>...</p>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="/aviaparts/part.php?id=<?php echo $part['part_id']; ?>" class="btn btn-primary">Детальніше</a>
                            <button onclick="addToCompare(<?php echo $part['part_id']; ?>, '<?php echo htmlspecialchars($part['name']); ?>')" class="btn btn-outline-secondary float-right" data-toggle="tooltip" title="Додати до порівняння">
                                <i class="fas fa-balance-scale"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <h2 class="mt-5 mb-4">Категорії комплектуючих</h2>
    <div class="row">
        <?php if (empty($categories)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    На даний момент немає доступних категорій. Скоро вони з'являться!
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($category['description']); ?></p>
                            <a href="/aviaparts/catalog.php?category=<?php echo $category['category_id']; ?>" class="btn btn-outline-primary">Переглянути</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-8 offset-md-2">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h3>Знайшли те, що шукали?</h3>
                    <p>Зареєструйтеся зараз, щоб отримати доступ до всіх функцій нашого сайту</p>
                    <a href="/aviaparts/register.php" class="btn btn-primary btn-lg">Зареєструватися</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>