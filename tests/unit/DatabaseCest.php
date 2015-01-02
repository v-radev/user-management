<?php
use \UnitTester;

require_once $_SERVER['PWD'] ."/src/init.php";

class DatabaseCest
{

    /** @var DB */
    protected $db = NULL;

    protected $_usersTable = Config::USERS_TABLE;

    protected $_nextDbUserId = 7;

    protected $_firstDbUserId = 5;
    protected $_firstDbUsername = 'steven';

    protected $_newUsername = 'equivalent';
    protected $_newPassword = '123456';
    protected $_newSalt = 'LongLiveSacredGermany';

    protected $_validUserData = [];


    public function __construct()
    {
        $this->_validUserData = ['username' => $this->_newUsername, 'password' => $this->_newPassword, 'salt' => $this->_newSalt ];
    }

    protected function createUser(UnitTester $I, $parameters = [] )
    {
        $defaults = $this->_validUserData;
        $data = array_merge($defaults, $parameters);

        return $I->haveInDatabase($this->_usersTable, $data);
    }

    public function _before(UnitTester $I)
    {
        $this->db = DB::instance();
    }

    public function _after(UnitTester $I)
    {
    }

    public function testDbClassInstance(UnitTester $I)
    {
        $I->assertTrue( is_object($this->db) );
        $I->assertNotNull($this->db);
    }

    public function testGetMethodWhereCountException(UnitTester $I)
    {
        try {
            $this->db->get($this->_usersTable, ['inevitable']);
        } catch( Exception $e ) {
            $I->assertEquals('InvalidArgumentException', get_class($e));
            $I->assertEquals('Error constructing query! Expected where clause to have 3 values.', $e->getMessage());
        }
    }

    public function testGetMethodWhereOperatorException(UnitTester $I)
    {
        try {
            $this->db->get($this->_usersTable, ['inevitable', 'X', 'end']);
        } catch( Exception $e ) {
            $I->assertEquals('InvalidArgumentException', get_class($e));
            $I->assertEquals('Error constructing query! Invalid comparing operator in where clause.', $e->getMessage());
        }
    }

    public function testGetMethodWhereOperatorInValueException(UnitTester $I)
    {
        try {
            $this->db->get($this->_usersTable, ['inevitable', 'IN', 'end']);
        } catch( Exception $e ) {
            $I->assertEquals('InvalidArgumentException', get_class($e));
            $I->assertEquals('Error constructing query! Operator "IN" expects values to be in array.', $e->getMessage());
        }
    }

    public function testGetMethodFieldsArgument(UnitTester $I)
    {
        $db = $this->db;

        $db->get($this->_usersTable, ['id', '<', $this->_firstDbUserId + 1], 'id, username');
        $data = $db->first();

        $I->assertTrue($db->passes());
        $I->assertTrue( $db->count() == 1 );
        $I->assertTrue(is_object($data));
        $I->assertTrue( count((array)$data) == 2 );

        $I->assertEquals($this->_firstDbUserId, $data->id);
        $I->assertEquals($this->_firstDbUsername, $data->username);
    }

    public function testGetMethodWhereOperatorEquals(UnitTester $I)
    {
        $db = $this->db;
        $this->createUser($I);

        $db->get($this->_usersTable, ['username', '=', $this->_newUsername]);

        $I->assertTrue($db->passes());
        $I->assertTrue( $db->count() == 1 );
        $I->assertTrue(is_object($db->first()));
    }

    public function testGetMethodWhereOperatorLess(UnitTester $I)
    {
        $db = $this->db;

        $db->get($this->_usersTable, ['id', '<', $this->_firstDbUserId + 1]);

        $I->assertTrue($db->passes());
        $I->assertTrue( $db->count() == 1 );
        $I->assertTrue(is_object($db->first()));
    }

