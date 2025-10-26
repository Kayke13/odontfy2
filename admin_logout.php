<?php
session_start();
require_once 'config/admin_auth.php';

AdminAuth::logout();
header('Location: admin_login.php');
exit;
?>
