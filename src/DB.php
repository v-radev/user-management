<?php


class DB {

    /** @var DB */
    protected static $_instance = NULL;

    /** @var PDO */
    protected $_pdo;

    protected $_count = 0;
    protected $_error = FALSE;
    protected $_errorInfo = '';
    protected $_query;
    protected $_result;

    protected $_mysqlHost = Config::DB_HOST;
    protected $_dbName = Config::DB_NAME;
    protected $_dbUser = Config::DB_USER;
    protected $_dbPass = Config::DB_PASS;

    protected function __construct()
    {
        try {
            $this->_pdo = new PDO('mysql:host='. $this->_mysqlHost .';dbname='. $this->_dbName .'', $this->_dbUser, $this->_dbPass);
        } catch( PDOException $e ) {
            die("PDO Exception");
        }
    }

    /**
     * @return DB
     */
    public static function instance()
    {
        if ( !static::$_instance ){
            static::$_instance = new static();
        }

        return static::$_instance;
    }

    /**
     * @param string $query
     * @param array $params
     *
     * @return $this
     */
    public function query( $query, $params = [] )
    {
        $this->_error = FALSE;
        $this->_errorInfo = '';

        $this->_query = $this->_pdo->prepare($query);
        
        if ( $this->_query ){
            if ( !empty($params) ){
                $x = 1;
                foreach ( $params as $param ) {
                    $this->_query->bindValue($x, $param);
                    $x++;
                }
            }

            if ( $this->_query->execute() ){
                $this->_result = $this->_query->fetchAll(PDO::FETCH_OBJ);
                $this->_count = $this->_query->rowCount();
            } else {
                $this->_error = TRUE;
                $this->_errorInfo = $this->_query->errorInfo();
            }

        }//END if query

        return $this;
    }//END query()

    /**
     * @param string $action
     * @param string $table
     * @param array $where
     *
     * @example
     *  $this->action('SELECT *', 'users', ['username', '=', $user] )
     *
     * @return $this
     * @throws ErrorException
     */
    public function action( $action, $table, array $where )
    {
        $operators = ['=', '<', '>', '>=', '<=', 'IN'];

        if ( count($where) != 3 )
            throw new InvalidArgumentException('Error constructing query! Expected where clause to have 3 values.');

        $field = $where[0];
        $operator = $where[1];
        $value = $where[2];

        if ( !in_array($operator, $operators) )
            throw new InvalidArgumentException('Error constructing query! Invalid comparing operator in where clause.');

        $sql = "{$action} FROM {$table} WHERE {$field} {$operator} ?";

        $this->query($sql, [$value]);

        return $this;

    }//END action()

    /**
     * @param string $table
     * @param array  $where
     * @param string $fields
     *
     * @example
     *   $this->get('users', ['username', '=', $user]);
     *   $this->get('users', ['id', '=', '5'], 'username, password');
     *   if ( $this->fails() ){ //error }
     *
     * @return DB
     */
    public function get( $table, array $where, $fields = '*' )
    {
        return $this->action('SELECT ' . $fields, $table, $where );
    }

    /**
     * @param string $table
     * @param array $where
     *
     * @example
     *   $this->delete('users', ['username', '=', $user]);
     *   if ( $this->fails() ){ //error }
     *
     * @return DB
     * @throws ErrorException
     */
    public function delete( $table, $where )
    {
        return $this->action('DELETE', $table, $where );
    }

    /**
     * @param string $table
     * @param array $fields
     *
     * @example
     * if ( $this->insert('users', ['username' => $name, 'password' => $pass]) === FALSE ){
     *   throw new Exception('Error creating a new user.');
     * }
     *
     * @return bool|int Last inserted ID
     * @throws ErrorException
     */
    public function insert( $table, $fields )
    {
        if ( empty($fields) )
            throw new InvalidArgumentException('Error inserting query. Expected some fields.');

        $keys = array_keys($fields);
        $values = '?' . str_repeat(", ?", count($fields) - 1);

        $sql = "INSERT INTO {$table} (`". implode('`,`', $keys) ."`) VALUES ({$values})";

        $this->query($sql, $fields);

        return $this->passes() ? $this->_pdo->lastInsertId() : FALSE;

    }//END insert()

    /**
     * @param string $table
     * @param int    $id
     * @param array  $fields
     *
     * @example
     *  if ( !$this->_db->update('users', '1', ['password' => '1234']) ){ //error }
     *
     * @return bool
     * @throws ErrorException
     */
    public function update( $table, $id, array $fields )
    {
        $set = '';
        $x = 1;

        foreach ( $fields as $name => $value ) {
            $set .= "{$name} = ?";
            if ( $x < count($fields) ){
                $set .= ', ';
            }
            $x++;
        }

        $sql = "UPDATE {$table} SET {$set} WHERE id = {$id}";

        $this->query( $sql, $fields );

        return $this->passes();
    }//END update()

    /**
     * @param string $table
     * @param string $where
     * @param array  $values
     * @param string $fields
     * @param string $extra
     *
     * @example
     *  $this->getCustom('users', 'id = ? AND username = ?', [$id, $username] );
     *  $this->getCustom('users', 'id > ?', [$id], 'username', 'ORDER BY id DESC');
     *
     * @return mixed
     */
    public function getCustom( $table, $where, array $values, $fields = '*', $extra = '' )
    {
        $action = 'SELECT ' . $fields;

        $sql = "{$action} FROM {$table} WHERE {$where} {$extra}";

        $this->query($sql, $values);

        return $this->passes();
    }//END getCustom()

    /**
     * @return mixed
     */
    public function results()
    {
        return $this->_result;
    }

    /**
     * @return mixed
     */
    public function first()
    {
        $data = $this->results();

        if ( empty($data) ){
            $data = [];
        }
        elseif ( isset($data[0]) ){
            $data = $data[0];
        }
        else {
            $data = array_shift($data);
        }

        return $data ;
    }//END first()

    /**
     * @return int
     */
    public function count()
    {
        return $this->_count;
    }

    /**
     * @return bool
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * @return bool
     */
    public function fails()
    {
        return $this->_error;
    }

    /**
     * @return bool
     */
    public function passes()
    {
        return !$this->_error;
    }

    /**
     * @return string
     */
    public function errorInfo()
    {
        return $this->_errorInfo;
    }
}
