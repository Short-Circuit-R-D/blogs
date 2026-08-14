<?php
/**
 * Admin security audit: writes a daily log file and a matching DB row
 * for every admin page open and every admin action.
 */

function adminAuditLogFileName(?DateTimeInterface $when = null): string
{
    $when = $when ?? new DateTimeImmutable('now');
    return 'admin-audit-' . $when->format('Y-m-d') . '.log';
}

function adminAuditLogDir(): string
{
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    return $dir;
}

function ensureAdminAuditSchema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    try {
        db()->exec(
            'CREATE TABLE IF NOT EXISTS admin_audit_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                log_file VARCHAR(80) NOT NULL,
                occurred_at DATETIME NOT NULL,
                account VARCHAR(60) NOT NULL DEFAULT \'guest\',
                action VARCHAR(60) NOT NULL,
                page VARCHAR(190) NOT NULL DEFAULT \'\',
                ip VARCHAR(45) NOT NULL DEFAULT \'\',
                location VARCHAR(160) NOT NULL DEFAULT \'\',
                device VARCHAR(180) NOT NULL DEFAULT \'\',
                user_agent VARCHAR(400) NOT NULL DEFAULT \'\',
                details TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_audit_occurred (occurred_at),
                KEY idx_audit_account (account),
                KEY idx_audit_file (log_file)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    } catch (\Throwable $e) {
        // Logging must never take down the admin UI.
    }
}

