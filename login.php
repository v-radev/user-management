<?php

require_once "src/init.php";

$user = new User();
if ( $user->isLoggedIn() ){
    redirectHome();
}

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ){

    if ( !Token::check(getInput('token')) )
        dieInvalidToken();
    //Validation
    if ( empty($_POST['username']) || empty($_POST['password']) )
        dieEmptyFields();

    $remember = getInput('remember') === 'on';

    $user = new User();
    $login = $user->login(getInput('username'), getInput('password'), $remember);

    if ( $login['status'] ){
        redirectHome();//success
    } else {
        if ( isset($login['reason']) && $login['reason'] == 'login_attempts' ){
            die("Login attempts reached, please try again after ". Config::LOGIN_ATTEMPTS_TIME ." minutes.");
        }
        die("No success.");
    }

}//END POST
?>
<form action="" method="post" autocomplete="off">

    <label for="username">Username</label>
    <input type="text" name="username" id="username"/>

    <label for="password">Password</label>
    <input type="password" name="password" id="password"/>

    <label for="remember">
        <input type="checkbox" name="remember" id="remember"/> Remember me
    </label>

    <input type="hidden" name="token" value="<?= Token::generate(TRUE); ?>"/>
    <input type="submit" value="Log In"/>
</form>