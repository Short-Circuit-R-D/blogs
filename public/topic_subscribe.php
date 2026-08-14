<?php
require_once __DIR__ . '/../includes/auth_user.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('articles.php');

$topic  = trim($_POST['topic'] ?? '');
$return = $_POST['return'] ?? 'articles.php';
// Only allow redirecting back within this same app's public folder.
if ($return === '' || $return[0] === '/' || strpos($return, '://') !== false) {
    $return = 'articles.php';
}

if (!currentUser()) {
    $_SESSION['post_login_redirect'] = $return;
    redirect('login.php');
}

if ($topic !== '') {
    userCsrfCheck();
    toggleTopicSubscription((int)currentUser()['id'], $topic);
}

redirect($return);
