<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api.php';
require_once __DIR__ . '/../includes/openapi.php';

apiServeDocsIfRequested();

apiRequestHeaders();
$parsed = apiParsePath();
$GLOBALS['api_version'] = $parsed['version'];

if ($parsed['version'] !== 'v1') {
    apiFail(404, 'unsupported_version', 'Unknown API version. Current version is v1.');
}

apiRequireToken();

try {
    apiV1Dispatch($parsed['segments'], apiMethod());
} catch (\PDOException $e) {
    apiFail(500, 'database_error', 'A database error occurred.');
} catch (\Throwable $e) {
    apiFail(500, 'server_error', 'The API request failed.');
}

function apiV1Dispatch(array $segments, string $method): void
{
    if (!$segments) {
        apiV1Catalog();
    }

    $resource = $segments[0];
    $id = isset($segments[1]) && ctype_digit((string)$segments[1]) ? (int)$segments[1] : null;
    $nested = $segments[2] ?? null;
    $nestedId = isset($segments[3]) && ctype_digit((string)$segments[3]) ? (int)$segments[3] : null;

    if ($resource === 'articles' && $nested === 'ranges') {
        apiV1ArticleRanges($id, $nestedId, $method);
    }
    if ($resource === 'articles' && $nested === 'comments') {
        apiV1ArticleComments($id, $nestedId, $method);
    }
    if ($resource === 'events' && $nested === 'images') {
        apiV1EventImages($id, $nestedId, $method);
    }

    if ($resource === 'roles') {
        apiV1Roles($segments[1] ?? null, $method);
    }

    $map = apiV1Resources();
    if (!isset($map[$resource])) {
        apiFail(404, 'not_found', 'Unknown resource. GET /api/v1 for the catalogue.');
    }

    if ($nested !== null && $id !== null) {
        apiFail(404, 'not_found', 'Unknown nested resource.');
    }

    $cfg = $map[$resource];
    if ($id === null) {
        if ($method === 'GET') {
            apiList($cfg['table'], $cfg);
        }
        if ($method === 'POST') {
            apiV1Create($resource, $cfg);
        }
        apiFail(405, 'method_not_allowed', 'Use GET or POST on this collection.');
    }

    apiMustFetch($cfg['table'], $id);
    if ($method === 'GET') {
        apiV1Show($resource, $cfg, $id);
    }
    if ($method === 'PUT' || $method === 'PATCH') {
        apiV1Update($resource, $cfg, $id);
    }
    if ($method === 'DELETE') {
        if ($resource === 'admins') {
            apiV1DeleteAdmin($id);
        }
        apiDelete($cfg['table'], $id);
        apiOk(['deleted' => true, 'id' => $id]);
    }
    apiFail(405, 'method_not_allowed', 'Use GET, PUT, PATCH, or DELETE on this item.');
}

function apiV1Catalog(): void
{
    apiOk([
        'name' => 'Short Circuit Lighting CMS API',
        'auth' => 'Authorization: Bearer <API_TOKEN>  or  X-API-Token: <API_TOKEN>',
        'docs' => appUrl('api/docs'),
        'openapi' => appUrl('api/openapi.json'),
        'resources' => [
            'GET /api/v1/articles' => 'List articles (?page,&per_page,&q,&is_published)',
            'POST /api/v1/articles' => 'Create article (optional ranges[])',
            'GET /api/v1/articles/{id}' => 'Show article with ranges',
            'PUT|PATCH /api/v1/articles/{id}' => 'Update article',
            'DELETE /api/v1/articles/{id}' => 'Delete article',
            'CRUD /api/v1/articles/{id}/ranges' => 'Recommended-range rows',
            'CRUD /api/v1/articles/{id}/comments' => 'Article comments',
            'CRUD /api/v1/standards' => 'Lighting standards',
            'CRUD /api/v1/terms' => 'Terminology matrix rows',
            'CRUD /api/v1/tools' => 'Design tools',
            'CRUD /api/v1/events' => 'Events',
            'CRUD /api/v1/events/{id}/images' => 'Event gallery images',
            'CRUD /api/v1/topics' => 'Community topics (?status=pending|approved|rejected)',
            'CRUD /api/v1/users' => 'Public accounts (password_hash never returned)',
            'CRUD /api/v1/contacts' => 'Contact Us messages',
            'GET|PATCH /api/v1/roles' => 'Role permissions',
            'CRUD /api/v1/admins' => 'CMS admins (invite on create; secrets stripped)',
        ],
    ]);
}

