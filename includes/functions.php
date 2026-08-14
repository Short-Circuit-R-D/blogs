<?php
require_once __DIR__ . '/../config.php';

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function redirect(string $path): void
{
    if ($path !== '' && $path[0] !== '/' && !preg_match('#^[a-z][a-z0-9+.-]*:#i', $path)) {
        $path = appUrl($path);
    }
    header('Location: ' . $path);
    exit;
}

/**
 * URL prefix of this install, no trailing slash — '' at domain root,
 * '/LightSCenter_roles_topics_update' (or '/lightSCenter') in a subfolder.
 * Derived from SCRIPT_NAME so it stays correct under the public/ rewrite.
 */
function appBase(): string
{
    static $base = null;
    if ($base !== null) return $base;
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim(dirname($script), '/');
    if (basename($dir) === 'public') {
        $dir = rtrim(dirname($dir), '/');
    }
    $base = ($dir === '/' || $dir === '.') ? '' : $dir;
    return $base;
}

/** Site-relative URL. Pass '' for the install root (always with a trailing slash). */
function appUrl(string $path = ''): string
{
    $base = appBase();
    $path = ltrim($path, '/');
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return ($base === '' ? '' : $base) . '/' . $path;
}

function articleUrl(string $slug): string
{
    return 'article/' . rawurlencode($slug);
}

