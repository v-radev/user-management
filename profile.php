<?php

require_once "src/init.php";

$userName = isset($_GET['user']) ? $_GET['user'] : FALSE;

if ( !$userName )
    redirectHome();


$user = new User( $userName );
if ( !$user->exists() ){
    die('User not found.');
} else {
    $data = $user->getData();
    ?>
    <h4><?= escape($data->id); ?></h4>
    <h3><?= escape($data->username); ?></h3>
    <?php
}
