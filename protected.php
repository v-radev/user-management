<?php

require_once "src/init.php";


$user = new User();

if ( !$user->isLoggedIn() ){
    redirectHome();
}

if ( !$user->isInGroup( User::GROUP_ADMINS ) ){
    die("You are not allowed to visit this page.");
} 
