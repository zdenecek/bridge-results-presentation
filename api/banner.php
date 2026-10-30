<?php

// The invitation shown at the top of the public pages, such as the next Velká cena Prahy. One row.

function bannerEnsureSchema(PDO $pdo): void
{
    static $ready = [];
    if (isset($ready[spl_object_id($pdo)])) return;
    $ready[spl_object_id($pdo)] = true;

    $suffix = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin';
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_banner (
        id INT NOT NULL PRIMARY KEY,
        enabled TINYINT NOT NULL DEFAULT 0,
        title VARCHAR(255) NOT NULL DEFAULT '',
        text VARCHAR(500) NOT NULL DEFAULT '',
        url VARCHAR(500) NULL,
        link_label VARCHAR(100) NULL,
        until DATE NULL
    )$suffix");
}

function bannerGet(PDO $pdo): array
{
    bannerEnsureSchema($pdo);
    $row = $pdo->query("SELECT enabled, title, text, url, link_label, until FROM site_banner WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    return [
        'enabled' => (bool) ($row['enabled'] ?? false),
        'title' => (string) ($row['title'] ?? ''),
        'text' => (string) ($row['text'] ?? ''),
        'url' => ($row['url'] ?? '') ?: null,
        'linkLabel' => ($row['link_label'] ?? '') ?: null,
        'until' => ($row['until'] ?? '') ?: null,
    ];
}

/** What visitors see: nothing once it is switched off or its last day has passed. */
function bannerPublic(PDO $pdo, ?string $today = null): ?array
{
    $banner = bannerGet($pdo);
    $today ??= date('Y-m-d');
    if (!$banner['enabled'] || $banner['title'] === '' || ($banner['until'] && $banner['until'] < $today)) return null;
    unset($banner['enabled']);
    return $banner;
}

function bannerSave(PDO $pdo, array $data): array
{
    bannerEnsureSchema($pdo);
    $title = trim((string) ($data['title'] ?? ''));
    $url = trim((string) ($data['url'] ?? ''));
    $until = trim((string) ($data['until'] ?? ''));
    $enabled = !empty($data['enabled']);
    if ($enabled && $title === '') throw new InvalidArgumentException('Pozvánka potřebuje nadpis.');
    if ($url !== '' && !preg_match('~^https?://~i', $url)) throw new InvalidArgumentException('Odkaz musí začínat http:// nebo https://.');
    if ($until !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $until)) throw new InvalidArgumentException('Datum musí být ve tvaru RRRR-MM-DD.');

    $pdo->exec("DELETE FROM site_banner WHERE id = 1");
    $insert = $pdo->prepare("INSERT INTO site_banner (id, enabled, title, text, url, link_label, until) VALUES (1, ?, ?, ?, ?, ?, ?)");
    $insert->execute([
        $enabled ? 1 : 0,
        mb_substr($title, 0, 255),
        mb_substr(trim((string) ($data['text'] ?? '')), 0, 500),
        $url ?: null,
        mb_substr(trim((string) ($data['linkLabel'] ?? '')), 0, 100) ?: null,
        $until ?: null,
    ]);
    return bannerGet($pdo);
}
