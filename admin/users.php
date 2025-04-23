<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAccess('admin');

// === ОБРОБКА ВИДАЛЕННЯ КОРИСТУВАЧА ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $uid = (int)$_POST['user_id'];
    // Не даємо адміну видалити самого себе
    if ($uid === $_SESSION['user_id']) {
        $_SESSION['error'] = 'Ви не можете видалити свій власний акаунт.';
    } else {
        // Видаляємо всі коментарі користувача
        $delComments = $conn->prepare("DELETE FROM comments WHERE user_id = ?");
        $delComments->bind_param("i", $uid);
        $delComments->execute();

        // Видаляємо користувача (user_permissions видаляться завдяки FK ON DELETE CASCADE)
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();

        $_SESSION['success'] = 'Користувача успішно видалено.';
        header("Location: users.php?deleted=1");
        exit;
    }
}
// =====================================

// Обробка пошуку користувачів
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$usersQuery = "SELECT user_id, username, email, user_type FROM users";

if (!empty($searchQuery)) {
    $searchParam = "%$searchQuery%";
    $usersQuery .= " WHERE username LIKE ? OR email LIKE ?";
    $stmt = $conn->prepare($usersQuery);
    $stmt->bind_param("ss", $searchParam, $searchParam);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query("$usersQuery ORDER BY username");
}

$selectedUser = null;
$permissions = [];
$allPermissions = [
    // Комплектуючі
    'add_parts'         => 'Додавати комплектуючі',
    'edit_parts'        => 'Редагувати комплектуючі',
    'delete_parts'      => 'Видаляти комплектуючі',
    'manage_categories' => 'Керування категоріями',

    // Форум та коментарі
    'access_forum'      => 'Доступ до форуму',
    'create_threads'    => 'Створювати теми на форумі',
    'reply_forum'       => 'Відповідати у темах форуму',
    'comment'           => 'Коментувати статті',
    'edit_own_posts'    => 'Редагувати власні повідомлення',
    'delete_own_posts'  => 'Видаляти власні повідомлення',
    'moderate_posts'    => 'Модерувати повідомлення інших користувачів',
];

// Обробка вибору користувача
if (isset($_GET['uid'])) {
    $uid = (int)$_GET['uid'];
    $stmt = $conn->prepare("SELECT user_id, username, email, user_type FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $selectedUser = $result->fetch_assoc();

    // Завантажуємо права
    $permStmt = $conn->prepare("SELECT permission, value FROM user_permissions WHERE user_id = ?");
    $permStmt->bind_param("i", $uid);
    $permStmt->execute();
    $res = $permStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $permissions[$row['permission']] = $row['value'];
    }
}

// Обробка збереження змін
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $uid = (int)$_POST['user_id'];
    $newRole = $_POST['role'];
    // Оновлюємо роль
    $stmt = $conn->prepare("UPDATE users SET user_type = ? WHERE user_id = ?");
    $stmt->bind_param("si", $newRole, $uid);
    $stmt->execute();

    // Оновлюємо granular права
    foreach ($allPermissions as $key => $label) {
        $value = isset($_POST['permissions'][$key]) ? 1 : 0;
        $check = $conn->prepare("SELECT id FROM user_permissions WHERE user_id = ? AND permission = ?");
        $check->bind_param("is", $uid, $key);
        $check->execute();
        $checkRes = $check->get_result();

        if ($checkRes->num_rows > 0) {
            $update = $conn->prepare("UPDATE user_permissions SET value = ? WHERE user_id = ? AND permission = ?");
            $update->bind_param("iis", $value, $uid, $key);
            $update->execute();
        } else {
            $insert = $conn->prepare("INSERT INTO user_permissions (user_id, permission, value) VALUES (?, ?, ?)");
            $insert->bind_param("isi", $uid, $key, $value);
            $insert->execute();
        }
    }

    // Редірект із повідомленням
    $searchParam = !empty($searchQuery) ? "&search=" . urlencode($searchQuery) : '';
    header("Location: users.php?uid=$uid&success=1$searchParam");
    exit;
}
?>

<?php include '../includes/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <h4>Користувачі</h4>
            <form method="get" class="mb-3">
                <input type="text" name="search" class="form-control" placeholder="Пошук..." value="<?= htmlspecialchars($searchQuery) ?>">
            </form>
            <div class="list-group user-select">
                <?php while ($u = $users->fetch_assoc()):
                    $isSelected = ($selectedUser && $selectedUser['user_id'] == $u['user_id']);
                    $roleIcon = 'user';
                    $roleClass = 'text-secondary';
                    if ($u['user_type'] == 'admin') { $roleIcon = 'user-shield'; $roleClass = 'text-danger'; }
                    elseif ($u['user_type'] == 'editor') { $roleIcon = 'user-edit'; $roleClass = 'text-primary'; }
                ?>
                    <a href="?uid=<?= $u['user_id'] ?><?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '' ?>" class="list-group-item list-group-item-action <?= $isSelected ? 'active' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="font-weight-bold"><?= htmlspecialchars($u['username']) ?></div>
                                <small><?= htmlspecialchars($u['email']) ?></small>
                            </div>
                            <span class="badge badge-pill <?= $isSelected ? 'badge-light' : $roleClass ?>">
                                <i class="fas fa-<?= $roleIcon ?>"></i>
                            </span>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="col-md-8">
            <?php if ($selectedUser): ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="user_id" value="<?= $selectedUser['user_id'] ?>">

                            <div class="mb-4">
                                <label for="role" class="form-label font-weight-bold"><i class="fas fa-id-badge"></i> Роль користувача</label>
                                <select name="role" id="role" class="form-control">
                                    <option value="user" <?= $selectedUser['user_type'] === 'user' ? 'selected' : '' ?>>Користувач</option>
                                    <option value="editor" <?= $selectedUser['user_type'] === 'editor' ? 'selected' : '' ?>>Редактор</option>
                                    <option value="admin" <?= $selectedUser['user_type'] === 'admin' ? 'selected' : '' ?>>Адміністратор</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label font-weight-bold"><i class="fas fa-lock"></i> Права доступу</label>
                                <div class="row permission-checkboxes">
                                    <?php foreach ($allPermissions as $key => $label): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[<?= $key ?>]" id="perm_<?= $key ?>" <?= !empty($permissions[$key]) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="perm_<?= $key ?>"><?= $label ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="text-right">
                                <button type="submit" name="save" class="btn btn-success"><i class="fas fa-save"></i> Зберегти зміни</button>
                                <button type="submit" name="delete" class="btn btn-danger ml-2" onclick="return confirm('Ви впевнені, що хочете видалити цього користувача? Ця дія незворотна.');"><i class="fas fa-trash-alt"></i> Видалити акаунт</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <?php if (!empty($searchQuery) && $users->num_rows == 0): ?>
                            <p>За вашим запитом нічого не знайдено.</p>
                        <?php else: ?>
                            <p>Виберіть користувача зі списку зліва, щоб переглянути чи редагувати інформацію.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.user-select .list-group-item:not(:last-child) { border-bottom: 1px solid rgba(0,0,0,.125); }
.user-select { max-height: 400px; overflow-y: auto; }
.avatar-placeholder { width: 70px; height: 70px; margin: 0 auto; display: flex; align-items: center; justify-content: center; }
</style>

<?php include '../includes/footer.php'; ?>
