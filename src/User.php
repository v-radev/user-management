<?php


class User {

    /** @var DB */
    protected $_db = NULL;

    /** @var DB */
    protected $_data = NULL;

    protected $_sessionName = 'sesUserLogin';
    protected $_isLoggedIn = FALSE;
    protected $_tableName = 'users';

    /**
     * If username or id is passed wil check in DB and will set the data
     * @param null $user Can be id or username string
     */
    function __construct( $user = NULL )
    {
        $this->_db = DB::getInstance();

        if ( !$user ){

            //If there is user in the session
            if ( Session::exists($this->_sessionName) ){
                $user = Session::get($this->_sessionName);

                if ( $this->find( $user ) ){
                    $this->_isLoggedIn = TRUE;
                } else {
                    Session::delete($this->_sessionName);
                }
            }
        } else {
            $this->find( $user );
        }
    }

    /**
     * @param string $user
     *
     * @return bool
     */
    public function find( $user )
    {
        $field = is_numeric($user) ? 'id' : 'username';
        $user = strtolower($user);

        $data = $this->_db->get($this->_tableName, [$field, '=', $user]);

        if ( $data->getCount() ){
            $this->_data = $data->getFirst();
            return TRUE;
        }
        return FALSE;

    }//END find()

    /**
     * @param string $fields
     * @example
     *  $this->create('users', ['username' => $name, 'password' => $pass])
     *
     * @throws Exception
     */
    public function create( $fields )
    {
        if ( $this->_db->insert($this->_tableName, $fields) )
            throw new Exception('Error creating a new user.');
    }


    /**
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function login( $username = NULL, $password = NULL )
    {
        sleep(3);

        //If no data passed bu I already found a user
        if ( !$username && !$password ){

            if ( !$this->exists() )
                return FALSE;

            $this->_isLoggedIn = TRUE;
            Session::put( $this->_sessionName, $this->_data->id );
            session_regenerate_id();
            return TRUE;

        }
        elseif( $username && $password ) {

            $user = $this->find( $username );

            if ( $user ){

                $dbPass = $this->_data->password;
                $inputPass = Hash::make($password, $this->_data->salt);

                if ( $dbPass === $inputPass ){

                    $this->_isLoggedIn = TRUE;
                    Session::put( $this->_sessionName, $this->_data->id );
                    session_regenerate_id();
                    return TRUE;
                }
                else {//Pass does not match
                    $this->_data = NULL;
                    return FALSE;
                }
            }
            else {//No user found()
                return FALSE;
            }
        }//END if username && password

        throw new InvalidArgumentException("Argument username or password is empty.");

    }//END login()

    /**
     * @return bool
     */
    public function exists()
    {
        return !empty($this->_data);
    }

    /**
     * Logout user. Other hacking measures handled in the logout page.
     */
    public function logout()
    {
        $this->_isLoggedIn = FALSE;
        $this->_data = NULL;
        Session::delete( $this->_sessionName );
    }

    /**
     * @param array $fields
     * @param int  $id
     *
     * @throws ErrorException
     */
    public function update( $fields, $id = NULL )
    {
        if ( !$id && $this->getIsLoggedIn() ){
            $id = $this->_data->id;
        }
        elseif ( !$id && !$this->getIsLoggedIn() ){
            throw new ErrorException('You can not update user without active login or without providing an id.');
        }

        if ( !$this->_db->update($this->_tableName, $id, $fields) )
            throw new ErrorException('There was a problem updating user data.');
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @return boolean
     */
    public function getIsLoggedIn()
    {
        return $this->_isLoggedIn;
    }

    /**
     * @return DB
     */
    public function getDb()
    {
        return $this->_db;
    }
} 
