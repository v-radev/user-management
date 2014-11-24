<?php

require_once "src/init.php";


if ( $_SERVER['REQUEST_METHOD'] === 'POST' ){

    if ( !Token::check(getInput('token')) )
        dieInvalidToken();
    //Validation
    if ( empty($_POST['username']) || empty($_POST['password']) || empty($_POST['password_again']) )
        dieEmptyFields();

    //Example rules
    //$validate->check($_POST, [ 'username'       => [ 'required' => true, 'max' => 20, 'min' => 3, 'unique' => 'users'//Unique to users table ], 'password'       => [ 'required' => true, 'min'      => 6 ], 'password_again' => [ 'required' => true, 'matches'  => 'password' ] ]);

    $user = new User();
    $salt = Hash::salt(32);

    try {
        $userName = getInput( 'username' );
        $user->create([
            'username' => strtolower($userName),
            'password' => Hash::make(getInput( 'password' ), $salt),
            'salt'     => $salt
        ]);

    } catch( Exception $e ) {
        die('There was an error registering. Please try again later.');
    }

    Session::flash('home', 'You registered successfully!');
    redirectHome();

}//END POST

?>

<form action="" method="post" autocomplete="off">
    <label for="username">Username</label>
    <input type="text" name="username" id="username" value="<?= escape( getInput('username') ); ?>"/>

    <label for="password">Password</label>
    <input type="password" name="password" id="password"/>

    <label for="password_again">Password again</label>
    <input type="password" name="password_again" id="password_again"/>

    <input type="hidden" name="token" value="<?= Token::generate(TRUE); ?>"/>
    <input type="submit" value="Register"/>
</form>