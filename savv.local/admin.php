<?php
session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf'] ?? '';
    
    if (!hash_equals($_SESSION['admin_csrf'], $token)) {
        $error = 'Сессия устарела. Обновите страницу.';
    } elseif ($login === ADMIN_LOGIN && $password === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}

if (!empty($_SESSION['admin']) && isset($_GET['download'])) {
    if (!file_exists(CSV_FILE) || filesize(CSV_FILE) === 0) {
        die('Файл с ответами пока не создан.');
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="responses_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    readfile(CSV_FILE);
    exit;
}

$rows = [];
if (!empty($_SESSION['admin']) && file_exists(CSV_FILE) && filesize(CSV_FILE) > 0) {
    $file = fopen(CSV_FILE, 'r');
    while (($row = fgetcsv($file)) !== false) {
        $rows[] = $row;
    }
    fclose($file);
}

$totalResponses = max(0, count($rows) - 1);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | Школа 21. Якутия</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
</head>
<body>

<header class="header">
    <a class="logo" href="/">Школа<span>21</span></a>
    <nav class="nav">
        <a href="/">Главная</a>
        <a href="/survey/">Опрос</a>
        <a href="/admin.php" class="active">Админ</a>
    </nav>
</header>

<main>
    <?php if (empty($_SESSION['admin'])): ?>
        <div class="card" style="max-width:440px;margin-top:60px;">
            <h2>🔐 Вход администратора</h2>
            
            <?php if ($error): ?>
                <p style="color:#ff6b6b;background:rgba(255,70,70,0.1);padding:10px 16px;border-radius:8px;"><?= h($error) ?></p>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['admin_csrf']) ?>">
                
                <div class="form-group">
                    <label for="login">Логин</label>
                    <input type="text" name="login" id="login" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" name="password" id="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Войти</button>
            </form>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="admin-header">
                <div>
                    <h2>📊 Ответы на опрос</h2>
                    <p style="color:var(--text-muted);font-size:14px;">
                        Всего ответов: <strong><?= $totalResponses ?></strong>
                    </p>
                </div>
                <div class="admin-actions">
                    <?php if ($totalResponses > 0): ?>
                        <a href="?download=1" class="btn btn-primary btn-sm">⬇️ Скачать CSV</a>
                    <?php endif; ?>
                    <a href="?logout=1" class="btn btn-danger btn-sm">🚪 Выйти</a>
                </div>
            </div>
        </div>

        <div class="card card-sm">
            <?php if ($totalResponses === 0): ?>
                <p style="text-align:center;color:var(--text-muted);padding:40px 0;">
                    📭 Ответов пока нет. Будьте первым!
                </p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <?php foreach ($rows[0] as $header): ?>
                                    <th><?= h($header) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 1; $i < count($rows); $i++): ?>
                                <tr>
                                    <?php foreach ($rows[$i] as $cell): ?>
                                        <td><?= h($cell) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <p style="color:var(--text-muted);font-size:13px;margin-top:12px;">
                    📌 Показано <?= $totalResponses ?> записей
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<footer class="footer">
    <span>© 2026 АНО «Школа 21. Якутия»</span>
    <a href="/">На главную</a>
</footer>

</body>
</html>