<?php
// Admin Secure Logout
require_once '../helpers.php';
init_secure_session();

$_SESSION = array();
session_destroy();

header("Location: login.php");
exit;
