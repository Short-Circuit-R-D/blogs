<?php
/**
 * JSON API helpers (versioned REST under /api/v1).
 */

function apiVersion(): string
{
    return $GLOBALS['api_version'] ?? 'v1';
}

function apiMethod(): string
{
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $override = strtoupper((string)($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $_POST['_method'] ?? ''));
    if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true) && in_array($method, ['POST', 'PUT'], true)) {
        return $override;
    }
    return $method;
}

function apiCorsHeaders(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-API-Token, X-HTTP-Method-Override');
    header('Access-Control-Expose-Headers: X-API-Version, Location');
    header('Access-Control-Max-Age: 86400');
}

function apiRequestHeaders(): void
{
    header('Content-Type: application/json; charset=UTF-8');
    header('X-API-Version: ' . apiVersion());
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    apiCorsHeaders();
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function apiJson(int $status, array $payload): void
{
    http_response_code($status);
    $payload['ok'] = $status >= 200 && $status < 300;
    $payload['version'] = apiVersion();
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function apiFail(int $status, string $code, string $message, array $extra = []): void
{
    apiJson($status, array_merge([
        'error' => ['code' => $code, 'message' => $message],
    ], $extra));
}

function apiOk($data = null, int $status = 200, array $meta = []): void
{
    $payload = [];
    if ($data !== null) {
        $payload['data'] = $data;
    }
    if ($meta) {
        $payload['meta'] = $meta;
    }
    apiJson($status, $payload);
}

function apiIncomingToken(): string
{
    $header = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ($header === '' && function_exists('apache_request_headers')) {
        foreach (apache_request_headers() as $name => $value) {
            if (strcasecmp((string)$name, 'Authorization') === 0) {
                $header = (string)$value;
                break;
            }
        }
    }
    if (preg_match('/^Bearer\s+(\S+)/i', $header, $m)) {
        return $m[1];
    }
    $alt = (string)($_SERVER['HTTP_X_API_TOKEN'] ?? '');
    if ($alt !== '') {
        return $alt;
    }
    return '';
}

function apiRequireToken(): void
{
    $expected = defined('API_TOKEN') ? (string)API_TOKEN : '';
    if ($expected === '') {
        apiFail(503, 'api_token_missing', 'API_TOKEN is not set in .env.');
    }
    $got = apiIncomingToken();
    if ($got === '' || !hash_equals($expected, $got)) {
        // Do not send WWW-Authenticate: browsers intercept that on XHR and Swagger shows no response.
        apiFail(401, 'unauthorized', 'Provide a valid API token (Authorization: Bearer <token> or X-API-Token).');
    }
}

function apiBody(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $raw = file_get_contents('php://input') ?: '';
    $ct = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
    if ($raw !== '' && (str_contains($ct, 'json') || ($raw[0] ?? '') === '{' || ($raw[0] ?? '') === '[')) {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            apiFail(400, 'invalid_json', 'Request body is not valid JSON.');
        }
        $cached = $decoded;
        return $cached;
    }
    $cached = $_POST ?: [];
    return $cached;
}

function apiInt($value, int $default = 0): int
{
    if (is_bool($value)) {
        return $value ? 1 : 0;
    }
    if ($value === null || $value === '') {
        return $default;
    }
    return (int)$value;
}

function apiBool($value, int $default = 0): int
{
    if (is_bool($value)) {
        return $value ? 1 : 0;
    }
    if ($value === null || $value === '') {
        return $default;
    }
    $v = strtolower(trim((string)$value));
    if (in_array($v, ['1', 'true', 'yes', 'on'], true)) {
        return 1;
    }
    if (in_array($v, ['0', 'false', 'no', 'off'], true)) {
        return 0;
    }
    return ((int)$value) ? 1 : 0;
}

function apiStr($value, int $max = 0): string
{
    $s = trim((string)($value ?? ''));
    if ($max > 0) {
        $s = function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
    }
    return $s;
}

function apiStripSecrets(array $row): array
{
    unset(
        $row['password_hash'],
        $row['invite_token_hash'],
        $row['unsubscribe_token']
    );
    return $row;
}

function apiPick(array $input, array $writable, array $ints = [], array $bools = []): array
{
    $out = [];
    foreach ($writable as $field) {
        if (!array_key_exists($field, $input)) {
            continue;
        }
        $val = $input[$field];
        if (in_array($field, $bools, true)) {
            $out[$field] = apiBool($val);
        } elseif (in_array($field, $ints, true)) {
            $out[$field] = $val === null || $val === '' ? null : apiInt($val);
        } elseif ($val === null) {
            $out[$field] = null;
        } else {
            $out[$field] = is_string($val) ? trim($val) : $val;
            if ($out[$field] === '') {
                $out[$field] = null;
            }
        }
    }
    return $out;
}

function apiRequireFields(array $data, array $required): void
{
    $missing = [];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
            $missing[] = $field;
        }
    }
    if ($missing) {
        apiFail(422, 'validation_error', 'Missing required fields: ' . implode(', ', $missing), [
            'fields' => $missing,
        ]);
    }
}