function apiV1Resources(): array
{
    return [
        'articles' => [
            'table' => 'articles',
            'writable' => ['slug', 'tag', 'icon', 'image_url', 'title', 'excerpt', 'intro', 'why_text', 'physical_text', 'physio_text', 'psycho_text', 'formula_text', 'formula_note', 'simulator_url', 'simulator_label', 'is_published', 'sort_order'],
            'required' => ['title', 'excerpt', 'intro'],
            'ints' => ['sort_order'],
            'bools' => ['is_published'],
            'search' => ['title', 'excerpt', 'intro', 'tag', 'slug'],
            'order' => 'sort_order ASC, id ASC',
        ],
        'standards' => [
            'table' => 'standards',
            'writable' => ['code', 'name', 'region', 'description', 'official_url', 'is_published', 'sort_order'],
            'required' => ['code', 'name', 'description'],
            'ints' => ['sort_order'],
            'bools' => ['is_published'],
            'search' => ['code', 'name', 'description', 'region'],
            'order' => 'sort_order ASC, id ASC',
        ],
        'terms' => [
            'table' => 'standard_terms',
            'writable' => ['parameter', 'en_12464', 'iso_8995', 'ansi_ies', 'well_v2', 'is_published', 'sort_order'],
            'required' => ['parameter'],
            'ints' => ['sort_order'],
            'bools' => ['is_published'],
            'search' => ['parameter', 'en_12464', 'iso_8995', 'ansi_ies', 'well_v2'],
            'order' => 'sort_order ASC, id ASC',
        ],
        'tools' => [
            'table' => 'tools',
            'writable' => ['name', 'description', 'url', 'icon', 'image_url', 'is_external', 'is_published', 'sort_order'],
            'required' => ['name', 'description'],
            'ints' => ['sort_order'],
            'bools' => ['is_external', 'is_published'],
            'search' => ['name', 'description'],
            'order' => 'sort_order ASC, id ASC',
        ],
        'events' => [
            'table' => 'events',
            'writable' => ['name', 'year', 'description', 'is_published', 'sort_order'],
            'required' => ['name'],
            'ints' => ['year', 'sort_order'],
            'bools' => ['is_published'],
            'search' => ['name', 'description'],
            'order' => 'sort_order ASC, id ASC',
        ],
        'topics' => [
            'table' => 'discussion_topics',
            'writable' => ['user_id', 'title', 'slug', 'category', 'body', 'status', 'reject_reason'],
            'required' => ['title', 'body', 'user_id'],
            'ints' => ['user_id'],
            'search' => ['title', 'body', 'category', 'slug'],
            'order' => 'created_at DESC',
            'status' => true,
        ],
        'users' => [
            'table' => 'users',
            'writable' => ['name', 'email', 'role', 'is_preapproved', 'is_active', 'phone', 'profession', 'profession_other', 'company', 'is_subscribed'],
            'required' => ['name', 'email'],
            'bools' => ['is_preapproved', 'is_active', 'is_subscribed'],
            'search' => ['name', 'email', 'company', 'role'],
            'order' => 'created_at DESC',
        ],
        'contacts' => [
            'table' => 'contact_messages',
            'writable' => ['name', 'email', 'company', 'message', 'is_read'],
            'required' => ['name', 'email', 'message'],
            'bools' => ['is_read'],
            'search' => ['name', 'email', 'company', 'message'],
            'order' => 'created_at DESC',
        ],
        'admins' => [
            'table' => 'admin_users',
            'writable' => ['username', 'name', 'email', 'phone', 'company', 'title', 'is_active'],
            'required' => ['username', 'name', 'email'],
            'bools' => ['is_active'],
            'search' => ['username', 'name', 'email', 'company', 'title'],
            'order' => 'created_at DESC',
        ],
        'roles' => [
            'table' => 'role_permissions',
            'writable' => ['can_post_topics', 'can_moderate_topics', 'auto_publish_topics'],
            'required' => [],
            'bools' => ['can_post_topics', 'can_moderate_topics', 'auto_publish_topics'],
            'search' => ['role'],
            'order' => 'role ASC',
            'pk' => 'role',
        ],
    ];
}

function apiV1Create(string $resource, array $cfg): void
{
    $body = apiBody();
    $data = apiPick($body, $cfg['writable'], $cfg['ints'] ?? [], $cfg['bools'] ?? []);
    apiRequireFields($data, $cfg['required']);
    apiV1Normalize($resource, $data, 0, $body);
    $id = apiInsert($cfg['table'], $data);
    if ($resource === 'articles') {
        apiV1SaveRanges($id, $body['ranges'] ?? null);
    }
    if ($resource === 'admins') {
        apiV1InviteAdmin($id);
    }
    $row = apiV1Load($resource, $cfg, $id);
    header('Location: ' . appUrl('api/v1/' . $resource . '/' . $id));
    apiOk($row, 201);
}

