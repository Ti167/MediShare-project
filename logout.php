<?php
// Secure Logout - Session Destruction
require_once 'helpers.php';
init_secure_session();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
