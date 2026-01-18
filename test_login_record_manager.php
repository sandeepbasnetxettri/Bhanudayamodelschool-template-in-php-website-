<?php
session_start();

// Set admin session for testing (remove this in production)
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['user_name'] = 'Test Admin';
$_SESSION['user_email'] = 'admin@test.com';

// Redirect to the login record manager
header('Location: auth/php/login-record-manager.php');
exit;
?>