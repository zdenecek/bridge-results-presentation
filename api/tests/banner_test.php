<?php

// php api/tests/banner_test.php

require __DIR__ . '/../banner.php';

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

check('nothing set', bannerPublic($pdo), null);

$saved = bannerSave($pdo, ['enabled' => true, 'title' => ' Velká cena Prahy ', 'text' => '14.–15. 11.', 'url' => 'https://bkpraha.cz/vc',
    'linkLabel' => 'Přihláška', 'until' => '2026-11-15']);
check('saved and trimmed', [$saved['title'], $saved['enabled']], ['Velká cena Prahy', true]);
check('shown until its last day', bannerPublic($pdo, '2026-11-15')['linkLabel'] ?? null, 'Přihláška');
check('gone the day after', bannerPublic($pdo, '2026-11-16'), null);

bannerSave($pdo, ['enabled' => false] + $saved);
check('switched off', bannerPublic($pdo, '2026-11-01'), null);
check('switched off keeps the text', bannerGet($pdo)['title'], 'Velká cena Prahy');

$rejected = function (array $data): bool {
    global $pdo;
    try {
        bannerSave($pdo, $data);
        return false;
    } catch (InvalidArgumentException $e) {
        return true;
    }
};
check('enabled needs a title', $rejected(['enabled' => true, 'title' => '']), true);
check('link must be http', $rejected(['title' => 'x', 'url' => 'javascript:alert(1)']), true);

echo $failures ? "\n$failures FAILED\n" : "\nall passed\n";
exit($failures ? 1 : 0);
