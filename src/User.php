<?php


class User {

    /** @var DB */
    protected $_db = NULL;

    /** @var DB */
    protected $_data = NULL;

    protected $_isLoggedIn = FALSE;

    protected $_sessionName = Config::USERS_SESSION_NAME;
    protected $_cookieName = Config::COOKIE_SESSION_NAME;

    protected $_tableName = Config::USERS_TABLE;
    protected $_sessionsTable = Config::SESSIONS_TABLE;

    /**
     * If username or id is passed wil check in DB and will set the data
     * @param null $user Can be id or username string
     */
    public function __construct( $user = NULL )
    {
        $this->_db = DB::instance();

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
     * @return bool
     */
    public function find( $user )
    {
        $field = is_numeric($user) ? 'id' : 'username';
        $user = strtolower($user);

        $data = $this->_db->get($this->_tableName, [$field, '=', $user]);

        if ( $data->passes() && $data->count() ){
            $this->_data = $data->first();
            return TRUE;
        }
        return FALSE;

    }//END find()

    /**
     * @param string $fields
     *
     * @example
     *  $this->create('users', ['username' => $name, 'password' => $pass])
     *
     * @throws Exception
     * @return int Last inserted ID
     */
    public function create( $fields )
    {
        $create = $this->_db->insert($this->_tableName, $fields);

        if ( $create === FALSE ) throw new Exception('Error creating a new user.');

        return $create;
    }


    /**
     * @param string $username
     * @param string $password
     * @param bool   $remember
     *
     * @return bool
     */
    public function login( $username = NULL, $password = NULL, $remember = FALSE )
    {
        //If no data passed but I already have a user
        if ( !$username && !$password ){

            //No data
            if ( !$this->exists() ) return FALSE;

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

                //Compare encrypted passwords
                if ( $dbPass === $inputPass ){

                    $this->_isLoggedIn = TRUE;
                    Session::put( $this->_sessionName, $this->_data->id );
                    session_regenerate_id();
                    $this->rememberUserInDb( $remember );
                    return TRUE;
                }
                else {//Pass does not match
                    //TODO
                    //sleep(4);//Slow brute force
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
        $this->_db->delete($this->_sessionsTable, ['user_id', '=', $this->_data->id]);

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
        if ( !$id && $this->isLoggedIn() ){
            $id = $this->_data->id;
        }
        elseif ( !$id && !$this->isLoggedIn() ){
            throw new ErrorException('You can not update user without active login or without providing an id.');
        }


        if ( !$this->_db->update($this->_tableName, $id, $fields) )
            throw new ErrorException('There was a problem updating the user data.');
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
    public function isLoggedIn()
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

    /**
     * @param bool $remember
     */
    protected function rememberUserInDb( $remember )
    {
        if ( $remember ){

            $hashCheck = $this->_db->get($this->_sessionsTable, ['user_id', '=', $this->_data->id]);
            //Query fails
            if ( $hashCheck->fails() ) return;

            //No record in db
            if ( !$hashCheck->count() ){

                $hash = Hash::unique();
                $this->_db->insert( $this->_sessionsTable, [
                    'user_id'    => $this->_data->id,
                    'hash'       => $hash,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ]);

            } else {
                $hash = $hashCheck->first()->hash;
            }

            $cookieLifeDays = 7;
            Cookie::put($this->_cookieName, $hash, $cookieLifeDays);
        }
    }//END rememberUserInDb()

    /**
     * @return bool
     */
    public function recallUser()
    {
        //User has remember cookie and is not logged in
        if ( Cookie::exists( $this->_cookieName ) && !Session::exists( $this->_sessionName ) ){

            $rememberHash = Cookie::get( $this->_cookieName );

            $hashCheck = $this->_db->get(Config::SESSIONS_TABLE, ['hash', '=', $rememberHash]);
            //Query fails
            if ( $hashCheck->fails() ) return FALSE;

            if ( $hashCheck->count() ){

                $hashData = $hashCheck->first();

                //Different user agent
                if ( $hashData->user_agent != $_SERVER['HTTP_USER_AGENT'] ) {
                    $this->_db->delete($this->_sessionsTable, ['user_id', '=', $hashData->user_id]);
                    Cookie::delete( $this->_cookieName );
                    return FALSE;
                }

                $rememberUser = new User( $hashData->user_id );
                $rememberUser->login();
                return TRUE;
            }
        }
        return FALSE;
    }//END recallUser()
} 
