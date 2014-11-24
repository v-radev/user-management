<?php


function escape( $string )
{
    return htmlentities( $string, ENT_QUOTES, 'UTF-8' );
}

function getInput( $name )
{
    return isset($_POST[$name]) ? $_POST[$name] : '';
}

function dieInvalidToken()
{
    die('Invalid token. Please try again.');
}

function redirectHome()
{
    header('Location: index.php');
    die();
}

function dieEmptyFields()
{
    die('All fields are required!');
}