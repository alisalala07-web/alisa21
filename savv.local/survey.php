<?php
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/config.php';

$alreadySubmitted = isset($_COOKIE['survey_sent']);
// $alreadySubmitted = false; 

function getUserResponse() {
    global $CSV_FILE;
    
    if (!file_exists($CSV_FILE) || filesize($CSV_FILE) === 0) {
        return null;
    }
    
    $file = fopen($CSV_FILE, 'r');
    $headers = fgetcsv($file);
    $rows = [];
    
    while (($row = fgetcsv($file)) !== false) {
        $rows[] = array_combine($headers, $row);
    }
    fclose($file);
    
    return !empty($rows) ? end($rows) : null;
}

$userData = getUserResponse();
$hasResponse = ($userData !== null);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Опрос учащихся | Школа 21. Якутия</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        .answered-field {
            background: rgba(37, 255, 122, 0.08) !important;
            border-color: var(--accent) !important;
            padding: 8px 14px;
            border-radius: 8px;
            margin-top: 4px;
            color: var(--text-light);
        }
        .answered-label {
            color: var(--accent);
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
        .response-badge {
            display: inline-block;
            padding: 4px 12px;
            background: var(--accent-dim);
            border: 1px solid var(--accent-border);
            border-radius: 50px;
            font-size: 12px;
            color: var(--accent);
            margin-left: 10px;
        }
        .readonly-field {
            opacity: 0.7;
            pointer-events: none;
        }
        .readonly-field input,
        .readonly-field textarea,
        .readonly-field .option-group label {
            opacity: 0.6;
            pointer-events: none;
        }
        .already-answered-banner {
            background: var(--accent-dim);
            border: 1px solid var(--accent-border);
            border-radius: 12px;
            padding: 16px 24px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .already-answered-banner p {
            color: var(--text-light);
            margin: 0;
            font-size: 15px;
        }
        .already-answered-banner .badge {
            color: var(--accent);
            font-weight: 600;
        }
    </style>
</head>
<body>

<header class="header">
    <a class="logo" href="/">Школа<span>21</span></a>
    <nav class="nav">
        <a href="/">Главная</a>
        <a href="/survey/" class="active">Опрос</a>
        <a href="/admin.php">Админ</a>
    </nav>
</header>

<main>
    <section class="hero" style="padding-bottom:20px;">
        <div class="hero-badge">Анонимный опрос</div>
        <h1>Ваше мнение <span class="highlight">важно</span></h1>
        <p>Помогите нам сделать обучение в «Школе 21» ещё лучше. Опрос займёт всего <strong>5–7 минут</strong>.</p>
        <div class="hero-stats">
            <span class="stat"><strong>17</strong> вопросов</span>
            <span class="stat">🔒 Анонимно</span>
            <span class="stat">⏱️ 5–7 минут</span>
        </div>
    </section>

    <?php if ($hasResponse): ?>
        <div class="card">
            <div class="already-answered-banner">
                <p>✅ <span class="badge">Вы уже проходили опрос</span> — вот ваши ответы (отправьте снова для теста):</p>
                <span style="color:var(--text-muted);font-size:14px;">📅 <?= htmlspecialchars($userData['submitted_at'] ?? '') ?></span>
            </div>
        </div>
    <?php endif; ?>

    <form class="survey-form" action="/process.php" method="POST" id="surveyForm" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
        
        <div class="hp-field">
            <label>Не заполняйте это поле <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <div class="card">
            <h2>📌 Блок 1. Ваш статус в Школе 21</h2>
            
            <div class="question">
                <span class="question-label">
                    1. Какой этап обучения вы прошли или проходите?
                    <span class="required">*</span>
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <div class="option-group <?= $hasResponse ? 'readonly-field' : '' ?>">
                    <label>
                        <input type="radio" name="q1" value="Только вступительный интенсив" required 
                            <?= ($hasResponse && $userData['q1_status'] == 'Только вступительный интенсив') ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span>Только вступительный интенсив (отбор)</span>
                    </label>
                    <label>
                        <input type="radio" name="q1" value="Участник основы"
                            <?= ($hasResponse && $userData['q1_status'] == 'Участник основы') ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span>Участник «основы» (продолжаю обучение)</span>
                    </label>
                    <label>
                        <input type="radio" name="q1" value="Закончил обучение" data-finished
                            <?= ($hasResponse && $userData['q1_status'] == 'Закончил обучение') ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span>Закончил обучение</span>
                    </label>
                    <?php if ($hasResponse && $userData['q1_status']): ?>
                        <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q1_status']) ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="question <?= ($hasResponse && $userData['q1_status'] != 'Закончил обучение') ? 'hidden' : '' ?>" id="q2Block">
                <span class="question-label">
                    2. Если вы завершили обучение, укажите причину:
                    <span class="required">*</span>
                    <span class="hint">Заполняется только при выборе «Закончил обучение»</span>
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <div class="option-group <?= $hasResponse ? 'readonly-field' : '' ?>">
                    <label>
                        <input type="radio" name="q2" value="Успешно завершил полный курс"
                            <?= ($hasResponse && $userData['q2_finish_reason'] == 'Успешно завершил полный курс') ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span>Успешно завершил полный курс</span>
                    </label>
                    <label>
                        <input type="radio" name="q2" value="Прервал по личным обстоятельствам"
                            <?= ($hasResponse && $userData['q2_finish_reason'] == 'Прервал по личным обстоятельствам') ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span>Прервал по личным обстоятельствам</span>
                    </label>
                    <label>
                        <input type="radio" name="q2" value="Не совместил с работой/учебой"
                            <?= ($hasResponse && $userData['q2_finish_reason'] == 'Не совместил с работой/учебой') ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span>Не совместил с работой/учебой</span>
                    </label>
                    <label>
                        <input type="radio" name="q2" value="Другое" data-other="q2_other"
                            <?= ($hasResponse && $userData['q2_finish_reason'] == 'Другое') ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span>Другое</span>
                    </label>
                    <input type="text" class="other-input" name="q2_other" id="q2_other" 
                        placeholder="Укажите причину" 
                        value="<?= $hasResponse ? htmlspecialchars($userData['q2_other'] ?? '') : '' ?>"
                        <?= ($hasResponse && $userData['q2_finish_reason'] != 'Другое') ? 'disabled' : '' ?>>
                    <?php if ($hasResponse && $userData['q2_finish_reason']): ?>
                        <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q2_finish_reason']) ?></strong>
                            <?php if (!empty($userData['q2_other'])): ?>
                                (<?= htmlspecialchars($userData['q2_other']) ?>)
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>🚀 Блок 2. Влияние на жизнь и карьеру</h2>

            <div class="question">
                <span class="question-label">
                    3. Насколько обучение повлияло на вашу жизнь?
                    <span class="required">*</span>
                    <span class="hint">1 — совсем не повлияло, 5 — кардинально изменило</span>
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <div class="scale-group <?= $hasResponse ? 'readonly-field' : '' ?>">
                    <label><input type="radio" name="q3" value="1" required
                        <?= ($hasResponse && $userData['q3_life_impact'] == '1') ? 'checked' : '' ?>
                        <?= $hasResponse ? 'disabled' : '' ?>><span>1</span></label>
                    <label><input type="radio" name="q3" value="2"
                        <?= ($hasResponse && $userData['q3_life_impact'] == '2') ? 'checked' : '' ?>
                        <?= $hasResponse ? 'disabled' : '' ?>><span>2</span></label>
                    <label><input type="radio" name="q3" value="3"
                        <?= ($hasResponse && $userData['q3_life_impact'] == '3') ? 'checked' : '' ?>
                        <?= $hasResponse ? 'disabled' : '' ?>><span>3</span></label>
                    <label><input type="radio" name="q3" value="4"
                        <?= ($hasResponse && $userData['q3_life_impact'] == '4') ? 'checked' : '' ?>
                        <?= $hasResponse ? 'disabled' : '' ?>><span>4</span></label>
                    <label><input type="radio" name="q3" value="5"
                        <?= ($hasResponse && $userData['q3_life_impact'] == '5') ? 'checked' : '' ?>
                        <?= $hasResponse ? 'disabled' : '' ?>><span>5</span></label>
                </div>
                <?php if ($hasResponse && $userData['q3_life_impact']): ?>
                    <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q3_life_impact']) ?></strong></div>
                <?php endif; ?>
            </div>

            <div class="question">
                <span class="question-label">
                    4. Как изменилась ваша карьерная ситуация?
                    <span class="hint">Можно выбрать несколько</span>
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <div class="option-group <?= $hasResponse ? 'readonly-field' : '' ?>">
                    <?php 
                    $q4Values = $hasResponse ? explode('; ', $userData['q4_career_changes'] ?? '') : [];
                    $q4Options = [
                        'Сменил работу / компанию',
                        'Повысили в должности',
                        'Выросла зарплата',
                        'Получил первую работу в IT',
                        'Сменил сферу деятельности',
                        'Смог работать удалённо / переехал',
                        'Заметных изменений не произошло'
                    ];
                    foreach ($q4Options as $opt): 
                    ?>
                    <label>
                        <input type="checkbox" name="q4[]" value="<?= htmlspecialchars($opt) ?>"
                            <?= (in_array($opt, $q4Values)) ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span><?= htmlspecialchars($opt) ?></span>
                    </label>
                    <?php endforeach; ?>
                    <?php if ($hasResponse && !empty($userData['q4_career_changes'])): ?>
                        <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q4_career_changes']) ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="question">
                <span class="question-label">
                    5. Как обучение помогло вам в текущей работе?
                    <span class="hint">Можно выбрать несколько</span>
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <div class="option-group <?= $hasResponse ? 'readonly-field' : '' ?>">
                    <?php 
                    $q5Values = $hasResponse ? explode('; ', $userData['q5_work_help'] ?? '') : [];
                    $q5Options = [
                        'Стал лучше решать рабочие задачи',
                        'Научился новым технологиям/подходам',
                        'Улучшил навыки работы в команде и самоорганизации',
                        'Пока не работаю / работа не связана с IT',
                        'Другое'
                    ];
                    foreach ($q5Options as $opt): 
                    ?>
                    <label>
                        <input type="checkbox" name="q5[]" value="<?= htmlspecialchars($opt) ?>"
                            <?= (in_array($opt, $q5Values)) ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>
                            <?= ($opt == 'Другое') ? 'data-other="q5_other"' : '' ?>>
                        <span><?= htmlspecialchars($opt) ?></span>
                    </label>
                    <?php endforeach; ?>
                    <input type="text" class="other-input" name="q5_other" id="q5_other" 
                        placeholder="Ваш вариант" 
                        value="<?= $hasResponse ? htmlspecialchars($userData['q5_other'] ?? '') : '' ?>"
                        <?= ($hasResponse && !in_array('Другое', $q5Values)) ? 'disabled' : '' ?>>
                    <?php if ($hasResponse && !empty($userData['q5_work_help'])): ?>
                        <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q5_work_help']) ?></strong>
                            <?php if (!empty($userData['q5_other'])): ?>
                                (<?= htmlspecialchars($userData['q5_other']) ?>)
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="question">
                <span class="question-label">
                    6. Оцените, насколько оправдались ваши ожидания:
                    <span class="required">*</span>
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <div class="option-group <?= $hasResponse ? 'readonly-field' : '' ?>">
                    <?php 
                    $q6Options = [
                        'Полностью оправдались',
                        'Частично оправдались',
                        'Скорее не оправдались',
                        'Полностью не оправдались',
                        'Затрудняюсь ответить'
                    ];
                    foreach ($q6Options as $opt): 
                    ?>
                    <label>
                        <input type="radio" name="q6" value="<?= htmlspecialchars($opt) ?>" required
                            <?= ($hasResponse && $userData['q6_expectations'] == $opt) ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span><?= htmlspecialchars($opt) ?></span>
                    </label>
                    <?php endforeach; ?>
                    <?php if ($hasResponse && $userData['q6_expectations']): ?>
                        <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q6_expectations']) ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="question">
                <span class="question-label">
                    7. Что конкретно оправдало или не оправдало ожидания?
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <textarea name="q7" maxlength="1000" placeholder="Расскажите подробнее..." 
                    <?= $hasResponse ? 'disabled' : '' ?>><?= $hasResponse ? htmlspecialchars($userData['q7_expectations_text'] ?? '') : '' ?></textarea>
                <?php if ($hasResponse && !empty($userData['q7_expectations_text'])): ?>
                    <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q7_expectations_text']) ?></strong></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h2>💡 Блок 3. Предложения по улучшению</h2>

            <div class="question">
                <span class="question-label">
                    8. Что вам больше всего понравилось в обучении?
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <textarea name="q8" maxlength="1000" placeholder="Ваш ответ..." 
                    <?= $hasResponse ? 'disabled' : '' ?>><?= $hasResponse ? htmlspecialchars($userData['q8_liked'] ?? '') : '' ?></textarea>
                <?php if ($hasResponse && !empty($userData['q8_liked'])): ?>
                    <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q8_liked']) ?></strong></div>
                <?php endif; ?>
            </div>

            <div class="question">
                <span class="question-label">
                    9. Какие проблемы или неудобства вы испытывали?
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <textarea name="q9" maxlength="1000" placeholder="Ваш ответ..." 
                    <?= $hasResponse ? 'disabled' : '' ?>><?= $hasResponse ? htmlspecialchars($userData['q9_problems'] ?? '') : '' ?></textarea>
                <?php if ($hasResponse && !empty($userData['q9_problems'])): ?>
                    <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q9_problems']) ?></strong></div>
                <?php endif; ?>
            </div>

            <div class="question">
                <span class="question-label">
                    10. Какие изменения вы бы предложили?
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <textarea name="q10" maxlength="1000" placeholder="Ваш ответ..." 
                    <?= $hasResponse ? 'disabled' : '' ?>><?= $hasResponse ? htmlspecialchars($userData['q10_suggestions'] ?? '') : '' ?></textarea>
                <?php if ($hasResponse && !empty($userData['q10_suggestions'])): ?>
                    <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q10_suggestions']) ?></strong></div>
                <?php endif; ?>
            </div>

            <div class="question">
                <span class="question-label">
                    11. Как вы оцениваете поддержку сообщества?
                    <span class="required">*</span>
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <div class="option-group <?= $hasResponse ? 'readonly-field' : '' ?>">
                    <?php 
                    $q11Options = [
                        'Отлично, всегда помогали',
                        'Хорошо, но иногда не хватало обратной связи',
                        'Удовлетворительно',
                        'Плохо, поддержка была слабой'
                    ];
                    foreach ($q11Options as $opt): 
                    ?>
                    <label>
                        <input type="radio" name="q11" value="<?= htmlspecialchars($opt) ?>" required
                            <?= ($hasResponse && $userData['q11_community_support'] == $opt) ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span><?= htmlspecialchars($opt) ?></span>
                    </label>
                    <?php endforeach; ?>
                    <?php if ($hasResponse && $userData['q11_community_support']): ?>
                        <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q11_community_support']) ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="question">
                <span class="question-label">
                    12. Хотели бы вы видеть дополнительные курсы или воркшопы?
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <div class="option-group <?= $hasResponse ? 'readonly-field' : '' ?>">
                    <label>
                        <input type="radio" name="q12" value="Да" id="q12_yes"
                            <?= ($hasResponse && $userData['q12_extra_courses'] == 'Да') ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span>Да, а именно:</span>
                    </label>
                    <input type="text" class="other-input" name="q12_text" id="q12_text" 
                        placeholder="Какие именно?" 
                        value="<?= $hasResponse ? htmlspecialchars($userData['q12_text'] ?? '') : '' ?>"
                        <?= ($hasResponse && $userData['q12_extra_courses'] != 'Да') ? 'disabled' : '' ?>>
                    <label>
                        <input type="radio" name="q12" value="Нет" id="q12_no"
                            <?= ($hasResponse && $userData['q12_extra_courses'] == 'Нет') ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span>Нет, меня всё устраивает</span>
                    </label>
                    <?php if ($hasResponse && $userData['q12_extra_courses']): ?>
                        <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q12_extra_courses']) ?></strong>
                            <?php if (!empty($userData['q12_text'])): ?>
                                (<?= htmlspecialchars($userData['q12_text']) ?>)
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>👤 Блок 4. Немного о себе</h2>

            <div class="question">
                <span class="question-label">
                    13. Ваш пол:
                    <span class="required">*</span>
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <div class="option-group <?= $hasResponse ? 'readonly-field' : '' ?>">
                    <label>
                        <input type="radio" name="q13" value="Мужской" required
                            <?= ($hasResponse && $userData['q13_gender'] == 'Мужской') ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span>Мужской</span>
                    </label>
                    <label>
                        <input type="radio" name="q13" value="Женский"
                            <?= ($hasResponse && $userData['q13_gender'] == 'Женский') ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span>Женский</span>
                    </label>
                    <?php if ($hasResponse && $userData['q13_gender']): ?>
                        <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q13_gender']) ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="question">
                <span class="question-label">
                    14. Ваш возраст:
                    <span class="required">*</span>
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <div class="option-group <?= $hasResponse ? 'readonly-field' : '' ?>">
                    <?php 
                    $q14Options = ['17–24', '25–34', '35–44', '45–54', '55+'];
                    foreach ($q14Options as $opt): 
                    ?>
                    <label>
                        <input type="radio" name="q14" value="<?= htmlspecialchars($opt) ?>" required
                            <?= ($hasResponse && $userData['q14_age'] == $opt) ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span><?= htmlspecialchars($opt) ?></span>
                    </label>
                    <?php endforeach; ?>
                    <?php if ($hasResponse && $userData['q14_age']): ?>
                        <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q14_age']) ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="question">
                <span class="question-label">
                    15. Семейное положение:
                    <span class="required">*</span>
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <div class="option-group <?= $hasResponse ? 'readonly-field' : '' ?>">
                    <?php 
                    $q15Options = [
                        'Холост / не замужем',
                        'В отношениях / гражданский брак',
                        'Женат / замужем',
                        'Разведён(а)'
                    ];
                    foreach ($q15Options as $opt): 
                    ?>
                    <label>
                        <input type="radio" name="q15" value="<?= htmlspecialchars($opt) ?>" required
                            <?= ($hasResponse && $userData['q15_family'] == $opt) ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>>
                        <span><?= htmlspecialchars($opt) ?></span>
                    </label>
                    <?php endforeach; ?>
                    <?php if ($hasResponse && $userData['q15_family']): ?>
                        <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q15_family']) ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="question">
                <span class="question-label">
                    16. Где вы работаете или учитесь?
                    <span class="hint">Например: разработчик, студент, госслужащий</span>
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <textarea name="q16" maxlength="1000" placeholder="Ваш ответ..." 
                    <?= $hasResponse ? 'disabled' : '' ?>><?= $hasResponse ? htmlspecialchars($userData['q16_work_or_study'] ?? '') : '' ?></textarea>
                <?php if ($hasResponse && !empty($userData['q16_work_or_study'])): ?>
                    <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q16_work_or_study']) ?></strong></div>
                <?php endif; ?>
            </div>

            <div class="question">
                <span class="question-label">
                    17. Как вы узнали о «Школе 21» в Якутске?
                    <span class="required">*</span>
                    <?php if ($hasResponse): ?>
                        <span class="response-badge">✅ Отвечено</span>
                    <?php endif; ?>
                </span>
                <div class="option-group <?= $hasResponse ? 'readonly-field' : '' ?>">
                    <?php 
                    $q17Options = [
                        'Соцсети / реклама',
                        'От друзей/знакомых',
                        'От работодателя',
                        'На мероприятии / карьерном форуме',
                        'Другое'
                    ];
                    foreach ($q17Options as $opt): 
                    ?>
                    <label>
                        <input type="radio" name="q17" value="<?= htmlspecialchars($opt) ?>" required
                            <?= ($hasResponse && $userData['q17_source'] == $opt) ? 'checked' : '' ?>
                            <?= $hasResponse ? 'disabled' : '' ?>
                            <?= ($opt == 'Другое') ? 'data-other="q17_other"' : '' ?>>
                        <span><?= htmlspecialchars($opt) ?></span>
                    </label>
                    <?php endforeach; ?>
                    <input type="text" class="other-input" name="q17_other" id="q17_other" 
                        placeholder="Ваш вариант" 
                        value="<?= $hasResponse ? htmlspecialchars($userData['q17_other'] ?? '') : '' ?>"
                        <?= ($hasResponse && $userData['q17_source'] != 'Другое') ? 'disabled' : '' ?>>
                    <?php if ($hasResponse && $userData['q17_source']): ?>
                        <div class="answered-field">Ваш ответ: <strong><?= htmlspecialchars($userData['q17_source']) ?></strong>
                            <?php if (!empty($userData['q17_other'])): ?>
                                (<?= htmlspecialchars($userData['q17_other']) ?>)
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <?php if ($hasResponse): ?>
                <div style="text-align:center;padding:16px;background:var(--accent-dim);border-radius:12px;margin-bottom:12px;">
                    <p style="color:var(--accent);font-weight:600;">✅ Вы уже проходили опрос (для теста можно отправить снова)</p>
                    <p style="color:var(--text-muted);font-size:14px;">Повторная отправка разрешена для тестирования</p>
                </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary btn-block">📨 Отправить ответ</button>
            <span class="note">После отправки вы увидите сообщение «Благодарим за обратную связь!»</span>
        </div>
    </form>
</main>

<footer class="footer">
    <span>© 2026 АНО «Школа 21. Якутия»</span>
    <a href="/survey/">Пройти опрос</a>
</footer>

<script src="/js/script.js"></script>
</body>
</html>