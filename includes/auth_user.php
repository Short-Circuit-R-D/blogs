<?php
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** The logged-in site user (subscriber account), distinct from admin sessions. */
function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireUserLogin(): void
{
    if (!currentUser()) {
        $_SESSION['post_login_redirect'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
        redirect('login.php');
    }
}

/** Full, fresh row from `users` for the logged-in account (role, is_preapproved, etc.) — session only carries id/name/email. */
function currentUserFull(): ?array
{
    $u = currentUser();
    if (!$u) return null;
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$u['id']]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Require login AND that the account's role currently has $permission on (per the admin cpanel). */
function requirePermission(string $permission): array
{
    requireUserLogin();
    $full = currentUserFull();
    if (!$full || empty($full['is_active'])) {
        redirect('logout.php');
    }
    if (!roleCan($full['role'], $permission)) {
        http_response_code(403);
        die('Your account role does not currently have this permission. Contact a Short Circuit admin if you believe this is wrong.');
    }
    return $full;
}

/** Shared CSRF helpers (same pattern as includes/auth.php, same $_SESSION). */
function userCsrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function userCsrfCheck(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(400);
        die('Invalid or expired form submission. Go back and try again.');
    }
}

function startUserSession(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'    => (int)$user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
    ];
}

function isScEmployee(?array $user = null): bool
{
    $user = $user ?? currentUserFull();
    return $user && ($user['role'] ?? '') === 'employee';
}