    public function testGetMethodWhereOperatorGreater(UnitTester $I)
    {
        $db = $this->db;

        $db->get($this->_usersTable, ['id', '>', $this->_firstDbUserId]);

        $I->assertTrue($db->passes());
        $I->assertTrue( $db->count() == 1 );
        $I->assertTrue(is_object($db->first()));
    }

    public function testGetMethodWhereOperatorGTE(UnitTester $I)
    {
        $db = $this->db;

        $db->get($this->_usersTable, ['id', '>=', $this->_firstDbUserId]);

        $I->assertTrue($db->passes());
        $I->assertTrue( $db->count() == 2 );
        $I->assertTrue( count($db->results()) == 2 );
    }

    public function testGetMethodWhereOperatorLTE(UnitTester $I)
    {
        $db = $this->db;

        $db->get($this->_usersTable, ['id', '<=', $this->_firstDbUserId + 1]);

        $I->assertTrue($db->passes());
        $I->assertTrue( $db->count() == 2 );
        $I->assertTrue( count($db->results()) == 2 );
    }

    public function testGetMethodWhereOperatorIn(UnitTester $I)
    {
        $db = $this->db;

        $db->get($this->_usersTable, ['id', 'IN', [$this->_firstDbUserId, $this->_firstDbUserId + 1]]);

        $I->assertTrue($db->passes());
        $I->assertFalse($db->fails());
        $I->assertTrue( $db->count() == 2 );
        $I->assertTrue( count($db->results()) == 2 );
    }

    public function testDeleteMethodWhereOperatorEquals(UnitTester $I)
    {
        $db = $this->db;

        $db->delete($this->_usersTable, ['id', '=', $this->_firstDbUserId]);

        $I->dontSeeInDatabase($this->_usersTable, ['id' => $this->_firstDbUserId]);
        $I->assertTrue($db->passes());
        $I->assertFalse($db->fails());
        $I->assertTrue( $db->errorInfo() == '');
        $I->assertFalse( $db->getError());
    }

    public function testDeleteMethodWhereOperatorIn(UnitTester $I)
    {
        $db = $this->db;

        $db->delete($this->_usersTable, ['id', 'IN', [$this->_firstDbUserId, $this->_firstDbUserId + 1]]);

        $I->dontSeeInDatabase($this->_usersTable, ['id' => $this->_firstDbUserId]);
        $I->dontSeeInDatabase($this->_usersTable, ['id' => $this->_firstDbUserId + 1]);
        $I->assertTrue($db->passes());
    }

    public function testDeleteMethodWhereOperatorLess(UnitTester $I)
    {
        $db = $this->db;

        $db->delete($this->_usersTable, ['id', '<', $this->_firstDbUserId + 1]);

        $I->dontSeeInDatabase($this->_usersTable, ['id' => $this->_firstDbUserId]);
        $I->seeInDatabase($this->_usersTable, ['id' => $this->_firstDbUserId + 1]);
        $I->assertTrue($db->passes());
    }

    public function testDeleteMethodWhereOperatorLTE(UnitTester $I)
    {
        $db = $this->db;

        $db->delete($this->_usersTable, ['id', '<=', $this->_firstDbUserId + 1]);

        $I->dontSeeInDatabase($this->_usersTable, ['id' => $this->_firstDbUserId]);
        $I->dontSeeInDatabase($this->_usersTable, ['id' => $this->_firstDbUserId + 1]);
        $I->assertTrue($db->passes());
    }

    public function testDeleteMethodWhereOperatorGreater(UnitTester $I)
    {
        $db = $this->db;

        $db->delete($this->_usersTable, ['id', '>', $this->_firstDbUserId]);

        $I->seeInDatabase($this->_usersTable, ['id' => $this->_firstDbUserId]);
        $I->dontSeeInDatabase($this->_usersTable, ['id' => $this->_firstDbUserId + 1]);
        $I->assertTrue($db->passes());
    }

