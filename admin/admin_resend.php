<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $id = (int)($_POST['id'] ?? 0);
    if ($id && $id !== (int)currentAdmin()['id']) {
        $stmt = db()->prepare('SELECT * FROM admin_users WHERE id = ?');
        $stmt->execute([$id]);
        $admin = $stmt->fetch();
        if ($admin && !empty($admin['email'])) {
            $token = issueAdminInviteToken($id);
            $inviter = currentAdmin();
            $sent = sendAdminInviteEmail(
                $admin,
                $token,
                (string)(($inviter['name'] ?? '') !== '' ? $inviter['name'] : ($inviter['username'] ?? ''))
            );
            $_SESSION['admin_flash'] = $sent
                ? 'A new confirmation link was sent to ' . $admin['email'] . '.'
                : 'Could not send the invite email. Try again.';
        }
    }
}
redirect('admins.php');
