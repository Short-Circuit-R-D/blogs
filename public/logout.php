<?php
require_once __DIR__ . '/../includes/auth_user.php';
unset($_SESSION['user']);
redirect('index.php');
