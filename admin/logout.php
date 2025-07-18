<?php
require_once '../includes/session_config.php';

// Destroy admin session
destroySession();

// Redirect to admin login
header('Location: login.php');
exit;
?>