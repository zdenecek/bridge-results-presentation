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

// A table from before the English fields gets them added and keeps its row
$old = new PDO('sqlite::memory:');
$old->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$old->exec("CREATE TABLE site_banner (id INT NOT NULL PRIMARY KEY, enabled TINYINT NOT NULL DEFAULT 0, title VARCHAR(255) NOT NULL DEFAULT '',
    text VARCHAR(500) NOT NULL DEFAULT '', url VARCHAR(500) NULL, link_label VARCHAR(100) NULL, until DATE NULL)");
$old->exec("INSERT INTO site_banner (id, enabled, title, text) VALUES (1, 1, 'CBT Praha', '6.–8. 11.')");
check('old table upgraded', [bannerGet($old)['title'], bannerGet($old)['titleEn']], ['CBT Praha', null]);
check('english saved', bannerSave($old, ['enabled' => true, 'title' => 'CBT Praha', 'titleEn' => 'CBT Prague', 'textEn' => 'Nov 6–8'])['textEn'], 'Nov 6–8');

echo $failures ? "\n$failures FAILED\n" : "\nall passed\n";
exit($failures ? 1 : 0);
