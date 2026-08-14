<?php
/**
 * Short Circuit employee login against the company credentials API.
 * Public subscribe / sign-up never assigns role=employee — only this path does.
 */

function scLoginApiConfigured(): bool
{
    return defined('SC_LOGIN_API_URL') && trim((string)SC_LOGIN_API_URL) !== '';
}

/**
 * @return array{ok:bool, name?:string, email?:string, error?:string}
 */
function scEmployeeAuthenticate(string $email, string $password): array
{
    $url = scLoginApiConfigured() ? rtrim((string)SC_LOGIN_API_URL, '/') : '';
    if ($url === '') {
        return ['ok' => false, 'error' => 'Staff login is not connected yet. Ask a Short Circuit admin to set SC_LOGIN_API_URL.'];
    }

    $payload = json_encode(['email' => $email, 'password' => $password]);
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    if (defined('SC_LOGIN_API_KEY') && SC_LOGIN_API_KEY !== '') {
        $headers[] = 'Authorization: Bearer ' . SC_LOGIN_API_KEY;
    }

    $raw = null;
    $status = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            return ['ok' => false, 'error' => 'Could not reach Short Circuit login (' . $curlErr . ').'];
        }
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => implode("\r\n", $headers),
                'content' => $payload,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int)$m[1];
        }
        if ($raw === false) {
            return ['ok' => false, 'error' => 'Could not reach Short Circuit login.'];
        }
    }

    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Staff login returned an unexpected response.'];
    }

    $ok = !empty($data['ok']) || !empty($data['success']) || $status === 200;
    if (!$ok) {
        return ['ok' => false, 'error' => (string)($data['error'] ?? $data['message'] ?? 'Those Short Circuit credentials were not accepted.')];
    }

    $user = is_array($data['user'] ?? null) ? $data['user'] : $data;
    $outEmail = trim((string)($user['email'] ?? $email));
    $outName  = trim((string)($user['name'] ?? $user['full_name'] ?? ''));
    if ($outEmail === '' || !filter_var($outEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Staff login did not return a valid email.'];
    }
    if ($outName === '') {
        $outName = explode('@', $outEmail)[0];
    }

    return ['ok' => true, 'name' => $outName, 'email' => $outEmail];
}

/** Create or promote a local user to role=employee after a successful SC API login. */
function upsertScEmployeeUser(string $email, string $name): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare('UPDATE users SET name = CASE WHEN name = \'\' THEN ? ELSE name END, role = \'employee\', is_active = 1 WHERE id = ?');
        $stmt->execute([$name, $existing['id']]);
        $existing['name'] = $existing['name'] !== '' ? $existing['name'] : $name;
        $existing['role'] = 'employee';
        return $existing;
    }

    $token = bin2hex(random_bytes(24));
    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, role, password_hash, is_subscribed, unsubscribe_token)
         VALUES (?,?,\'employee\',NULL,1,?)'
    );
    $stmt->execute([$name, $email, $token]);
    return [
        'id'    => (int)$pdo->lastInsertId(),
        'name'  => $name,
        'email' => $email,
        'role'  => 'employee',
    ];
}
