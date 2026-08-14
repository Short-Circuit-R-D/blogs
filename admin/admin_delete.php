<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $id = (int)($_POST['id'] ?? 0);
    $me = (int)(currentAdmin()['id'] ?? 0);
    if ($id && $id !== $me) {
        $remaining = (int)db()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
        if ($remaining > 1) {
            db()->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$id]);
        }
    }
}
redirect('admins.php');