/** Absolute public-blog URL (emails, Open Graph). Always https://blogs.shortcircuit.company/... */
function publicSiteUrl(string $path = ''): string
{
    $base = rtrim(defined('PUBLIC_SITE_URL') ? PUBLIC_SITE_URL : SITE_URL, '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

function articlePermalink(string $slug): string
{
    return publicSiteUrl('article/' . rawurlencode($slug));
}

function topicPermalink(string $slug): string
{
    return publicSiteUrl('topic/' . rawurlencode($slug));
}

function defaultLogoUrl(): string
{
    return publicSiteUrl('logo.svg');
}

function defaultOgImageUrl(): string
{
    return publicSiteUrl('og-image.png');
}

/**
 * Make sure newer front-end columns/tables exist on an already-running
 * database, so subscribe-only accounts and article comments work without
 * a manual migration step. Safe to call on every request.
 */
function ensureFrontendSchema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    try {
        $pdo = db();

        $phone = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'")->fetch();
        if (!$phone) {
            $pdo->exec('ALTER TABLE users ADD COLUMN phone VARCHAR(40) DEFAULT NULL AFTER password_hash');
        }

        $hashCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_hash'")->fetch();
        if ($hashCol && strtoupper((string)$hashCol['Null']) === 'NO') {
            $pdo->exec('ALTER TABLE users MODIFY password_hash VARCHAR(255) DEFAULT NULL');
        }

        $cols = [
            'profession'       => 'ALTER TABLE users ADD COLUMN profession VARCHAR(40) DEFAULT NULL AFTER phone',
            'profession_other' => 'ALTER TABLE users ADD COLUMN profession_other VARCHAR(120) DEFAULT NULL AFTER profession',
            'company'          => 'ALTER TABLE users ADD COLUMN company VARCHAR(160) DEFAULT NULL AFTER profession_other',
        ];
        foreach ($cols as $name => $ddl) {
            $exists = $pdo->query("SHOW COLUMNS FROM users LIKE " . $pdo->quote($name))->fetch();
            if (!$exists) {
                $pdo->exec($ddl);
            }
        }

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS article_comments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                article_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED DEFAULT NULL,
                body TEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_article_comments (article_id, created_at),
                CONSTRAINT fk_comment_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
                CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    } catch (\Throwable $e) {
        // Don't block the public site if the account can't ALTER TABLE.
    }
}

/**
 * Countries shown on the subscribe / account phone picker.
 * `min`/`max` are the national mobile length (digits only, no leading 0,
 * no country code) used to validate optional numbers.
 */
function countryCallingCodes(): array
{
    return [
        ['iso' => 'EG', 'name' => 'Egypt',               'dial' => '20',  'min' => 10, 'max' => 10],
        ['iso' => 'DZ', 'name' => 'Algeria',              'dial' => '213', 'min' => 9,  'max' => 9],
        ['iso' => 'AU', 'name' => 'Australia',            'dial' => '61',  'min' => 9,  'max' => 9],
        ['iso' => 'AT', 'name' => 'Austria',              'dial' => '43',  'min' => 10, 'max' => 13],
        ['iso' => 'BH', 'name' => 'Bahrain',              'dial' => '973', 'min' => 8,  'max' => 8],
        ['iso' => 'BE', 'name' => 'Belgium',              'dial' => '32',  'min' => 9,  'max' => 9],
        ['iso' => 'BR', 'name' => 'Brazil',               'dial' => '55',  'min' => 10, 'max' => 11],
        ['iso' => 'CA', 'name' => 'Canada',               'dial' => '1',   'min' => 10, 'max' => 10],
        ['iso' => 'CN', 'name' => 'China',                'dial' => '86',  'min' => 11, 'max' => 11],
        ['iso' => 'DK', 'name' => 'Denmark',              'dial' => '45',  'min' => 8,  'max' => 8],
        ['iso' => 'FR', 'name' => 'France',               'dial' => '33',  'min' => 9,  'max' => 9],
        ['iso' => 'DE', 'name' => 'Germany',              'dial' => '49',  'min' => 10, 'max' => 11],
        ['iso' => 'GR', 'name' => 'Greece',               'dial' => '30',  'min' => 10, 'max' => 10],
        ['iso' => 'IN', 'name' => 'India',                'dial' => '91',  'min' => 10, 'max' => 10],
        ['iso' => 'ID', 'name' => 'Indonesia',            'dial' => '62',  'min' => 9,  'max' => 12],
        ['iso' => 'IQ', 'name' => 'Iraq',                 'dial' => '964', 'min' => 10, 'max' => 10],
        ['iso' => 'IT', 'name' => 'Italy',                'dial' => '39',  'min' => 9,  'max' => 10],
        ['iso' => 'JP', 'name' => 'Japan',                'dial' => '81',  'min' => 10, 'max' => 10],
        ['iso' => 'JO', 'name' => 'Jordan',               'dial' => '962', 'min' => 9,  'max' => 9],
        ['iso' => 'KE', 'name' => 'Kenya',                'dial' => '254', 'min' => 9,  'max' => 9],
        ['iso' => 'KW', 'name' => 'Kuwait',               'dial' => '965', 'min' => 8,  'max' => 8],
        ['iso' => 'LB', 'name' => 'Lebanon',              'dial' => '961', 'min' => 7,  'max' => 8],
        ['iso' => 'MY', 'name' => 'Malaysia',             'dial' => '60',  'min' => 9,  'max' => 10],
        ['iso' => 'MX', 'name' => 'Mexico',               'dial' => '52',  'min' => 10, 'max' => 10],
        ['iso' => 'MA', 'name' => 'Morocco',              'dial' => '212', 'min' => 9,  'max' => 9],
        ['iso' => 'NL', 'name' => 'Netherlands',          'dial' => '31',  'min' => 9,  'max' => 9],
        ['iso' => 'NG', 'name' => 'Nigeria',              'dial' => '234', 'min' => 10, 'max' => 10],
        ['iso' => 'NO', 'name' => 'Norway',               'dial' => '47',  'min' => 8,  'max' => 8],
        ['iso' => 'OM', 'name' => 'Oman',                 'dial' => '968', 'min' => 8,  'max' => 8],
        ['iso' => 'PK', 'name' => 'Pakistan',             'dial' => '92',  'min' => 10, 'max' => 10],
        ['iso' => 'PH', 'name' => 'Philippines',          'dial' => '63',  'min' => 10, 'max' => 10],
        ['iso' => 'PL', 'name' => 'Poland',               'dial' => '48',  'min' => 9,  'max' => 9],
        ['iso' => 'PT', 'name' => 'Portugal',             'dial' => '351', 'min' => 9,  'max' => 9],
        ['iso' => 'QA', 'name' => 'Qatar',                'dial' => '974', 'min' => 8,  'max' => 8],
        ['iso' => 'RU', 'name' => 'Russia',               'dial' => '7',   'min' => 10, 'max' => 10],
        ['iso' => 'SA', 'name' => 'Saudi Arabia',         'dial' => '966', 'min' => 9,  'max' => 9],
        ['iso' => 'SG', 'name' => 'Singapore',            'dial' => '65',  'min' => 8,  'max' => 8],
        ['iso' => 'ZA', 'name' => 'South Africa',         'dial' => '27',  'min' => 9,  'max' => 9],
        ['iso' => 'KR', 'name' => 'South Korea',          'dial' => '82',  'min' => 9,  'max' => 11],
        ['iso' => 'ES', 'name' => 'Spain',                'dial' => '34',  'min' => 9,  'max' => 9],
        ['iso' => 'SE', 'name' => 'Sweden',               'dial' => '46',  'min' => 9,  'max' => 10],
        ['iso' => 'CH', 'name' => 'Switzerland',          'dial' => '41',  'min' => 9,  'max' => 9],
        ['iso' => 'TN', 'name' => 'Tunisia',              'dial' => '216', 'min' => 8,  'max' => 8],
        ['iso' => 'TR', 'name' => 'Turkey',               'dial' => '90',  'min' => 10, 'max' => 10],
        ['iso' => 'AE', 'name' => 'United Arab Emirates', 'dial' => '971', 'min' => 9,  'max' => 9],
        ['iso' => 'GB', 'name' => 'United Kingdom',       'dial' => '44',  'min' => 10, 'max' => 10],
        ['iso' => 'US', 'name' => 'United States',        'dial' => '1',   'min' => 10, 'max' => 10],
    ];
}

function phoneCountryByDial(string $dial): ?array
{
    foreach (countryCallingCodes() as $c) {
        if ($c['dial'] === $dial) return $c;
    }
    return null;
}

/** Split a stored E.164 value (e.g. +2010…) into dial code + national number. */
function splitPhone(?string $e164): array
{
    $digits = preg_replace('/\D+/', '', (string)$e164);
    if ($digits === '') return ['dial' => '20', 'number' => ''];

    $codes = countryCallingCodes();
    usort($codes, fn($a, $b) => strlen($b['dial']) <=> strlen($a['dial']));
    foreach ($codes as $c) {
        if (strpos($digits, $c['dial']) === 0) {
            return ['dial' => $c['dial'], 'number' => substr($digits, strlen($c['dial']))];
        }
    }
    return ['dial' => '20', 'number' => $digits];
}

/**
 * Validate an optional mobile number.
 * Empty country+number → null (allowed).
 * Otherwise returns E.164 (+dial+national) or false on length/format error.
 */
function formatPhoneE164(string $dial, string $national): string|false|null
{
    $dial = preg_replace('/\D+/', '', $dial);
    $national = preg_replace('/\D+/', '', $national);
    $national = ltrim($national, '0');

    if ($dial === '' && $national === '') return null;
    if ($national === '') return null;

    $country = phoneCountryByDial($dial);
    if (!$country) return false;

    $len = strlen($national);
    if ($len < $country['min'] || $len > $country['max']) return false;

    $e164 = '+' . $country['dial'] . $national;
    return strlen($e164) <= 20 ? $e164 : false;
}

function phoneLengthHint(array $country): string
{
    if ($country['min'] === $country['max']) {
        return $country['name'] . ' mobile numbers are ' . $country['min'] . ' digits (without the country code or a leading 0).';
    }
    return $country['name'] . ' mobile numbers are ' . $country['min'] . '–' . $country['max'] . ' digits (without the country code or a leading 0).';
}

function normalizePhone(?string $phone): ?string
{
    $phone = trim((string)$phone);
    if ($phone === '') return null;
    $parts = splitPhone($phone);
    $formatted = formatPhoneE164($parts['dial'], $parts['number']);
    return $formatted ?: null;
}

/** Profession options on the subscribe-only form (not the account permission role). */
function subscribeProfessions(): array
{
    return [
        'consultant' => 'Consultant',
        'engineer'   => 'Engineer',
        'professor'  => 'Professor',
        'other'      => 'Other',
    ];
}

function subscribeProfessionLabel(?string $key, ?string $other = null): string
{
    $labels = subscribeProfessions();
    if ($key === 'other') {
        $other = trim((string)$other);
        return $other !== '' ? 'Other — ' . $other : 'Other';
    }
    return $labels[$key] ?? '';
}

function parseSubscribeProfession(array $post): array
{
    $key = (string)($post['profession'] ?? '');
    $allowed = array_keys(subscribeProfessions());
    if (!in_array($key, $allowed, true)) {
        return ['profession' => '', 'profession_other' => null, 'error' => 'Please choose your role.'];
    }
    $other = trim((string)($post['profession_other'] ?? ''));
    if ($key === 'other') {
        if ($other === '') {
            return ['profession' => 'other', 'profession_other' => null, 'error' => 'Please specify your role.'];
        }
        return ['profession' => 'other', 'profession_other' => substr($other, 0, 120), 'error' => null];
    }
    return ['profession' => $key, 'profession_other' => null, 'error' => null];
}

function getArticleComments(int $articleId): array
{
    try {
        $stmt = db()->prepare(
            'SELECT c.*, u.name AS user_name
             FROM article_comments c
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.article_id = ?
             ORDER BY c.created_at ASC, c.id ASC'
        );
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    } catch (\Throwable $e) {
        return [];
    }
}

function addArticleComment(int $articleId, int $userId, string $body): bool
{
    $body = trim($body);
    if ($body === '' || strlen($body) > 4000) return false;
    $stmt = db()->prepare('INSERT INTO article_comments (article_id, user_id, body) VALUES (?,?,?)');
    $stmt->execute([$articleId, $userId, $body]);
    return true;
}

/**
 * Save an uploaded image into $subdir under UPLOAD_DIR.
 * Returns the relative path (e.g. "events/booth-2025-01.jpg") or null if no file was uploaded.
 */
function saveUpload(string $fieldName, string $subdir): ?string
{
    if (empty($_FILES[$fieldName]['name']) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed with error code ' . $_FILES[$fieldName]['error']);
    }

    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        throw new RuntimeException('Only JPG, PNG, or WEBP images are allowed.');
    }

    $targetDir = UPLOAD_DIR . '/' . trim($subdir, '/');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $targetPath = $targetDir . '/' . $filename;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
        throw new RuntimeException('Could not move uploaded file.');
    }

    return trim($subdir, '/') . '/' . $filename;
}

