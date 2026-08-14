<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
    }
}
redirect('contacts.php');
