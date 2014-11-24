<?php

session_start();

$path = realpath( dirname(__FILE__) );

spl_autoload_register(function($name) use ($path){
    $file = $path . DIRECTORY_SEPARATOR . $name .".php";
    if ( file_exists( $file ) ){
        require_once $file;
    }
});

require_once "functions.php";
require_once "c3.php";