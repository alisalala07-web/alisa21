<?php
session_start();
require_once __DIR__ . '/config.php';

date_default_timezone_set('Asia/Yakutsk');

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function showMessage($title, $text, $type = 'success') {
    $icon = $type === 'success' ? '✅' : '❌';
    $color = $type === 'success' ? 'var(--accent)' : '#ff6b6b';
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= h($title) ?></title>
        <link rel="stylesheet" href="/css/style.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    </head>
    <body>
        <div class="message-page">
            <div class="message-box card <?= $type ?>">
                <div class="icon"><?= $icon ?></div>
                <h2 style="color:<?= $color ?>"><?= h($title) ?></h2>
                <p><?= h($text) ?></p>
                <a href="/survey/" class="btn btn-primary">Вернуться к опросу</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

function post($name, $default = '') {
    return isset($_POST[$name]) ? trim($_POST[$name]) : $default;
}

function postArray($name) {
    if (!isset($_POST[$name]) || !is_array($_POST[$name])) {
        return [];
    }
    return array_values(array_filter(array_map('trim', $_POST[$name]), function($v) {
        return $v !== '';
    }));
}

function validateOption($value, $options) {
    return in_array($value, $options, true);
}

function checkLength($str, $max = 1000) {
    return mb_strlen((string)$str, 'UTF-8') <= $max;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    showMessage('Ошибка', 'Форма должна отправляться методом POST.', 'error');
}

if (post('website') !== '') {
    showMessage('Ошибка отправки', 'Форма не прошла проверку на спам.', 'error');
}

// проверка для повторной отправки

//  if (isset($_COOKIE['survey_sent'])) {
//      showMessage('Повторная отправка запрещена', 'Вы уже отправляли ответ. Повторная отправка доступна через 24 часа.', 'error');
//  }


if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    showMessage('Ошибка безопасности', 'CSRF-токен не прошёл проверку. Обновите страницу и попробуйте снова.', 'error');
}

$requiredFields = ['q1', 'q3', 'q6', 'q11', 'q13', 'q14', 'q15', 'q17'];
foreach ($requiredFields as $field) {
    if (post($field) === '') {
        showMessage('Заполните обязательные поля', "Не заполнено поле: {$field}", 'error');
    }
}

$options = SURVEY_OPTIONS;

$singleFields = ['q1', 'q2', 'q3', 'q6', 'q11', 'q12', 'q13', 'q14', 'q15', 'q17'];
foreach ($singleFields as $field) {
    if (post($field) !== '' && !validateOption(post($field), $options[$field])) {
        showMessage('Ошибка в форме', "Недопустимое значение поля: {$field}", 'error');
    }
}

$multiFields = ['q4', 'q5'];
foreach ($multiFields as $field) {
    foreach (postArray($field) as $value) {
        if (!validateOption($value, $options[$field])) {
            showMessage('Ошибка в форме', "Недопустимое значение поля: {$field}", 'error');
        }
    }
}

if (post('q1') === 'Закончил обучение' && post('q2') === '') {
    showMessage('Заполните вопрос 2', 'Если обучение завершено, укажите причину.', 'error');
}

if (post('q1') !== 'Закончил обучение' && post('q2') !== '') {
    showMessage('Ошибка в форме', 'Вопрос 2 заполняется только для завершивших обучение.', 'error');
}

if (post('q2') === 'Другое' && post('q2_other') === '') {
    showMessage('Заполните поле «Другое»', 'Укажите свой вариант причины завершения обучения.', 'error');
}

if (in_array('Другое', postArray('q5'), true) && post('q5_other') === '') {
    showMessage('Заполните поле «Другое»', 'Укажите свой вариант ответа в вопросе 5.', 'error');
}

if (post('q12') === 'Да' && post('q12_text') === '') {
    showMessage('Заполните предложение', 'Укажите, какие курсы или воркшопы вы хотите видеть.', 'error');
}

if (post('q17') === 'Другое' && post('q17_other') === '') {
    showMessage('Заполните поле «Другое»', 'Укажите свой вариант ответа в вопросе 17.', 'error');
}

if (post('q2') !== 'Другое') {
    $_POST['q2_other'] = '';
}
if (!in_array('Другое', postArray('q5'), true)) {
    $_POST['q5_other'] = '';
}
if (post('q12') !== 'Да') {
    $_POST['q12_text'] = '';
}
if (post('q17') !== 'Другое') {
    $_POST['q17_other'] = '';
}

$textFields = ['q2_other', 'q5_other', 'q7', 'q8', 'q9', 'q10', 'q12_text', 'q16', 'q17_other'];
foreach ($textFields as $field) {
    if (!checkLength(post($field))) {
        showMessage('Слишком длинный ответ', "Поле «{$field}» не должно быть длиннее 1000 символов.", 'error');
    }
}

$nextId = 1;
if (file_exists(CSV_FILE)) {
    $lines = file(CSV_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $nextId = count($lines);
}

$row = [
    'id' => $nextId,
    'submitted_at' => date('Y-m-d H:i:s'),
    'q1_status' => post('q1'),
    'q2_finish_reason' => post('q2'),
    'q2_other' => post('q2_other'),
    'q3_life_impact' => post('q3'),
    'q4_career_changes' => implode('; ', postArray('q4')),
    'q5_work_help' => implode('; ', postArray('q5')),
    'q5_other' => post('q5_other'),
    'q6_expectations' => post('q6'),
    'q7_expectations_text' => post('q7'),
    'q8_liked' => post('q8'),
    'q9_problems' => post('q9'),
    'q10_suggestions' => post('q10'),
    'q11_community_support' => post('q11'),
    'q12_extra_courses' => post('q12'),
    'q12_text' => post('q12_text'),
    'q13_gender' => post('q13'),
    'q14_age' => post('q14'),
    'q15_family' => post('q15'),
    'q16_work_or_study' => post('q16'),
    'q17_source' => post('q17'),
    'q17_other' => post('q17_other')
];

$lock = fopen(LOCK_FILE, 'c');
if (!$lock || !flock($lock, LOCK_EX)) {
    showMessage('Ошибка записи', 'Не удалось заблокировать файл для записи. Попробуйте позже.', 'error');
}

$needHeader = !file_exists(CSV_FILE) || filesize(CSV_FILE) === 0;

$csv = fopen(CSV_FILE, 'a');
if (!$csv) {
    flock($lock, LOCK_UN);
    showMessage('Ошибка записи', 'Не удалось открыть CSV-файл.', 'error');
}

if ($needHeader) {
    fputcsv($csv, CSV_COLUMNS);
}

$csvData = [];
foreach (CSV_COLUMNS as $col) {
    $csvData[] = $row[$col] ?? '';
}
fputcsv($csv, $csvData);
fclose($csv);

$jsonData = [];
if (file_exists(JSON_FILE) && filesize(JSON_FILE) > 0) {
    $content = file_get_contents(JSON_FILE);
    if ($content !== false) {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $jsonData = $decoded;
        }
    }
}
$jsonData[] = $row;
file_put_contents(JSON_FILE, json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

flock($lock, LOCK_UN);
fclose($lock);

// закомментировано для теста
setcookie('survey_sent', '1', time() + 86400, '/', '', false, true);

unset($_SESSION['csrf']);

showMessage('Благодарим за обратную связь!', 'Ваш ответ успешно сохранён. Спасибо за участие в опросе!', 'success');