function apiV1Update(string $resource, array $cfg, int $id): void
{
    $body = apiBody();
    $data = apiPick($body, $cfg['writable'], $cfg['ints'] ?? [], $cfg['bools'] ?? []);
    if ($resource === 'roles') {
        apiFail(404, 'not_found', 'Update a role at PUT /api/v1/roles/{role}.');
    }
    apiV1Normalize($resource, $data, $id, $body);
    if ($data) {
        apiUpdate($cfg['table'], $id, $data);
    }
    if ($resource === 'articles' && array_key_exists('ranges', $body)) {
        apiV1SaveRanges($id, $body['ranges']);
    }
    apiOk(apiV1Load($resource, $cfg, $id));
}

function apiV1Show(string $resource, array $cfg, int $id): void
{
    apiOk(apiV1Load($resource, $cfg, $id));
}

function apiV1Load(string $resource, array $cfg, int $id): array
{
    $row = apiMustFetch($cfg['table'], $id);
    if ($resource === 'articles') {
        $stmt = db()->prepare('SELECT * FROM article_ranges WHERE article_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$id]);
        $row['ranges'] = $stmt->fetchAll();
        $stmt = db()->prepare('SELECT id, article_id, user_id, body, created_at FROM article_comments WHERE article_id = ? ORDER BY created_at ASC');
        $stmt->execute([$id]);
        $row['comments'] = $stmt->fetchAll();
    }
    if ($resource === 'events') {
        $stmt = db()->prepare('SELECT * FROM event_images WHERE event_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$id]);
        $row['images'] = $stmt->fetchAll();
    }
    return $row;
}

function apiV1Normalize(string $resource, array &$data, int $id, array $body): void
{
    if ($resource === 'articles') {
        $existing = $id ? apiFetch('articles', $id) : null;
        $title = (string)($data['title'] ?? ($existing['title'] ?? ''));
        if (isset($data['slug']) || isset($data['title']) || $id === 0) {
            $data['slug'] = apiUniqueSlug('articles', (string)($data['slug'] ?? $title), $id);
        }
        if ($id === 0 && !isset($data['why_text'])) {
            $data['why_text'] = '';
        }
    }
    if ($resource === 'topics') {
        if (isset($data['user_id']) && !apiFetch('users', (int)$data['user_id'])) {
            apiFail(422, 'validation_error', 'user_id does not match a public user.');
        }
        if (isset($data['status']) && !in_array($data['status'], ['pending', 'approved', 'rejected'], true)) {
            apiFail(422, 'validation_error', 'status must be pending, approved, or rejected.');
        }
        $title = (string)($data['title'] ?? '');
        if ($title !== '' || isset($data['slug'])) {
            $data['slug'] = uniqueTopicSlug((string)($data['slug'] ?? $title), $id);
        }
        if (isset($data['status']) && in_array($data['status'], ['approved', 'rejected'], true) && $id) {
            $data['decided_at'] = date('Y-m-d H:i:s');
            if (!isset($data['decided_by'])) {
                $data['decided_by'] = 'API';
            }
        }
    }
    if ($resource === 'users') {
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            apiFail(422, 'validation_error', 'A valid email is required.');
        }
        if (isset($data['role']) && !in_array($data['role'], allRoles(), true)) {
            apiFail(422, 'validation_error', 'Invalid role.');
        }
        if ($id === 0) {
            $data['unsubscribe_token'] = bin2hex(random_bytes(24));
            $password = (string)($body['password'] ?? '');
            $data['password_hash'] = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
            if (!isset($data['role'])) {
                $data['role'] = 'client';
            }
        } elseif (isset($body['password']) && $body['password'] !== '') {
            if (strlen((string)$body['password']) < 8) {
                apiFail(422, 'validation_error', 'Password must be at least 8 characters.');
            }
            $data['password_hash'] = password_hash((string)$body['password'], PASSWORD_DEFAULT);
        }
    }
    if ($resource === 'contacts' && isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        apiFail(422, 'validation_error', 'A valid email is required.');
    }
    if ($resource === 'admins') {
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            apiFail(422, 'validation_error', 'A valid email is required.');
        }
        if ($id === 0) {
            $data['password_hash'] = password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT);
            $data['email_verified_at'] = null;
        }
    }
}

