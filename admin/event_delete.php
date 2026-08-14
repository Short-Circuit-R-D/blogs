<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        // Best-effort: remove image files from disk before the DB rows cascade-delete.
        $stmt = db()->prepare('SELECT image_path FROM event_images WHERE event_id = ?');
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll() as $row) {
            $full = UPLOAD_DIR . '/' . $row['image_path'];
            if (is_file($full)) @unlink($full);
        }
        db()->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
    }
}
redirect('events.php');
