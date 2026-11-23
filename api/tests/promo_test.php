<?php

// php api/tests/promo_test.php

require __DIR__ . '/../promo.php';

$failures = 0;
function check(string $label, $actual, $expected): void
{
    global $failures;
    if ($actual === $expected) {
        echo "ok   $label\n";
        return;
    }
    $failures++;
    echo "FAIL $label\n  expected: " . json_encode($expected, JSON_UNESCAPED_UNICODE)
        . "\n  actual:   " . json_encode($actual, JSON_UNESCAPED_UNICODE) . "\n";
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

check('nothing set', promoPublic($pdo), null);
check('empty settings', promoGet($pdo)['enabled'], false);

$draft = ['enabled' => false, 'title' => ' Skupinová A Zima 2026 ', 'url' => 'https://www.bkpraha.cz/prihlasky-ska/',
    'closes' => '2026-11-28T18:00', 'tournament' => 'skupinova-a-podzim-2026'];
$saved = promoSave($pdo, $draft);
check('saved switched off and trimmed', [$saved['title'], $saved['enabled'], $saved['textEn']], ['Skupinová A Zima 2026', false, null]);
check('hidden while switched off', promoPublic($pdo, '2026-11-23T12:00'), null);

promoSave($pdo, ['enabled' => true] + $draft);
check('shown before the deadline', promoPublic($pdo, '2026-11-28T17:59')['tournament'] ?? null, 'skupinova-a-podzim-2026');
check('gone at the deadline', promoPublic($pdo, '2026-11-28T18:00'), null);

$rejected = function (array $data): bool {
    global $pdo;
    try {
        promoSave($pdo, $data);
        return false;
    } catch (InvalidArgumentException $e) {
        return true;
    }
};
check('enabled needs a link', $rejected(['enabled' => true, 'title' => 'X']), true);
check('link must be http', $rejected(['title' => 'X', 'url' => 'javascript:alert(1)']), true);
check('deadline format', $rejected(['title' => 'X', 'closes' => '28. 11. 2026']), true);

exit($failures ? 1 : 0);
