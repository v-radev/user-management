<?php
use \UnitTester;

require_once $_SERVER['PWD'] ."/src/init.php";

class UserCest
{

    protected $_sessionName = Config::USERS_SESSION_NAME;
    protected $_isLoggedInMethod = 'isLoggedIn';

    protected $_nextDbUserId = 9;

    protected $_firstDbUserId = 7;
    protected $_secondDbUserId = 8;

    protected $_firstDbUsername = 'roku';
    protected $_firstDbPass = '1234';
    protected $_secondDbUsername = 'zeke';

    protected $_nonExistingDbUsername = 'equivalent';


    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }

    public function testUserClass(UnitTester $I)
    {
        $u = new User();
        $I->assertTrue( is_object($u) );
        $I->assertNotNull($u);
    }

    public function testNotLoggedIn(UnitTester $I)
    {
        $u = new User();
        $I->assertFalse($u->{$this->_isLoggedInMethod}());
        $I->assertFalse( Session::exists( $this->_sessionName ) );
    }

    public function testFindingUserMethod(UnitTester $I)
    {
        $id = $this->_firstDbUserId;
        $name = $this->_firstDbUsername;
        $I->seeInDatabase('users', ['id' => $id, 'username' => $name]);
        $I->dontSeeInDatabase('users', ['id' => 65, 'username' => 'HitleR']);

        //Find by id
        $u = new User();
        $I->assertTrue($u->find($id));
        $data = $u->getData();
        $I->assertEquals($id, $data->id);
        $I->assertEquals($name, $data->username);
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        //Don't find
        $two = new User();
        $I->assertFalse($two->find(65));
        $I->assertFalse($two->find('HitleR'));

        //Find by username
        $to = new User();
        $I->assertTrue($to->find($name));
        $data = $to->getData();
        $I->assertEquals($id, $data->id);
        $I->assertEquals($name, $data->username);
    }


    public function testLoginFromClassInstance(UnitTester $I)
    {
        $idOne = $this->_firstDbUserId;
        $idTwo = $this->_secondDbUserId;
        $nameOne = $this->_firstDbUsername;
        $nameTwo = $this->_secondDbUsername;

        $I->seeInDatabase('users', ['id' => $idOne, 'username' => $nameOne]);
        $I->seeInDatabase('users', ['id' => $idTwo, 'username' => $nameTwo]);

        //By id //Not logged in
        $u = new User($idTwo);
        $data = $u->getData();
        $I->assertEquals($idTwo, $data->id);
        $I->assertEquals($nameTwo, $data->username);
        $I->assertFalse( $u->{$this->_isLoggedInMethod}() );
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        //By username //Not logged in
        $two = new User($nameOne);
        $data = $two->getData();
        $I->assertEquals($idOne, $data->id);
        $I->assertEquals($nameOne, $data->username);
        $I->assertFalse( $two->{$this->_isLoggedInMethod}() );
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        //Success login from session
        Session::put( $this->_sessionName, $idTwo );
        $to = new User();
        $I->assertTrue( $to->{$this->_isLoggedInMethod}(), 'User should be logged in because of session.' );
        $data = $to->getData();
        $I->assertEquals($idTwo, $data->id);
        $I->assertEquals($nameTwo, $data->username);
        $to->logout();

        //Fail login from session
        $I->assertFalse( Session::exists( $this->_sessionName ) );
        Session::put( $this->_sessionName, 55 );
        $I->assertTrue( Session::exists( $this->_sessionName ) );
        $me = new User();
        $I->assertFalse( Session::exists( $this->_sessionName ) );
    }

    public function testCreatingNewUser(UnitTester $I)
    {
        $name = $this->_nonExistingDbUsername;
        $I->dontSeeInDatabase('users', ['username' => $name]);

        $u = new User();

        $I->assertFalse( $u->{$this->_isLoggedInMethod}() );
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        $u->create([
            'username' => $name,
            'password' => 'NoStringsOnMe',
            'salt'     => 'pepper'
        ]);

        $I->seeInDatabase('users', ['username' => $name, 'password' => 'NoStringsOnMe']);
    }

    public function testLoginUserMethodFromExistingData(UnitTester $I)
    {
        //Existing data from find(), successful login
        $u = new User();

        $I->assertFalse( $u->{$this->_isLoggedInMethod}() );
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        $find = $u->find($this->_firstDbUserId);

        $I->assertTrue($find, 'User should be found.');
        $login = $u->login();

        $I->assertTrue($login['status'], 'User should be logged in.');
        $I->assertTrue($u->{$this->_isLoggedInMethod}(), '$_isLoggedIn should be true.');
        $I->assertTrue( Session::exists( $this->_sessionName ) );
        $I->assertNotNull( $u->getData() );
        $u->logout();

        $I->assertFalse( Session::exists( $this->_sessionName ) );
        $I->assertNull( $u->getData() );

        //No data, failed login
        $two = new User();
        $I->assertNull( $two->getData() );
        $I->assertFalse( $two->{$this->_isLoggedInMethod}() );

        $login = $two->login();
        $I->assertFalse($login['status']);
        $I->assertNull( $two->getData() );
        $I->assertFalse( $two->{$this->_isLoggedInMethod}() );
    }

    public function testLoginUserMethodWithPass(UnitTester $I)
    {
        $I->dontSeeInDatabase('users', ['username' => $this->_nonExistingDbUsername]);

        //Success login
        $u = new User();
        $login = $u->login($this->_firstDbUsername, $this->_firstDbPass);
        $I->assertTrue($login['status']);
        $I->assertTrue( $u->{$this->_isLoggedInMethod}() );
        $I->assertNotNull( $u->getData() );
        $u->logout();

        $I->assertFalse( Session::exists( $this->_sessionName ) );

        //Fail password
        $two = new User();
        $login = $two->login($this->_firstDbUsername, 'hacked');
        $I->assertFalse($login['status']);
        $I->assertFalse( $two->{$this->_isLoggedInMethod}() );
        $I->assertNull( $two->getData(), 'User failed to login, no data should exist.' );

        $I->assertFalse( Session::exists( $this->_sessionName ) );

        //Fail username
        $to = new User();
        $login = $to->login($this->_nonExistingDbUsername, 'GermanY');
        $I->assertFalse($login['status']);
        $I->assertFalse( $to->{$this->_isLoggedInMethod}() );
        $I->assertNull( $to->getData() );

        $I->assertFalse( Session::exists( $this->_sessionName ) );
    }

    public function testLoginException(UnitTester $I)
    {
        $u = new User();

        try {
            $login = $u->login($this->_firstDbUsername);
        } catch( InvalidArgumentException $e ) {
            $I->assertEquals("Argument username or password is empty.", $e->getMessage());
        }
    }

    public function testLoginAttempts(UnitTester $I)
    {
        $times = Config::LOGIN_ATTEMPTS;

        $u = new User();

        for ($i = 1; $i <= $times; $i++){

            //Try to login, but fail
            $login = $u->login($this->_firstDbUsername, 'HackinG');
            $I->assertFalse($login['status'], 'Login should fail.');
            $I->assertTrue($login['reason'] == '', 'Reason should be empty.');
            $I->assertFalse( $u->{$this->_isLoggedInMethod}(), 'User should not be logged in.');
            $I->assertNull( $u->getData(), 'There should be no user data.');

            $I->seeInDatabase(Config::ATTEMPTS_TABLE, ['user_id' => $this->_firstDbUserId, 'num_attempts' => $i] );
        }

        //Try to login one last time to get attempts error
        $login = $u->login($this->_firstDbUsername, 'HackinG');
        $I->assertFalse($login['status'], 'Login should fail.');
        $I->assertTrue($login['reason'] == 'login_attempts', 'Reason should be max login attempts reached.');
        $I->assertFalse( $u->{$this->_isLoggedInMethod}(), 'User should not be logged in.');
        $I->assertNull( $u->getData(), 'There should be no user data.');

        $I->seeInDatabase(Config::ATTEMPTS_TABLE, ['user_id' => $this->_firstDbUserId, 'num_attempts' => $times] );
    }

    public function testLogoutMethod(UnitTester $I)
    {
        $_SERVER['HTTP_USER_AGENT'] = '';

        //Success login
        $u = new User();
        $login = $u->login($this->_firstDbUsername, $this->_firstDbPass, TRUE);
        $I->assertTrue($login['status']);
        $I->assertTrue( $u->{$this->_isLoggedInMethod}() );
        $I->assertNotNull( $u->getData() );
        $u->logout();

        $I->assertFalse( Session::exists( $this->_sessionName ) );
        $I->assertFalse( $u->{$this->_isLoggedInMethod}() );
        $I->assertFalse( $u->exists() );
        $I->assertNull( $u->getData() );
        $I->dontSeeInDatabase(Config::SESSIONS_TABLE, ['user_id' => $this->_firstDbUserId]);
    }

    public function testUpdateMethodLoggedInUser(UnitTester $I)
    {
        $name = $this->_nonExistingDbUsername;
        $I->dontSeeInDatabase('users', ['username' => $name]);

        $u = new User();

        $I->assertFalse( $u->{$this->_isLoggedInMethod}() );
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        $u->create([
            'username' => $name,
            'password' => Hash::make('NoStringsOnMe', 'pepper'),
            'salt'     => 'pepper'
        ]);

        $I->seeInDatabase('users', ['username' => $name, 'salt' => 'pepper']);

        //Login new user
        $roku = new User();
        $login = $roku->login($name, 'NoStringsOnMe');
        $I->assertTrue($login['status']);
        $I->assertTrue( $roku->{$this->_isLoggedInMethod}() );
        $I->assertNotNull( $roku->getData() );

        $roku->update([
            'password' => 'NoOrdersFromYou',
            'salt'     => 'tlas'
        ]);

        $I->seeInDatabase('users', ['username' => $name, 'password' => 'NoOrdersFromYou', 'salt' => 'tlas']);
    }

    public function testUpdateMethodWithId(UnitTester $I)
    {
        $name = $this->_nonExistingDbUsername;
        $I->dontSeeInDatabase('users', ['username' => $name]);

        $u = new User();

        $I->assertFalse( $u->{$this->_isLoggedInMethod}() );
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        $u->create([
            'username' => $name,
            'password' => Hash::make('NoStringsOnMe', 'pepper'),
            'salt'     => 'pepper'
        ]);

        $I->seeInDatabase('users', ['username' => $name, 'salt' => 'pepper']);

        $u->update([
            'password' => 'NoOrdersFromYou',
            'salt'     => 'tlas'
        ], $this->_nextDbUserId);

        $I->seeInDatabase('users', ['username' => $name, 'password' => 'NoOrdersFromYou', 'salt' => 'tlas']);
    }

    public function testUpdateMethodException(UnitTester $I)
    {
        $u = new User();

        $I->assertFalse( $u->{$this->_isLoggedInMethod}() );
        $I->assertNull( $u->getData() );
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        try {
            $u->update([
                'password' => 'NoOrdersFromYou',
                'salt'     => 'tlas'
            ]);
        } catch( ErrorException $e ) {
            $I->assertEquals("You can not update user without active login or without providing an id.", $e->getMessage());
        }
    }

    public function testGetDbInstanceMethod(UnitTester $I)
    {
        $u = new User();
        $db = $u->getDb();
        $I->assertNotNull($db);
    }

    public function testRememberUserInDbMethod(UnitTester $I)
    {
        $I->dontSeeInDatabase(Config::SESSIONS_TABLE, ['user_id' => $this->_firstDbUserId]);
        $_SERVER['HTTP_USER_AGENT'] = '';

        //Success login with remember
        $u = new User();
        $login = $u->login($this->_firstDbUsername, $this->_firstDbPass, TRUE);
        $I->assertTrue($login['status']);
        $I->assertTrue( $u->{$this->_isLoggedInMethod}() );
        $I->assertNotNull( $u->getData() );

        $I->seeInDatabase(Config::SESSIONS_TABLE, ['user_id' => $this->_firstDbUserId]);
        //$I->seeCookie(Config::COOKIE_SESSION_NAME);

        $two = new User();
        $login = $two->login($this->_firstDbUsername, $this->_firstDbPass, TRUE);
        $I->assertTrue($login['status']);
        $I->assertTrue( $two->{$this->_isLoggedInMethod}() );
        $I->assertNotNull( $two->getData() );

        $I->seeInDatabase(Config::SESSIONS_TABLE, ['user_id' => $this->_firstDbUserId]);
        //$I->seeCookie(Config::COOKIE_SESSION_NAME);
    }

    public function testRecallUserMethod(UnitTester $I)
    {
        $_SERVER['HTTP_USER_AGENT'] = 'AirBison';
        $hash = Hash::unique();

        $u = new User();
        $loginFromCookie = $u->recallUser();
        $I->assertFalse($loginFromCookie, 'User should not be logged in because cookie does not exists.');

        $_COOKIE[Config::COOKIE_SESSION_NAME] = $hash;

        $loginFromCookie = $u->recallUser();
        $I->assertFalse($loginFromCookie, 'User should not be logged in because no rows will be found.');

        $I->haveInDatabase(Config::SESSIONS_TABLE, [
            'user_id'    => $this->_firstDbUserId,
            'hash'       => $hash,
            'user_agent' => 'Panzer'
        ]);

        $loginFromCookie = $u->recallUser();
        $I->assertFalse($loginFromCookie, 'User should not be logged in because user agent wont\' match.');
    }

    public function testIsAllowedToMethodFailNotLoggedIn(UnitTester $I)
    {
        $u = new User();
        try {
            $u->isAllowedTo('firebend');
        } catch( BadMethodCallException $e ) {
            $I->assertEquals("User is not logged in.", $e->getMessage());
        }
    }

    public function testIsAllowedToMethodFailUnknownAction(UnitTester $I)
    {
        $u = new User();
        $login = $u->login($this->_firstDbUsername, $this->_firstDbPass);
        $I->assertTrue($login['status']);

        try {
            $u->isAllowedTo('firebend');
        } catch( BadMethodCallException $e ) {
            $I->assertEquals("Action does not exist.", $e->getMessage());
        }
    }

    public function testIsAllowedToMethodSuccess(UnitTester $I)
    {
        $u = new User();
        $login = $u->login($this->_firstDbUsername, $this->_firstDbPass);
        $I->assertTrue($login['status']);

        $allowed = $u->isAllowedTo(User::ACTION_EDIT_OWN_PROFILE);
        $I->assertTrue($allowed);
    }

    public function testIsInGroupMethodFailNotLoggedIn(UnitTester $I)
    {
        $u = new User();
        try {
            $u->isInGroup('Firebenders');
        } catch( BadMethodCallException $e ) {
            $I->assertEquals("User is not logged in.", $e->getMessage());
        }
    }

    public function testIsInGroupMethodSuccessNumeric(UnitTester $I)
    {
        $u = new User();
        $login = $u->login($this->_firstDbUsername, $this->_firstDbPass);
        $I->assertTrue($login['status']);

        $inGroup = $u->isInGroup(2);
        $I->assertTrue($inGroup);
    }

    public function testIsInGroupMethodFailUnknownGroup(UnitTester $I)
    {
        $u = new User();
        $login = $u->login($this->_firstDbUsername, $this->_firstDbPass);
        $I->assertTrue($login['status']);

        try {
            $u->isInGroup('Firebenders');
        } catch( InvalidArgumentException $e ) {
            $I->assertEquals("This group does not exist in the DB.", $e->getMessage());
        }
    }

    public function testIsInGroupMethodSuccessNamed(UnitTester $I)
    {
        $u = new User();
        $login = $u->login($this->_firstDbUsername, $this->_firstDbPass);
        $I->assertTrue($login['status']);

        $inGroup = $u->isInGroup(User::GROUP_USERS);
        $I->assertTrue($inGroup);
    }

}