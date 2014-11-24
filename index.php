<?php

require_once "src/init.php";

if ( Session::exists('home') ){
    echo Session::flash('home');
}

$user = new User();

if ( $user->getIsLoggedIn() ){
    $u = $user->getData();
?>

    <p>Hello <a href="profile.php?user=<?= $u->username; ?>"><?= $u->username; ?></a>!</p>

    <ul>
        <li><a href="logout.php">Log out</a></li>
        <li><a href="changepassword.php">Change password</a></li>
    </ul>

<?php
} else {
?>
    <p>You need to <a href="login.php">log in</a> or <a href="register.php">register</a>.</p>
<?php
}
