<?php

require_once "src/init.php";


if ( $_SERVER['REQUEST_METHOD'] === 'POST' ){

    if ( !Token::check(getInput('token')) )
        dieInvalidToken();
    //Validation
    if ( empty($_POST['username']) || empty($_POST['password']) )
        dieEmptyFields();


    $user = new User();
    $login = $user->login(getInput('username'), getInput('password'));

    if ( $login ){
        redirectHome();//success
    } else {
        die("No success.");
    }

}//END POST
?>
<form action="" method="post" autocomplete="off">

    <label for="username">Username</label>
    <input type="text" name="username" id="username"/>

    <label for="password">Password</label>
    <input type="password" name="password" id="password"/>

    <input type="hidden" name="token" value="<?= Token::generate(TRUE); ?>"/>
    <input type="submit" value="Log In"/>
</form>