function apiV1SaveRanges(int $articleId, $ranges): void
{
    if ($ranges === null) {
        return;
    }
    if (!is_array($ranges)) {
        apiFail(422, 'validation_error', 'ranges must be an array.');
    }
    db()->prepare('DELETE FROM article_ranges WHERE article_id = ?')->execute([$articleId]);
    $ins = db()->prepare(
        'INSERT INTO article_ranges (article_id, stage_label, environment_label, range_text, notes, sort_order) VALUES (?,?,?,?,?,?)'
    );
    foreach (array_values($ranges) as $i => $r) {
        if (!is_array($r)) {
            continue;
        }
        $stage = apiStr($r['stage_label'] ?? $r['stage'] ?? '', 80);
        $env = apiStr($r['environment_label'] ?? $r['env'] ?? '', 80);
        $range = apiStr($r['range_text'] ?? $r['range'] ?? '', 120);
        if ($stage === '' && $env === '' && $range === '') {
            continue;
        }
        $ins->execute([$articleId, $stage, $env, $range, apiStr($r['notes'] ?? '', 255) ?: null, $i]);
    }
}

function apiV1InviteAdmin(int $id): void
{
    $admin = apiFetch('admin_users', $id);
    if (!$admin || empty($admin['email'])) {
        return;
    }
    $token = issueAdminInviteToken($id);
    sendAdminInviteEmail($admin, $token, 'API');
}

function apiV1DeleteAdmin(int $id): void
{
    $remaining = (int)db()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    if ($remaining <= 1) {
        apiFail(409, 'conflict', 'Cannot delete the last CMS admin.');
    }
    apiDelete('admin_users', $id);
    apiOk(['deleted' => true, 'id' => $id]);
}

function apiV1Roles(?string $role, string $method): void
{
    $role = $role !== null ? trim($role) : '';
    if ($role === '') {
        if ($method === 'GET') {
            apiList('role_permissions', apiV1Resources()['roles']);
        }
        apiFail(405, 'method_not_allowed', 'Use GET to list roles, or PATCH /api/v1/roles/{role}.');
    }
    if (!in_array($role, allRoles(), true)) {
        apiFail(404, 'not_found', 'Unknown role.');
    }
    $stmt = db()->prepare('SELECT * FROM role_permissions WHERE role = ?');
    $stmt->execute([$role]);
    $row = $stmt->fetch();
    if (!$row) {
        apiFail(404, 'not_found', 'Role permissions row was not found.');
    }
    if ($method === 'GET') {
        apiOk($row);
    }
    if ($method === 'PUT' || $method === 'PATCH') {
        if ($role === 'admin') {
            apiFail(403, 'forbidden', 'The CMS treats admin as having every permission; it cannot be reduced via the API.');
        }
        $data = apiPick(apiBody(), ['can_post_topics', 'can_moderate_topics', 'auto_publish_topics'], [], ['can_post_topics', 'can_moderate_topics', 'auto_publish_topics']);
        if (!$data) {
            apiFail(422, 'validation_error', 'No permission fields were provided.');
        }
        $sets = implode(', ', array_map(static fn($c) => "{$c} = ?", array_keys($data)));
        $vals = array_values($data);
        $vals[] = $role;
        db()->prepare("UPDATE role_permissions SET {$sets} WHERE role = ?")->execute($vals);
        $stmt = db()->prepare('SELECT * FROM role_permissions WHERE role = ?');
        $stmt->execute([$role]);
        apiOk($stmt->fetch());
    }
    apiFail(405, 'method_not_allowed', 'Roles can be listed or updated, not created or deleted.');
}

function apiV1ArticleRanges(?int $articleId, ?int $rangeId, string $method): void
{
    if (!$articleId) {
        apiFail(404, 'not_found', 'Article id is required.');
    }
    apiMustFetch('articles', $articleId);
    $writable = ['stage_label', 'environment_label', 'range_text', 'notes', 'sort_order'];
    if ($rangeId === null) {
        if ($method === 'GET') {
            $stmt = db()->prepare('SELECT * FROM article_ranges WHERE article_id = ? ORDER BY sort_order ASC, id ASC');
            $stmt->execute([$articleId]);
            apiOk($stmt->fetchAll());
        }
        if ($method === 'POST') {
            $body = apiBody();
            $data = apiPick($body, $writable, ['sort_order']);
            apiRequireFields($data, ['stage_label', 'environment_label', 'range_text']);
            $data['article_id'] = $articleId;
            $id = apiInsert('article_ranges', $data);
            apiOk(apiMustFetch('article_ranges', $id), 201);
        }
        apiFail(405, 'method_not_allowed', 'Use GET or POST.');
    }
    $row = apiMustFetch('article_ranges', $rangeId);
    if ((int)$row['article_id'] !== $articleId) {
        apiFail(404, 'not_found', 'Range does not belong to this article.');
    }
    if ($method === 'GET') {
        apiOk($row);
    }
    if ($method === 'PUT' || $method === 'PATCH') {
        $data = apiPick(apiBody(), $writable, ['sort_order']);
        apiUpdate('article_ranges', $rangeId, $data);
        apiOk(apiMustFetch('article_ranges', $rangeId));
    }
    if ($method === 'DELETE') {
        apiDelete('article_ranges', $rangeId);
        apiOk(['deleted' => true, 'id' => $rangeId]);
    }
    apiFail(405, 'method_not_allowed', 'Use GET, PUT, PATCH, or DELETE.');
}

