<?php

require_once __DIR__ . '/schema.php';

// Registration for the next cycle of a competition, shown large on the home page and larger on one tournament's pages. One row.

const PROMO_FIELDS = ['title' => 255, 'titleEn' => 255, 'text' => 500, 'textEn' => 500, 'facts' => 500, 'factsEn' => 500,
    'url' => 500, 'linkLabel' => 100, 'linkLabelEn' => 100, 'closes' => 16, 'tournament' => 255];

function promoEnsureSchema(PDO $pdo): void
{
    static $ready = [];
    if (isset($ready[spl_object_id($pdo)])) return;
    $ready[spl_object_id($pdo)] = true;

    $suffix = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin';
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_promo (
        id INT NOT NULL PRIMARY KEY,
        enabled TINYINT NOT NULL DEFAULT 0,
        data TEXT NOT NULL
    )$suffix");
}

function promoGet(PDO $pdo): array
{
    promoEnsureSchema($pdo);
    $row = $pdo->query("SELECT enabled, data FROM site_promo WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    $data = json_decode($row['data'] ?? '{}', true) ?: [];
    $promo = ['enabled' => (bool) ($row['enabled'] ?? false)];
    foreach (array_keys(PROMO_FIELDS) as $field) {
        $promo[$field] = ($data[$field] ?? '') === '' ? ($field === 'title' || $field === 'text' ? '' : null) : (string) $data[$field];
    }
    return $promo;
}

/** What visitors see: nothing once it is switched off or registration has closed (Prague time). */
function promoPublic(PDO $pdo, ?string $now = null): ?array
{
    $promo = promoGet($pdo);
    $now ??= (new DateTime('now', new DateTimeZone('Europe/Prague')))->format('Y-m-d\TH:i');
    if (!$promo['enabled'] || $promo['title'] === '' || ($promo['closes'] && $promo['closes'] <= $now)) return null;
    unset($promo['enabled']);
    return $promo;
}

function promoSave(PDO $pdo, array $input): array
{
    promoEnsureSchema($pdo);
    $data = [];
    foreach (PROMO_FIELDS as $field => $max) {
        $data[$field] = mb_substr(trim((string) ($input[$field] ?? '')), 0, $max);
    }
    $enabled = !empty($input['enabled']);
    if ($enabled && $data['title'] === '') throw new InvalidArgumentException('Přihlášky potřebují nadpis.');
    if ($enabled && $data['url'] === '') throw new InvalidArgumentException('Přihlášky potřebují odkaz.');
    if ($data['url'] !== '' && !preg_match('~^https?://~i', $data['url'])) throw new InvalidArgumentException('Odkaz musí začínat http:// nebo https://.');
    if ($data['closes'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $data['closes'])) throw new InvalidArgumentException('Uzávěrka musí být ve tvaru RRRR-MM-DDTHH:MM.');

    $pdo->exec("DELETE FROM site_promo WHERE id = 1");
    $pdo->prepare("INSERT INTO site_promo (id, enabled, data) VALUES (1, ?, ?)")
        ->execute([$enabled ? 1 : 0, json_encode($data, JSON_UNESCAPED_UNICODE)]);
    return promoGet($pdo);
}