function uploadUrl(?string $relativePath): ?string
{
    if (!$relativePath) return null;
    return UPLOAD_URL . '/' . ltrim($relativePath, '/');
}

/**
 * Render the visual for an article/tool: a real <img> when image_url is
 * set, falling back to the legacy inline SVG icon set when it isn't (so
 * older rows that never got an image keep working). $class is applied to
 * whichever element renders.
 */
function mediaTag(?string $imageUrl, string $iconKey, string $alt = '', string $class = ''): string
{
    $imageUrl = trim((string)$imageUrl);
    if ($imageUrl !== '') {
        return '<img src="' . e($imageUrl) . '" alt="' . e($alt) . '" class="' . e($class) . '" loading="lazy">';
    }
    return '<span class="' . e($class) . ' icon-fallback">' . iconSvg($iconKey) . '</span>';
}

/**
 * Paginated article listing — used by both the "All Articles" page and
 * empty-query "browse" mode. When $query is non-empty it searches
 * title/excerpt/intro/tag via FULLTEXT (falling back to a LIKE scan if
 * the FULLTEXT index isn't available or the query is too short/common
 * for FULLTEXT to match, e.g. a single short word).
 */
function paginateArticles(string $query, int $page, int $perPage = 12, string $topic = ''): array
{
    $pdo    = db();
    $query  = trim($query);
    $topic  = trim($topic);
    $page   = max(1, $page);
    $offset = ($page - 1) * $perPage;
    $rows   = [];
    $total  = 0;

    $topicSql    = $topic !== '' ? ' AND tag = ?' : '';
    $topicParams = $topic !== '' ? [$topic] : [];

    if ($query !== '') {
        $usedLike = false;
        try {
            $stmt = $pdo->prepare(
                'SELECT *, MATCH(title, excerpt, intro, tag) AGAINST (? IN NATURAL LANGUAGE MODE) AS relevance
                 FROM articles
                 WHERE is_published = 1' . $topicSql . ' AND MATCH(title, excerpt, intro, tag) AGAINST (? IN NATURAL LANGUAGE MODE)
                 ORDER BY relevance DESC LIMIT ? OFFSET ?'
            );
            $i = 1;
            $stmt->bindValue($i++, $query);
            foreach ($topicParams as $p) { $stmt->bindValue($i++, $p); }
            $stmt->bindValue($i++, $query);
            $stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
            $stmt->bindValue($i++, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            $countStmt = $pdo->prepare(
                'SELECT COUNT(*) FROM articles WHERE is_published = 1' . $topicSql . '
                 AND MATCH(title, excerpt, intro, tag) AGAINST (? IN NATURAL LANGUAGE MODE)'
            );
            $countStmt->execute(array_merge($topicParams, [$query]));
            $total = (int)$countStmt->fetchColumn();
        } catch (\Throwable $e) {
            $usedLike = true;
        }

        // FULLTEXT can legitimately return nothing for a short/common word
        // (below MySQL's default 4-char minimum) — fall back to LIKE.
        if ($usedLike || $total === 0) {
            $like = '%' . $query . '%';
            $stmt = $pdo->prepare(
                'SELECT * FROM articles WHERE is_published = 1' . $topicSql . '
                 AND (title LIKE ? OR excerpt LIKE ? OR intro LIKE ? OR tag LIKE ?)
                 ORDER BY sort_order ASC, id ASC LIMIT ? OFFSET ?'
            );
            $i = 1;
            foreach ($topicParams as $p) { $stmt->bindValue($i++, $p); }
            $stmt->bindValue($i++, $like);
            $stmt->bindValue($i++, $like);
            $stmt->bindValue($i++, $like);
            $stmt->bindValue($i++, $like);
            $stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
            $stmt->bindValue($i++, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            $countStmt = $pdo->prepare(
                'SELECT COUNT(*) FROM articles WHERE is_published = 1' . $topicSql . '
                 AND (title LIKE ? OR excerpt LIKE ? OR intro LIKE ? OR tag LIKE ?)'
            );
            $countStmt->execute(array_merge($topicParams, [$like, $like, $like, $like]));
            $total = (int)$countStmt->fetchColumn();
        }
    } else {
        $stmt = $pdo->prepare('SELECT * FROM articles WHERE is_published = 1' . $topicSql . ' ORDER BY sort_order ASC, id ASC LIMIT ? OFFSET ?');
        $i = 1;
        foreach ($topicParams as $p) { $stmt->bindValue($i++, $p); }
        $stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($i++, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows  = $stmt->fetchAll();

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE is_published = 1' . $topicSql);
        $countStmt->execute($topicParams);
        $total = (int)$countStmt->fetchColumn();
    }

    return [
        'rows'       => $rows,
        'total'      => $total,
        'page'       => $page,
        'perPage'    => $perPage,
        'totalPages' => max(1, (int)ceil($total / $perPage)),
    ];
}

/** Distinct tags across published articles, for topic filter chips + follow lists. */
function getArticleTopics(): array
{
    return db()->query("SELECT DISTINCT tag FROM articles WHERE is_published = 1 AND tag != '' ORDER BY tag ASC")->fetchAll(PDO::FETCH_COLUMN);
}

function isSubscribedToTopic(int $userId, string $topic): bool
{
    $stmt = db()->prepare('SELECT 1 FROM topic_subscriptions WHERE user_id = ? AND topic = ?');
    $stmt->execute([$userId, $topic]);
    return (bool)$stmt->fetchColumn();
}

/** All topics one user currently follows, as a flat array — cheaper than one query per chip. */
function getUserSubscribedTopics(int $userId): array
{
    $stmt = db()->prepare('SELECT topic FROM topic_subscriptions WHERE user_id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/** Toggle follow/unfollow for one user+topic. Returns the resulting state (true = now following). */
function toggleTopicSubscription(int $userId, string $topic): bool
{
    if (isSubscribedToTopic($userId, $topic)) {
        $stmt = db()->prepare('DELETE FROM topic_subscriptions WHERE user_id = ? AND topic = ?');
        $stmt->execute([$userId, $topic]);
        return false;
    }
    $stmt = db()->prepare('INSERT IGNORE INTO topic_subscriptions (user_id, topic) VALUES (?, ?)');
    $stmt->execute([$userId, $topic]);
    return true;
}

/**
 * Fixture search — FRONT-END STUB ONLY.
 *
 * This is wired up end-to-end (search.php UI, tab, form submission,
 * pagination-free result panel) so the "Fixtures" tab already works from
 * a user's point of view — it just has no backend yet. To go live: call
 * the Short Circuit fixtures API here instead of returning the stub
 * array, keeping the same return shape so search.php needs no changes:
 *   ['results' => [ ['name'=>.., 'sku'=>.., 'url'=>.., 'summary'=>..], ... ], 'stub' => false]
 */
function searchFixtures(string $query): array
{
    return [
        'results' => [],
        'stub'    => true,
        'message' => 'Fixture search will connect to the Short Circuit fixtures API soon. '
                   . 'This tab is ready and wired up — it just has nothing to query yet.',
    ];
}

/** Render a brand-styled prev/1..n/next pagination bar. $baseParams excludes 'page'. */
function renderPagination(string $basePath, array $baseParams, int $page, int $totalPages): string
{
    if ($totalPages <= 1) return '';
    $link = function (int $p) use ($basePath, $baseParams) {
        return e($basePath . '?' . http_build_query($baseParams + ['page' => $p]));
    };
    $html = '<nav class="pagination" aria-label="Pagination">';
    $html .= $page > 1 ? '<a class="page-btn" href="' . $link($page - 1) . '">‹ Prev</a>' : '<span class="page-btn disabled">‹ Prev</span>';
    $start = max(1, $page - 2);
    $end   = min($totalPages, $page + 2);
    if ($start > 1) { $html .= '<a class="page-num" href="' . $link(1) . '">1</a>'; if ($start > 2) $html .= '<span class="page-dots">…</span>'; }
    for ($p = $start; $p <= $end; $p++) {
        $html .= $p === $page ? '<span class="page-num active">' . $p . '</span>' : '<a class="page-num" href="' . $link($p) . '">' . $p . '</a>';
    }
    if ($end < $totalPages) { if ($end < $totalPages - 1) $html .= '<span class="page-dots">…</span>'; $html .= '<a class="page-num" href="' . $link($totalPages) . '">' . $totalPages . '</a>'; }
    $html .= $page < $totalPages ? '<a class="page-btn" href="' . $link($page + 1) . '">Next ›</a>' : '<span class="page-btn disabled">Next ›</span>';
    $html .= '</nav>';
    return $html;
}

/**
 * Email every subscribed user that a new article is live — global
 * subscribers plus anyone specifically following this article's topic
 * (tag) — then mark it notified so the admin "Notify Subscribers" button
 * doesn't double-send. Each recipient gets exactly one email even if
 * they're both globally subscribed and following the topic.
 * Returns the number of emails sent.
 */
function notifySubscribers(array $article): int
{
    require_once __DIR__ . '/mailer.php';
    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT DISTINCT u.email, u.name, u.unsubscribe_token
         FROM users u
         LEFT JOIN topic_subscriptions t ON t.user_id = u.id AND t.topic = ?
         WHERE u.is_subscribed = 1 OR t.id IS NOT NULL'
    );
    $stmt->execute([$article['tag']]);
    $users = $stmt->fetchAll();

    // Range table rows for this article (same data shown on the admin edit screen).
    $stmt = $pdo->prepare('SELECT stage_label, environment_label, range_text, notes FROM article_ranges WHERE article_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$article['id']]);
    $ranges = $stmt->fetchAll();

    $url = articlePermalink($article['slug']);

    // --- Content blocks built once, reused for every recipient ---

    $imageHtml = '';
    if (!empty($article['image_url'])) {
        $imageHtml = '<img src="' . e($article['image_url']) . '" alt="' . e($article['title']) . '" '
                   . 'style="width:100%;max-width:512px;height:auto;border-radius:6px;display:block;margin:0 0 20px;">';
    }

    $whyHtml = '';
    if (!empty($article['why_text'])) {
        $whyHtml = '<p style="font-size:13px;font-weight:600;color:#eb1b26;margin:20px 0 4px;text-transform:uppercase;letter-spacing:.04em;">Why it matters</p>'
                 . '<p>' . nl2br(e($article['why_text'])) . '</p>';
    }

    $formulaHtml = '';
    if (!empty($article['formula_text'])) {
        $formulaHtml = '<div style="background:#f5f5f5;border-left:3px solid #eb1b26;padding:10px 14px;margin:16px 0;font-family:monospace;font-size:14px;">'
                      . e($article['formula_text'])
                      . (!empty($article['formula_note']) ? '<div style="font-family:Arial,sans-serif;font-size:12px;color:#666;margin-top:6px;">' . e($article['formula_note']) . '</div>' : '')
                      . '</div>';
    }

    $rangesHtml = '';
    if ($ranges) {
        $rows = '';
        foreach ($ranges as $r) {
            $rows .= '<tr>'
                   . '<td style="padding:6px 10px;border-bottom:1px solid #eee;">' . e($r['stage_label']) . '</td>'
                   . '<td style="padding:6px 10px;border-bottom:1px solid #eee;">' . e($r['environment_label']) . '</td>'
                   . '<td style="padding:6px 10px;border-bottom:1px solid #eee;font-weight:600;">' . e($r['range_text']) . '</td>'
                   . '<td style="padding:6px 10px;border-bottom:1px solid #eee;color:#666;">' . e($r['notes'] ?? '') . '</td>'
                   . '</tr>';
        }
        $rangesHtml = '<p style="font-size:13px;font-weight:600;color:#eb1b26;margin:20px 0 4px;text-transform:uppercase;letter-spacing:.04em;">Recommended ranges</p>'
                    . '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
                    . '<thead><tr style="text-align:left;color:#999;font-size:11px;text-transform:uppercase;">'
                    . '<th style="padding:6px 10px;">Stage</th><th style="padding:6px 10px;">Environment</th><th style="padding:6px 10px;">Range</th><th style="padding:6px 10px;">Notes</th>'
                    . '</tr></thead><tbody>' . $rows . '</tbody></table>';
    }

    $sent = 0;
    foreach ($users as $u) {
        $unsubUrl = publicSiteUrl('unsubscribe?token=' . urlencode($u['unsubscribe_token']));
        $body = '<p>Hi ' . e($u['name']) . ',</p>'
              . '<p>A new lighting guide just went live' . ($article['tag'] ? ' in <strong>' . e($article['tag']) . '</strong>' : '') . ':</p>'
              . $imageHtml
              . '<p style="font-size:17px;font-weight:bold;">' . e($article['title']) . '</p>'
              . '<p>' . e($article['excerpt']) . '</p>'
              . $whyHtml
              . $formulaHtml
              . $rangesHtml
              . '<p style="margin-top:20px;"><a href="' . e($url) . '" style="display:inline-block;background:#eb1b26;color:#fff;padding:10px 18px;border-radius:4px;text-decoration:none;">Read it →</a></p>'
              . '<p style="font-size:11px;color:#999;">Don\'t want these emails? <a href="' . e($unsubUrl) . '">Unsubscribe</a>.</p>';
        if (sendMail($u['email'], 'New guide: ' . $article['title'], siteEmailLayout('New Lighting Guide Published', $body))) {
            $sent++;
        }
    }

    $stmt = $pdo->prepare('UPDATE articles SET notified_at = NOW() WHERE id = ?');
    $stmt->execute([$article['id']]);

    return $sent;
}

/* ---------------------------------------------------------------
 * Roles & community-topic moderation
 * --------------------------------------------------------------- */

/** Fixed role list, lowest to highest power. `admin` = full admin / high board. */
function allRoles(): array
{
    return ['client', 'employee', 'leader', 'admin'];
}

function roleLabel(string $role): string
{
    $labels = ['client' => 'Client', 'employee' => 'Employee (SC)', 'leader' => 'Leader', 'admin' => 'Full Admin / High Board'];
    return $labels[$role] ?? ucfirst($role);
}

/**
 * All role_permissions rows keyed by role. `admin` is always forced to
 * every permission = 1 in the returned array even if the DB row was
 * edited down, so the top tier can never accidentally lock itself out.
 */
function getRolePermissions(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    $rows = db()->query('SELECT * FROM role_permissions')->fetchAll(PDO::FETCH_ASSOC);
    $byRole = [];
    foreach (allRoles() as $r) {
        $byRole[$r] = ['role' => $r, 'can_post_topics' => 0, 'can_moderate_topics' => 0, 'auto_publish_topics' => 0];
    }
    foreach ($rows as $row) {
        $byRole[$row['role']] = $row;
    }
    $byRole['admin']['can_post_topics']     = 1;
    $byRole['admin']['can_moderate_topics'] = 1;
    $byRole['admin']['auto_publish_topics'] = 1;

    $cache = $byRole;
    return $cache;
}

function roleCan(string $role, string $permission): bool
{
    $perms = getRolePermissions();
    return !empty($perms[$role][$permission]);
}

/** Whether this specific logged-in user's own topics should skip moderation. */
function userAutoPublishes(array $user): bool
{
    if (!empty($user['is_preapproved'])) return true;
    return roleCan($user['role'] ?? 'client', 'auto_publish_topics');
}

/** Make a unique slug for discussion_topics by appending -2, -3, ... on collision. */
function uniqueTopicSlug(string $title, int $excludeId = 0): string
{
    $base = slugify($title) ?: 'topic';
    $slug = $base;
    $i = 2;
    $pdo = db();
    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM discussion_topics WHERE slug = ? AND id != ?');
        $stmt->execute([$slug, $excludeId]);
        if (!$stmt->fetch()) return $slug;
        $slug = $base . '-' . $i++;
    }
}

/** Distinct categories used by community topics so far, for the browse filter. */
function getDiscussionCategories(): array
{
    return db()->query("SELECT DISTINCT category FROM discussion_topics WHERE status = 'approved' AND category != '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Email a topic's author once it's been accepted or rejected. Called
 * right after the status UPDATE from both the leader moderation queue
 * (public/moderate.php) and the admin dashboard (admin/topics.php).
 * Silently no-ops if the topic/author can't be found — moderation should
 * never fail just because a notification couldn't be sent.
 */
function notifyTopicDecision(int $topicId, string $decision, ?string $reason = null): bool
{
    require_once __DIR__ . '/mailer.php';

    $stmt = db()->prepare(
        'SELECT t.title, t.slug, u.name, u.email FROM discussion_topics t
         JOIN users u ON u.id = t.user_id WHERE t.id = ?'
    );
    $stmt->execute([$topicId]);
    $row = $stmt->fetch();
    if (!$row) return false;

    if ($decision === 'approved') {
        $url = topicPermalink($row['slug']);
        $body = '<p>Hi ' . e($row['name']) . ',</p>'
              . '<p>Your topic is now live on the Community page:</p>'
              . '<p style="font-size:17px;font-weight:bold;">' . e($row['title']) . '</p>'
              . '<p><a href="' . e($url) . '" style="display:inline-block;background:#eb1b26;color:#fff;padding:10px 18px;border-radius:4px;text-decoration:none;">View it →</a></p>';
        return sendMail($row['email'], 'Your topic was accepted: ' . $row['title'], siteEmailLayout('Topic Accepted', $body));
    }

    if ($decision === 'rejected') {
        $body = '<p>Hi ' . e($row['name']) . ',</p>'
              . '<p>Your topic wasn\'t accepted for the Community page:</p>'
              . '<p style="font-size:17px;font-weight:bold;">' . e($row['title']) . '</p>'
              . ($reason ? '<p><strong>Reason:</strong> ' . e($reason) . '</p>' : '')
              . '<p>You can edit and resubmit any time from your account page.</p>';
        return sendMail($row['email'], 'Your topic needs changes: ' . $row['title'], siteEmailLayout('Topic Not Accepted', $body));
    }

    return false;
}

/** Inline SVG icon set shared by the public site and the admin dashboard. */
function iconSvg(string $key): string
{
    $icons = [
        'cri' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><circle cx="32" cy="32" r="22"/><path d="M32 10 A22 22 0 0 1 51 42"/><circle cx="32" cy="32" r="6" fill="currentColor" stroke="none"/></svg>',
        'cct' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M26 8h12v30a10 10 0 1 1-12 0V8z"/><path d="M26 18h12"/><circle cx="32" cy="46" r="5" fill="currentColor" stroke="none"/></svg>',
        'lux' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><circle cx="32" cy="32" r="11"/><path d="M32 6v8M32 50v8M6 32h8M50 32h8M14 14l6 6M44 44l6 6M50 14l-6 6M20 44l-6 6"/></svg>',
        'lumens' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M32 8a16 16 0 0 0-9 29c2 1.5 3 3.5 3 6v3h12v-3c0-2.5 1-4.5 3-6a16 16 0 0 0-9-29z"/><path d="M26 54h12M28 60h8"/></svg>',
        'ugr' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M6 32s10-16 26-16 26 16 26 16-10 16-26 16S6 32 6 32z"/><circle cx="32" cy="32" r="7"/></svg>',
        'flicker' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 40l10-20 8 16 8-24 8 24 8-12 10 16"/></svg>',
        'uniformity' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3"><rect x="8" y="8" width="20" height="20"/><rect x="36" y="8" width="20" height="20" opacity=".4"/><rect x="8" y="36" width="20" height="20" opacity=".7"/><rect x="36" y="36" width="20" height="20" opacity=".55"/></svg>',
        'melanopic' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><circle cx="32" cy="32" r="24"/><path d="M32 18v14l10 6"/></svg>',
        'vertical' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><rect x="14" y="8" width="36" height="48" rx="2"/><path d="M32 48V16M26 22l6-6 6 6"/></svg>',
        'exposure' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M18 8h28M18 56h28M20 8c0 14 24 14 24 28s-24 14-24 28M44 8c0 14-24 14-24 28s24 14 24 28" opacity=".85"/></svg>',
        'standard' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M32 6l20 8v16c0 14-9 24-20 28C21 54 12 44 12 30V14z"/><path d="M24 32l6 6 12-12"/></svg>',
        'compare' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M32 8L8 20l24 12 24-12z"/><path d="M8 32l24 12 24-12M8 44l24 12 24-12"/></svg>',
        'xr' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="22" width="52" height="24" rx="8"/><circle cx="22" cy="34" r="5"/><circle cx="42" cy="34" r="5"/><path d="M27 34h10"/></svg>',
        'lux2' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><rect x="10" y="10" width="44" height="34" rx="3"/><path d="M18 44v6h28v-6M22 24l7 7 13-13"/></svg>',
        'school' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 24L32 12l26 12-26 12z"/><path d="M16 30v14c0 4 8 8 16 8s16-4 16-8V30"/></svg>',
        'dialux' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="14" width="48" height="36" rx="3"/><path d="M8 24h48"/><circle cx="20" cy="19" r="1.5" fill="currentColor" stroke="none"/><circle cx="26" cy="19" r="1.5" fill="currentColor" stroke="none"/><path d="M18 38l8-8 6 6 12-12"/></svg>',
    ];
    return $icons[$key] ?? $icons['standard'];
}

ensureFrontendSchema();