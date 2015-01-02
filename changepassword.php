<?php

require_once "src/init.php";


$user = new User();

if ( !$user->isLoggedIn() ){
    redirectHome();
}

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ){

    if ( !Token::check(getInput('token')) )
        dieInvalidToken();
    //Validation
    if ( empty($_POST['password_current']) || empty($_POST['password_new']) || empty($_POST['password_new_again']) )
        dieEmptyFields();

    $currentPass = $user->getData()->password;
    $inputPass = Hash::make( getInput('password_current'), $user->getData()->salt);

    if ( $inputPass !== $currentPass ){
        die('Your current password is wrong!');
    } else {

        $salt = Hash::salt(32);
        $user->update([
            'password' => Hash::make(getInput('password_new'), $salt),
            'salt'     => $salt
        ]);

        Session::flash('home', 'Your password has been updated.');
        redirectHome();
    }

}//END POST

?>

<form action="" method="post">
    <label for="password_current">Current pass</label>
    <input type="password" name="password_current" id="password_current"/>

    <label for="password_new">New pass</label>
    <input type="password" name="password_new" id="password_new"/>

    <label for="password_new_again">New pass again</label>
    <input type="password" name="password_new_again" id="password_new_again"/>

    <input type="hidden" name="token" value="<?= Token::generate(TRUE); ?>"/>
    <input type="submit" value="Change"/>

</form>