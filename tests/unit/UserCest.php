<?php
use \UnitTester;

require_once $_SERVER['PWD'] ."/src/init.php";

class UserCest
{

    protected $_sessionName = 'sesUserLogin';

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
        $I->assertFalse($u->getIsLoggedIn());
        $I->assertFalse( Session::exists( $this->_sessionName ) );
    }

    public function testFindingUserMethod(UnitTester $I)
    {
        $I->seeInDatabase('users', ['id' => 5, 'username' => 'steven']);
        $I->dontSeeInDatabase('users', ['id' => 65, 'username' => 'HitleR']);

        //Find by id
        $u = new User();
        $I->assertTrue($u->find(5));
        $data = $u->getData();
        $I->assertEquals(5, $data->id);
        $I->assertEquals('steven', $data->username);
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        //Don't find
        $two = new User();
        $I->assertFalse($two->find(65));
        $I->assertFalse($two->find('HitleR'));

        //Find by username
        $to = new User();
        $I->assertTrue($to->find('steven'));
        $data = $to->getData();
        $I->assertEquals(5, $data->id);
        $I->assertEquals('steven', $data->username);
    }


    public function testLoginFromClassInstance(UnitTester $I)
    {
        $I->seeInDatabase('users', ['id' => 6, 'username' => 'koko']);
        $I->seeInDatabase('users', ['id' => 5, 'username' => 'steven']);

        //By id //Not logged in
        $u = new User(6);
        $data = $u->getData();
        $I->assertEquals(6, $data->id);
        $I->assertEquals('koko', $data->username);
        $I->assertFalse( $u->getIsLoggedIn() );
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        //By username //Not logged in
        $two = new User('steven');
        $data = $two->getData();
        $I->assertEquals(5, $data->id);
        $I->assertEquals('steven', $data->username);
        $I->assertFalse( $two->getIsLoggedIn() );
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        //Success login from session
        Session::put( $this->_sessionName, 6 );
        $to = new User();
        $I->assertTrue( $to->getIsLoggedIn(), 'User should be logged in because of session.' );
        $data = $to->getData();
        $I->assertEquals(6, $data->id);
        $I->assertEquals('koko', $data->username);
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

        $I->dontSeeInDatabase('users', ['username' => 'roku']);

        $u = new User();

        $I->assertFalse( $u->getIsLoggedIn() );
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        $u->create([
            'username' => 'roku',
            'password' => 'NoStringsOnMe',
            'salt'     => 'pepper'
        ]);

        $I->seeInDatabase('users', ['username' => 'roku', 'password' => 'NoStringsOnMe']);
    }

    public function testLoginUserMethodFromExistingData(UnitTester $I)
    {
        //Existing data from find(), successful login
        $u = new User();

        $I->assertFalse( $u->getIsLoggedIn() );
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        $find = $u->find(5);

        $I->assertTrue($find, 'User should be found.');
        $login = $u->login();

        $I->assertTrue($login, 'User should be logged in.');
        $I->assertTrue($u->getIsLoggedIn(), '$_isLoggedIn should be true.');
        $I->assertTrue( Session::exists( $this->_sessionName ) );
        $I->assertNotNull( $u->getData() );
        $u->logout();

        $I->assertFalse( Session::exists( $this->_sessionName ) );
        $I->assertNull( $u->getData() );

        //No data, failed login
        $two = new User();
        $I->assertNull( $two->getData() );
        $I->assertFalse( $two->getIsLoggedIn() );

        $login = $two->login();
        $I->assertFalse($login);
        $I->assertNull( $two->getData() );
        $I->assertFalse( $two->getIsLoggedIn() );
    }

    public function testLoginUserMethodWithPass(UnitTester $I)
    {
        $I->dontSeeInDatabase('users', ['username' => 'hitler']);

        //Success login
        $u = new User();
        $login = $u->login('steven', '223344');
        $I->assertTrue($login);
        $I->assertTrue( $u->getIsLoggedIn() );
        $I->assertNotNull( $u->getData() );
        $u->logout();

        $I->assertFalse( Session::exists( $this->_sessionName ) );

        //Fail password
        $two = new User();
        $login = $two->login('steven', 'hacked');
        $I->assertFalse($login);
        $I->assertFalse( $two->getIsLoggedIn() );
        $I->assertNull( $two->getData(), 'User failed to login, no data should exist.' );

        $I->assertFalse( Session::exists( $this->_sessionName ) );

        //Fail username
        $to = new User();
        $login = $to->login('hitler', 'GermanY');
        $I->assertFalse($login);
        $I->assertFalse( $to->getIsLoggedIn() );
        $I->assertNull( $to->getData() );

        $I->assertFalse( Session::exists( $this->_sessionName ) );
    }

    public function testLoginException(UnitTester $I)
    {
        $u = new User();

        try {
            $login = $u->login('steven');
        } catch( InvalidArgumentException $e ) {
            $I->assertEquals("Argument username or password is empty.", $e->getMessage());
        }
    }

    public function testLogoutMethod(UnitTester $I)
    {
        //Success login
        $u = new User();
        $login = $u->login('steven', '223344');
        $I->assertTrue($login);
        $I->assertTrue( $u->getIsLoggedIn() );
        $I->assertNotNull( $u->getData() );
        $u->logout();

        $I->assertFalse( Session::exists( $this->_sessionName ) );
        $I->assertFalse( $u->getIsLoggedIn() );
        $I->assertFalse( $u->exists() );
        $I->assertNull( $u->getData() );
    }


    public function testUpdateMethodLoggedInUser(UnitTester $I)
    {
        $I->dontSeeInDatabase('users', ['username' => 'roku']);

        $u = new User();

        $I->assertFalse( $u->getIsLoggedIn() );
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        $u->create([
            'username' => 'roku',
            'password' => Hash::make('NoStringsOnMe', 'pepper'),
            'salt'     => 'pepper'
        ]);

        $I->seeInDatabase('users', ['username' => 'roku', 'salt' => 'pepper']);

        //Login new user
        $roku = new User();
        $login = $roku->login('roku', 'NoStringsOnMe');
        $I->assertTrue($login);
        $I->assertTrue( $roku->getIsLoggedIn() );
        $I->assertNotNull( $roku->getData() );

        $roku->update([
            'password' => 'NoOrdersFromYou',
            'salt'     => 'tlas'
        ]);

        $I->seeInDatabase('users', ['username' => 'roku', 'password' => 'NoOrdersFromYou', 'salt' => 'tlas']);
    }

    public function testUpdateMethodWithId(UnitTester $I)
    {
        $I->dontSeeInDatabase('users', ['username' => 'roku']);

        $u = new User();

        $I->assertFalse( $u->getIsLoggedIn() );
        $I->assertFalse( Session::exists( $this->_sessionName ) );

        $u->create([
            'username' => 'roku',
            'password' => Hash::make('NoStringsOnMe', 'pepper'),
            'salt'     => 'pepper'
        ]);

        $I->seeInDatabase('users', ['username' => 'roku', 'salt' => 'pepper']);

        $u->update([
            'password' => 'NoOrdersFromYou',
            'salt'     => 'tlas'
        ], 8);

        $I->seeInDatabase('users', ['username' => 'roku', 'password' => 'NoOrdersFromYou', 'salt' => 'tlas']);
    }

    public function testUpdateMethodException(UnitTester $I)
    {
        $u = new User();

        $I->assertFalse( $u->getIsLoggedIn() );
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

}