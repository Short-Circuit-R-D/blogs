<?php
require_once __DIR__ . '/../includes/auth.php';
$who = currentAdmin()['username'] ?? 'guest';
adminAuditLog('logout', $who, 'Admin signed out');
$_SESSION = [];
session_destroy();
redirect('login.php');
