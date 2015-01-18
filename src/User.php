<?php


/**
 * Class User
 * @author V.Radev <mail@radev.info>
 */
class User {

    /** Allowed actions */
    const ACTION_EDIT_OWN_PROFILE     = 'editOwnProfile';
    const ACTION_EDIT_FOREIGN_PROFILE = 'editForeignProfile';
    const ACTION_CREATE_USER          = 'createUser';
    const ACTION_DELETE_USER          = 'deleteUser';
    const ACTION_MANAGE_GROUPS        = 'manageGroups';

    const GROUP_ADMINS = 'Administrator';
    const GROUP_USERS = 'User';

    const DEFAULT_GROUP_ID = 2;

    protected $_allowedActions = [
        self::ACTION_EDIT_OWN_PROFILE,
        self::ACTION_EDIT_FOREIGN_PROFILE,
        self::ACTION_CREATE_USER,
        self::ACTION_DELETE_USER,
        self::ACTION_MANAGE_GROUPS
    ];

    /** @var DB */
    protected $_db = NULL;

    /** @var DB */
    protected $_data = NULL;

    protected $_isLoggedIn = FALSE;

    protected $_maxLoginAttempts = Config::LOGIN_ATTEMPTS;
    protected $_loginAttemptsTime = Config::LOGIN_ATTEMPTS_TIME;//minutes
    protected $_loginTimeFormat = 'Y-m-d H:i:s';

    protected $_sessionName = Config::USERS_SESSION_NAME;
    protected $_cookieName = Config::COOKIE_SESSION_NAME;

    protected $_tableName = Config::USERS_TABLE;
    protected $_sessionsTable = Config::SESSIONS_TABLE;
    protected $_attemptsTable = Config::ATTEMPTS_TABLE;

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
        $success = FALSE;
        $reason = '';

        //If no data passed but I already have a user
        if ( !$username && !$password ){

            //No data
            if ( !$this->exists() ) {
                $success = FALSE; goto returnStatement;
            }

            $this->_isLoggedIn = TRUE;
            Session::put( $this->_sessionName, $this->_data->id );
            session_regenerate_id();
            $success = TRUE; goto returnStatement;

        }
        elseif( $username && $password ) {

            $user = $this->find( $username );

            if ( $user ){

                //Check login attempts
                $attemptsCheck = $this->_db->get($this->_attemptsTable, ['user_id', '=', $this->_data->id]);
                //I have a row in DB
                if ( $attemptsCheck->count() ){
                    $attemptData = $attemptsCheck->first();

                    //Max attempts reached
                    if ( $attemptData->num_attempts >= $this->_maxLoginAttempts ){

                        $aTime = DateTime::createFromFormat($this->_loginTimeFormat, $attemptData->last_attempt);
                        $aTime->add( new DateInterval('PT'. $this->_loginAttemptsTime .'M') );
                        $nowTime = new DateTime('now');
                        //If X minutes haven't passed since the last attempt
                        if ( $nowTime < $aTime ){
                            $this->_data = NULL;
                            $success = FALSE; $reason = 'login_attempts'; goto returnStatement;
                        }
                    }//END max
                }//END result from db

                $dbPass = $this->_data->password;
                $inputPass = Hash::make($password, $this->_data->salt);

                //Compare encrypted passwords
                if ( $dbPass === $inputPass ){

                    //Delete login attempts
                    $this->_db->delete($this->_attemptsTable, ['user_id', '=', $this->_data->id]);

                    $this->_isLoggedIn = TRUE;
                    Session::put( $this->_sessionName, $this->_data->id );
                    session_regenerate_id();
                    $this->rememberUserInDb( $remember );
                    $success = TRUE; goto returnStatement;
                }
                else {//Pass does not match
                    $attemptsQuery = 'INSERT INTO '. $this->_attemptsTable .'
                            (user_id)
                            VALUES(?)
                            ON DUPLICATE KEY UPDATE num_attempts = num_attempts + 1';
                    $this->_db->query($attemptsQuery, [$this->_data->id]);
                    
                    sleep(4);//Slow brute force
                    $this->_data = NULL;
                    $success = FALSE; goto returnStatement;
                }
            }
            else {//No user found()
                $success = FALSE; goto returnStatement;
            }
        }//END if username && password

        throw new InvalidArgumentException("Argument username or password is empty.");

        returnStatement:
        return [ 'status' => $success, 'reason' => $reason ];

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

    /**
     * @param string $action
     * @return bool
     */
    public function isAllowedTo( $action )
    {
        if ( !$this->isLoggedIn() || !$this->getData() )
            throw new BadMethodCallException('User is not logged in.');

        if ( !in_array($action, $this->_allowedActions) )
            throw new BadMethodCallException('Action does not exist.');

        $groupId = $this->_data->group_id;

        //Get action id
        $actionData = $this->_db->get(Config::PERMISSIONS_TABLE, ['name', '=', $action]);

        if ( $actionData->fails() || !$actionData->count() )
            throw new InvalidArgumentException('This action does not exist in the DB');

        $actionData = $actionData->first();
        $permId = $actionData->id;

        $allowedQuery = $this->_db->getCustom(Config::GROUP_PERM_TABLE, 'group_id = ? AND perm_id = ?', [$groupId, $permId] );

        if ( !$allowedQuery ) return FALSE;

        return $this->_db->count() == 1;

    }//END isAllowedTo()

    public function isInGroup( $group )
    {
        if ( !$this->isLoggedIn() || !$this->getData() ){
            throw new BadMethodCallException('User is not logged in.');
        }

        if ( is_numeric($group) ){
            return $this->_data->group_id == $group;
        }

        //Get group id
        $groupData = $this->_db->get(Config::GROUPS_TABLE, ['name', '=', $group]);

        if ( $groupData->fails() || !$groupData->count() )
            throw new InvalidArgumentException('This group does not exist in the DB.');

        $groupData = $groupData->first();
        $groupId = $groupData->id;

        return $this->_data->group_id == $groupId;

    }//END isInGroup()
} 
