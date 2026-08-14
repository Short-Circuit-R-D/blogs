<?php
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function currentAdmin(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function requireLogin(): void
{
    if (!currentAdmin()) {
        redirect('login.php');
    }
}

/** Simple per-session CSRF token for the admin forms. */
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCheck(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(400);
        die('Invalid or expired form submission. Go back and try again.');
    }
}
