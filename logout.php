<?php
// logout.php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'config/config.php';
require_once 'includes/DatabaseConfig.php';

$auth = new SpaceNet\Auth();
$auth->logout();

header('Location: login.php?message=logged_out');
exit;

