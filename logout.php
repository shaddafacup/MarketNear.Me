<?php
// logout.php
require_once 'includes/auth.php';

Auth::logout();
header("Location: /marketnearme/index.php");
exit();
?>