function apiFetch(string $table, int $id): ?array
{
    $stmt = db()->prepare("SELECT * FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function apiMustFetch(string $table, int $id): array
{
    $row = apiFetch($table, $id);
    if (!$row) {
        apiFail(404, 'not_found', ucfirst(rtrim($table, 's')) . " #{$id} was not found.");
    }
    return apiStripSecrets($row);
}

function apiUniqueSlug(string $table, string $base, int $excludeId = 0): string
{
    $base = slugify($base) ?: 'item';
    $slug = $base;
    $i = 2;
    $pdo = db();
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $excludeId]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

function apiList(string $table, array $opts = []): void
{
    $page = max(1, apiInt($_GET['page'] ?? 1, 1));
    $perPage = min(100, max(1, apiInt($_GET['per_page'] ?? 20, 20)));
    $q = apiStr($_GET['q'] ?? '');
    $order = $opts['order'] ?? 'id DESC';
    $search = $opts['search'] ?? [];
    $where = ['1=1'];
    $params = [];

    if ($q !== '' && $search) {
        $likes = [];
        foreach ($search as $col) {
            $likes[] = "{$col} LIKE ?";
            $params[] = '%' . $q . '%';
        }
        $where[] = '(' . implode(' OR ', $likes) . ')';
    }
    if (isset($_GET['is_published']) && $_GET['is_published'] !== '' && in_array('is_published', $opts['bools'] ?? [], true)) {
        $where[] = 'is_published = ?';
        $params[] = apiBool($_GET['is_published']);
    }
    if (isset($_GET['status']) && $_GET['status'] !== '' && !empty($opts['status'])) {
        $where[] = 'status = ?';
        $params[] = apiStr($_GET['status'], 40);
    }

    $sqlWhere = implode(' AND ', $where);
    $count = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE {$sqlWhere}");
    $count->execute($params);
    $total = (int)$count->fetchColumn();
    $offset = ($page - 1) * $perPage;

    $stmt = db()->prepare("SELECT * FROM {$table} WHERE {$sqlWhere} ORDER BY {$order} LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    $rows = array_map('apiStripSecrets', $stmt->fetchAll());
    if (!empty($opts['map']) && is_callable($opts['map'])) {
        $rows = array_map($opts['map'], $rows);
    }
    apiOk($rows, 200, [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => max(1, (int)ceil($total / $perPage)),
    ]);
}

function apiInsert(string $table, array $data): int
{
    if (!$data) {
        apiFail(422, 'validation_error', 'No writable fields were provided.');
    }
    $cols = array_keys($data);
    $place = implode(',', array_fill(0, count($cols), '?'));
    $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES (' . $place . ')';
    db()->prepare($sql)->execute(array_values($data));
    return (int)db()->lastInsertId();
}

function apiUpdate(string $table, int $id, array $data): void
{
    if (!$data) {
        apiFail(422, 'validation_error', 'No writable fields were provided.');
    }
    $sets = implode(', ', array_map(static fn($c) => "{$c} = ?", array_keys($data)));
    $vals = array_values($data);
    $vals[] = $id;
    db()->prepare("UPDATE {$table} SET {$sets} WHERE id = ?")->execute($vals);
}

function apiDelete(string $table, int $id): void
{
    db()->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
}

function apiParsePath(): array
{
    $path = rawurldecode((string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? ''));
    $path = rtrim($path, '/');
    if (!preg_match('#/api/(v[0-9]+)(?:/(.*))?$#', $path, $m)) {
        apiFail(404, 'not_found', 'API path not recognised. Use /api/v1/...');
    }
    $GLOBALS['api_version'] = $m[1];
    $rest = trim((string)($m[2] ?? ''), '/');
    return [
        'version' => $m[1],
        'segments' => $rest === '' ? [] : explode('/', $rest),
    ];
}