    public function testDeleteMethodWhereOperatorGTE(UnitTester $I)
    {
        $db = $this->db;

        $db->delete($this->_usersTable, ['id', '>=', $this->_firstDbUserId]);

        $I->dontSeeInDatabase($this->_usersTable, ['id' => $this->_firstDbUserId]);
        $I->dontSeeInDatabase($this->_usersTable, ['id' => $this->_firstDbUserId + 1]);
        $I->assertTrue($db->passes());
    }

    public function testInsertMethodSuccess(UnitTester $I)
    {
        $db = $this->db;

        $id = $db->insert($this->_usersTable, $this->_validUserData);

        $I->assertTrue(is_numeric($id));
        $I->seeInDatabase($this->_usersTable, ['id' => $id]);
        $I->seeInDatabase($this->_usersTable, ['username' => $this->_newUsername]);
    }

    public function testInsertMethodFailQuery(UnitTester $I)
    {
        $db = $this->db;

        $id = $db->insert('hitler', $this->_validUserData);

        $I->assertFalse($id);
    }

    public function testInsertMethodFailFields(UnitTester $I)
    {
        $db = $this->db;

        try {
            $db->insert($this->_usersTable, []);
        } catch( Exception $e ) {
            $I->assertEquals('InvalidArgumentException', get_class($e));
            $I->assertEquals('Error inserting query. Expected some fields.', $e->getMessage());
        }
    }

    public function testUpdateMethodSuccess(UnitTester $I)
    {
        $db = $this->db;

        $db->update($this->_usersTable, $this->_firstDbUserId, ['username' => 'Zeke', 'password' => 'Sacr3DG3rmanY']);

        $I->seeInDatabase($this->_usersTable, ['username' => 'Zeke', 'password' => 'Sacr3DG3rmanY']);
    }


    public function testGetCustomMethodSimple(UnitTester $I)
    {
        $db = $this->db;

        $db->getCustom($this->_usersTable, 'id = ? AND username = ?', [$this->_firstDbUserId, $this->_firstDbUsername]);

        $I->assertTrue($db->passes());
        $I->assertFalse($db->fails());
        $I->assertTrue( $db->count() == 1 );
        $I->assertTrue( count($db->results()) == 1 );
        $I->assertTrue(is_object($db->first()));
    }

    public function testGetCustomMethodFields(UnitTester $I)
    {
        $db = $this->db;

        $db->getCustom($this->_usersTable, 'id = ? AND username = ?', [$this->_firstDbUserId, $this->_firstDbUsername], 'id, username');
        $data = $db->first();

        $I->assertTrue($db->passes());
        $I->assertFalse($db->fails());
        $I->assertTrue( $db->count() == 1 );
        $I->assertTrue( count($db->results()) == 1 );

        $I->assertTrue(is_object($data));
        $I->assertTrue( count((array)$data) == 2 );

        $I->assertEquals($this->_firstDbUserId, $data->id);
        $I->assertEquals($this->_firstDbUsername, $data->username);
    }

    public function testGetCustomMethodOrder(UnitTester $I)
    {
        $db = $this->db;

        $db->getCustom($this->_usersTable, 'id > 1', [$this->_firstDbUserId, $this->_firstDbUsername], 'id', 'ORDER BY id DESC');
        $data = $db->first();

        $I->assertTrue($db->passes());
        $I->assertFalse($db->fails());
        $I->assertTrue( count($db->results()) > 1 );

        $I->assertTrue(is_object($data));
        $I->assertTrue( count((array)$data) == 1 );

        $I->assertTrue( $data->id > $this->_firstDbUserId, 'Because of the descending order in the query the first result should have an id bigger than the first row in the table.' );
    }

    public function testFirstMethodNoResults(UnitTester $I)
    {
        $db = $this->db;

        $db->get($this->_usersTable, ['username', '=', $this->_newUsername]);

        $I->assertTrue($db->passes());
        $I->assertTrue( $db->count() == 0 );
        $I->assertTrue(is_array($db->first()));
    }

}