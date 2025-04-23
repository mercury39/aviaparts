<?php
require_once 'includes/header.php';

// Отримання параметрів фільтрації
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

// Формування SQL-запиту з урахуванням фільтрів
$sql = "SELECT p.*, c.name as category_name FROM parts p 
        LEFT JOIN categories c ON p.category_id = c.category_id WHERE 1=1";
$params = [];
$types = "";

if ($categoryId > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryId;
    $types .= "i";
}

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$sql .= " ORDER BY p.name ASC";

// Підготовка та виконання запиту
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$parts = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $parts[] = $row;
    }
}

// Отримання всіх категорій для фільтрації
$categories = getAllCategories();
?>

<h1>Каталог комплектуючих літаків</h1>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Фільтри</h5>
            </div>
            <div class="card-body">
                <form action="catalog.php" method="GET" class="mb-3">
                    <div class="form-group">
                        <label for="search">Пошук:</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Назва або опис...">
                    </div>
                    <div class="form-group">
                        <label for="category">Категорія:</label>
                        <select class="form-control" id="category" name="category">
                            <option value="0">Всі категорії</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php echo ($categoryId == $category['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Застосувати</button>
                    <a href="catalog.php" class="btn btn-outline-secondary btn-block">Скинути фільтри</a>
                </form>
                
                <hr>
                
                <h6>Категорії</h6>
                <ul class="category-list">
                    <li><a href="catalog.php">Всі категорії</a></li>
                    <?php foreach ($categories as $category): ?>
                        <li>
                            <a href="catalog.php?category=<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Порівняння</h5>
            </div>
            <div class="card-body">
                <p>Виберіть до 3-х комплектуючих для порівняння їх характеристик.</p>
                <div id="compare-list">
                    <!-- Список порівняння буде відображатися тут за допомогою JavaScript -->
                </div>
                <a href="compare.php" class="btn btn-info btn-block mt-3" id="compare-button" style="display: none;">
                    Порівняти <span id="compare-counter" class="badge badge-light">0</span>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <?php if (!empty($search) || $categoryId > 0): ?>
            <div class="alert alert-info">
                Результати пошуку 
                <?php if (!empty($search)): ?>
                    за запитом: <strong><?php echo htmlspecialchars($search); ?></strong>
                <?php endif; ?>
                
                <?php if ($categoryId > 0): 
                    $categoryName = '';
                    foreach ($categories as $cat) {
                        if ($cat['category_id'] == $categoryId) {
                            $categoryName = $cat['name'];
                            break;
                        }
                    }
                ?>
                    в категорії: <strong><?php echo htmlspecialchars($categoryName); ?></strong>
                <?php endif; ?>
                
                <a href="catalog.php" class="float-right">Скинути</a>
            </div>
        <?php endif; ?>
        
        <?php if (empty($parts)): ?>
            <div class="alert alert-warning">
                Комплектуючі не знайдені. Спробуйте змінити параметри пошуку.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($parts as $part): ?>
                    <div class="col-md-4">
                        <div class="card card-part h-100 mb-4">
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
                                <button onclick="addToCompare(<?php echo $part['part_id']; ?>, '<?php echo htmlspecialchars(addslashes($part['name'])); ?>')" class="btn btn-outline-secondary float-right" data-toggle="tooltip" title="Додати до порівняння">
                                    <i class="fas fa-balance-scale"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Оновлення списку порівняння
function updateCompareList() {
    const compareList = JSON.parse(localStorage.getItem('compareList')) || [];
    const compareListElement = document.getElementById('compare-list');
    const compareButton = document.getElementById('compare-button');
    
    // Очищення попереднього вмісту
    compareListElement.innerHTML = '';
    
    if (compareList.length > 0) {
        // Створення елементів списку
        compareList.forEach(function(item) {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'd-flex justify-content-between align-items-center mb-2';
            itemDiv.innerHTML = `
                <span>${item.name}</span>
                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCompare(${item.id})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            compareListElement.appendChild(itemDiv);
        });
        
        // Показуємо кнопку порівняння
        compareButton.style.display = 'block';
        document.getElementById('compare-counter').textContent = compareList.length;
    } else {
        // Якщо список порожній
        compareListElement.innerHTML = '<p class="text-muted">Список порівняння порожній</p>';
        compareButton.style.display = 'none';
    }
}

// Виклик функції при завантаженні сторінки
document.addEventListener('DOMContentLoaded', function() {
    updateCompareList();
    
    // Додавання обробників для кнопок FontAwesome
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js';
    document.head.appendChild(script);
});
</script>

<?php
require_once 'includes/footer.php';
?>