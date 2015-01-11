<?php

require_once "src/init.php";


$user = new User();

if ( !$user->isLoggedIn() ){
    redirectHome();
}

if ( !$user->isInGroup( User::ADMIN_GROUP ) ){
    die("You are not allowed to visit this page.");
} 