function apiV1ArticleComments(?int $articleId, ?int $commentId, string $method): void
{
    if (!$articleId) {
        apiFail(404, 'not_found', 'Article id is required.');
    }
    apiMustFetch('articles', $articleId);
    $writable = ['user_id', 'body'];
    if ($commentId === null) {
        if ($method === 'GET') {
            $stmt = db()->prepare('SELECT id, article_id, user_id, body, created_at FROM article_comments WHERE article_id = ? ORDER BY created_at ASC');
            $stmt->execute([$articleId]);
            apiOk($stmt->fetchAll());
        }
        if ($method === 'POST') {
            $data = apiPick(apiBody(), $writable, ['user_id']);
            apiRequireFields($data, ['body']);
            $data['article_id'] = $articleId;
            $id = apiInsert('article_comments', $data);
            apiOk(apiMustFetch('article_comments', $id), 201);
        }
        apiFail(405, 'method_not_allowed', 'Use GET or POST.');
    }
    $row = apiMustFetch('article_comments', $commentId);
    if ((int)$row['article_id'] !== $articleId) {
        apiFail(404, 'not_found', 'Comment does not belong to this article.');
    }
    if ($method === 'GET') {
        apiOk($row);
    }
    if ($method === 'PUT' || $method === 'PATCH') {
        $data = apiPick(apiBody(), $writable, ['user_id']);
        apiUpdate('article_comments', $commentId, $data);
        apiOk(apiMustFetch('article_comments', $commentId));
    }
    if ($method === 'DELETE') {
        apiDelete('article_comments', $commentId);
        apiOk(['deleted' => true, 'id' => $commentId]);
    }
    apiFail(405, 'method_not_allowed', 'Use GET, PUT, PATCH, or DELETE.');
}

function apiV1EventImages(?int $eventId, ?int $imageId, string $method): void
{
    if (!$eventId) {
        apiFail(404, 'not_found', 'Event id is required.');
    }
    apiMustFetch('events', $eventId);
    $writable = ['image_path', 'caption', 'sort_order'];
    if ($imageId === null) {
        if ($method === 'GET') {
            $stmt = db()->prepare('SELECT * FROM event_images WHERE event_id = ? ORDER BY sort_order ASC, id ASC');
            $stmt->execute([$eventId]);
            apiOk($stmt->fetchAll());
        }
        if ($method === 'POST') {
            $body = apiBody();
            if (!empty($body['image_url']) && empty($body['image_path'])) {
                $body['image_path'] = $body['image_url'];
            }
            $data = apiPick($body, $writable, ['sort_order']);
            apiRequireFields($data, ['image_path']);
            $data['event_id'] = $eventId;
            $id = apiInsert('event_images', $data);
            apiOk(apiMustFetch('event_images', $id), 201);
        }
        apiFail(405, 'method_not_allowed', 'Use GET or POST.');
    }
    $row = apiMustFetch('event_images', $imageId);
    if ((int)$row['event_id'] !== $eventId) {
        apiFail(404, 'not_found', 'Image does not belong to this event.');
    }
    if ($method === 'GET') {
        apiOk($row);
    }
    if ($method === 'PUT' || $method === 'PATCH') {
        $body = apiBody();
        if (!empty($body['image_url']) && empty($body['image_path'])) {
            $body['image_path'] = $body['image_url'];
        }
        $data = apiPick($body, $writable, ['sort_order']);
        apiUpdate('event_images', $imageId, $data);
        apiOk(apiMustFetch('event_images', $imageId));
    }
    if ($method === 'DELETE') {
        apiDelete('event_images', $imageId);
        apiOk(['deleted' => true, 'id' => $imageId]);
    }
    apiFail(405, 'method_not_allowed', 'Use GET, PUT, PATCH, or DELETE.');
}
