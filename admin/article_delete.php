<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $stmt = db()->prepare('SELECT image_url FROM articles WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            mediaUnlinkLocal($row['image_url'] ?? null);
        }
        db()->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
    }
}
redirect('articles.php');
