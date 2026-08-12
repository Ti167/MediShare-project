<?php
// Dismiss announcement — stores in session
require_once '../helpers.php';
init_secure_session();
if (isset($_GET['id'])) {
    $_SESSION['dismissed_announcement'] = (int)$_GET['id'];
}
echo 'ok';