function adminAuditClientIp(): string
{
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        $_SERVER['HTTP_X_REAL_IP'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '',
    ];
    foreach ($candidates as $raw) {
        $ip = trim(explode(',', $raw)[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return '';
}

function adminAuditIsPrivateIp(string $ip): bool
{
    return $ip === '' || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}

function adminAuditLocation(string $ip): string
{
    if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1') {
        return 'Localhost';
    }
    if (adminAuditIsPrivateIp($ip)) {
        return 'Private network (' . $ip . ')';
    }

    $cacheFile = adminAuditLogDir() . DIRECTORY_SEPARATOR . 'geo-cache.json';
    $cache = [];
    if (is_readable($cacheFile)) {
        $cache = json_decode((string)file_get_contents($cacheFile), true) ?: [];
    }
    $now = time();
    if (isset($cache[$ip]['label'], $cache[$ip]['exp']) && $cache[$ip]['exp'] > $now) {
        return (string)$cache[$ip]['label'];
    }

    $label = 'Unknown location';
    $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,country,regionName,city,isp';
    $ctx = stream_context_create(['http' => ['timeout' => 0.8, 'ignore_errors' => true]]);
    $json = @file_get_contents($url, false, $ctx);
    if (is_string($json) && $json !== '') {
        $data = json_decode($json, true);
        if (is_array($data) && ($data['status'] ?? '') === 'success') {
            $parts = array_filter([
                $data['city'] ?? '',
                $data['regionName'] ?? '',
                $data['country'] ?? '',
            ]);
            $label = implode(', ', $parts);
            if (!empty($data['isp'])) {
                $label .= ' (' . $data['isp'] . ')';
            }
        }
    }

    $cache[$ip] = ['label' => $label, 'exp' => $now + 86400 * 7];
    if (count($cache) > 400) {
        $cache = array_slice($cache, -400, null, true);
    }
    @file_put_contents($cacheFile, json_encode($cache), LOCK_EX);

    return $label;
}

function adminAuditDevice(string $ua): string
{
    if ($ua === '') {
        return 'Unknown device';
    }

    $os = 'Unknown OS';
    if (stripos($ua, 'Windows') !== false) $os = 'Windows';
    elseif (stripos($ua, 'Android') !== false) $os = 'Android';
    elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) $os = 'iOS';
    elseif (stripos($ua, 'Mac OS') !== false || stripos($ua, 'Macintosh') !== false) $os = 'macOS';
    elseif (stripos($ua, 'Linux') !== false) $os = 'Linux';

    $browser = 'Unknown browser';
    if (stripos($ua, 'Edg/') !== false || stripos($ua, 'Edge/') !== false) $browser = 'Edge';
    elseif (stripos($ua, 'OPR/') !== false || stripos($ua, 'Opera') !== false) $browser = 'Opera';
    elseif (stripos($ua, 'Chrome/') !== false) $browser = 'Chrome';
    elseif (stripos($ua, 'Firefox/') !== false) $browser = 'Firefox';
    elseif (stripos($ua, 'Safari/') !== false) $browser = 'Safari';

    $type = preg_match('/Mobile|Android|iPhone|iPad/i', $ua) ? 'mobile' : 'desktop';
    return $browser . ' on ' . $os . ' (' . $type . ')';
}

function adminAuditSanitize(array $input): array
{
    $skip = [
        'password', 'password_hash', 'pass', 'csrf', 'token',
        'current_password', 'new_password', 'smtp_pass', 'smtp_password',
    ];
    $out = [];
    foreach ($input as $key => $value) {
        $k = strtolower((string)$key);
        if (in_array($k, $skip, true) || str_contains($k, 'password')) {
            $out[$key] = '[redacted]';
            continue;
        }
        if (is_array($value)) {
            $out[$key] = adminAuditSanitize($value);
            continue;
        }
        $str = (string)$value;
        if (strlen($str) > 300) {
            $str = substr($str, 0, 300) . '…';
        }
        $out[$key] = $str;
    }
    return $out;
}

function adminAuditInferAction(): string
{
    $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($method !== 'POST') {
        return 'page_open';
    }
    if (str_contains($script, '_delete')) {
        return 'delete';
    }
    if (str_contains($script, '_edit')) {
        return !empty($_POST['id']) ? 'update' : 'create';
    }
    if ($script === 'roles.php') {
        return 'update_permissions';
    }
    if ($script === 'topics.php') {
        return 'moderate_topic';
    }
    return 'form_submit';
}

function adminAuditPage(): string
{
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
    $path = parse_url($uri, PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
}

/**
 * Write one audit line to today's log file and insert a DB row.
 * The DB `log_file` column stores only the filename (not a path).
 */
function adminAuditLog(string $action, string $account, string $summary = '', array $extra = []): void
{
    try {
        ensureAdminAuditSchema();

        $when = new DateTimeImmutable('now');
        $logFile = adminAuditLogFileName($when);
        $ip = adminAuditClientIp();
        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 400);
        $device = adminAuditDevice($ua);
        $location = adminAuditLocation($ip);
        $page = adminAuditPage();
        $account = $account !== '' ? $account : 'guest';

        $details = array_merge([
            'summary' => $summary,
            'method'  => strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')),
            'script'  => basename((string)($_SERVER['SCRIPT_NAME'] ?? '')),
            'query'   => adminAuditSanitize($_GET),
            'post'    => adminAuditSanitize($_POST),
            'files'   => array_keys($_FILES),
        ], $extra);

        $line = sprintf(
            "[%s] action=%s account=%s ip=%s location=%s device=%s page=%s details=%s\n",
            $when->format('Y-m-d H:i:s'),
            $action,
            $account,
            $ip,
            $location,
            $device,
            $page,
            json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        @file_put_contents(adminAuditLogDir() . DIRECTORY_SEPARATOR . $logFile, $line, FILE_APPEND | LOCK_EX);

        $stmt = db()->prepare(
            'INSERT INTO admin_audit_logs
                (log_file, occurred_at, account, action, page, ip, location, device, user_agent, details)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $logFile,
            $when->format('Y-m-d H:i:s'),
            $account,
            $action,
            $page,
            $ip,
            $location,
            $device,
            $ua,
            json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    } catch (\Throwable $e) {
        // Never block admin because logging failed.
    }
}

/** Log the current admin request (page open or POST action). */
function adminAuditCurrentRequest(): void
{
    static $logged = false;
    if ($logged) return;
    $logged = true;

    $admin = function_exists('currentAdmin') ? currentAdmin() : null;
    $account = $admin['username'] ?? 'guest';
    $action = adminAuditInferAction();
    $summary = $action === 'page_open'
        ? 'Opened admin page'
        : 'Admin action: ' . $action;
    adminAuditLog($action, $account, $summary);
}
