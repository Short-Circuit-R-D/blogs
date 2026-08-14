<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $imageId = (int)($_POST['image_id'] ?? 0);
    $eventId = (int)($_POST['event_id'] ?? 0);

    if ($imageId) {
        $stmt = db()->prepare('SELECT image_path FROM event_images WHERE id = ?');
        $stmt->execute([$imageId]);
        $row = $stmt->fetch();
        if ($row) {
            $full = UPLOAD_DIR . '/' . $row['image_path'];
            if (is_file($full)) @unlink($full);
            db()->prepare('DELETE FROM event_images WHERE id = ?')->execute([$imageId]);
        }
    }
}
redirect('event_edit.php?id=' . $eventId);
