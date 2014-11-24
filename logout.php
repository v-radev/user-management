<?php

require_once "src/init.php";

$user = new User();

if ( $user->getIsLoggedIn() ){
    $user->logout();
}

if ( isset($_COOKIE[session_name()]) ){
    setcookie(session_name(), '', time() - 86400, '/');
}
session_unset();
session_destroy();
$_SESSION = [];

redirectHome();