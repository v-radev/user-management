<?php


class DB {
    
    /** @var int Last inserted id */
    public $lastId = 0;

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

    protected function __construct(){
        try {
            $this->_pdo = new PDO('mysql:host='. $this->_mysqlHost .';dbname='. $this->_dbName .'', $this->_dbUser, $this->_dbPass);
        } catch( PDOException $e ) {
            die("PDO Exception");
        }
    }

    /**
     * @return DB
     */
    public static function getInstance()
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
    public function action( $action, $table, $where )
    {
        $operators = ['=', '<', '>', '>=', '<='];

        if ( count($where) != 3 )
            throw new InvalidArgumentException('Error constructing query! Expected where clause to have 3 values.');

        $field = $where[0];
        $operator = $where[1];
        $value = $where[2];

        if ( !in_array($operator, $operators) )
            throw new InvalidArgumentException('Error constructing query! Invalid comparing operator in where clause.');

        $sql = "{$action} FROM {$table} WHERE {$field} {$operator} ?";

        $query = $this->query($sql, [$value]);

        if ( $query->getError() )
            throw new ErrorException('Error executing query.');

        return $this;

    }//END action()


    /**
     * @param string $table
     * @param array $where
     *
     * @example
     *   $this->get('users', ['username', '=', $user]);
     *
     * @return DB
     * @throws ErrorException
     */
    public function get( $table, $where )
    {
        return $this->action('SELECT *', $table, $where );
    }

    /**
     * @param string $table
     * @param array $where
     *
     * @example
     *   $this->delete('users', ['username', '=', $user]);
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
     * if ( !$this->insert('users', ['username' => $name, 'password' => $pass]) ){
     *   throw new Exception('Error creating a new user.');
     * }
     *
     * @return bool
     * @throws ErrorException
     */
    public function insert( $table, $fields )
    {
        if ( empty($fields) )
            throw new InvalidArgumentException('Error inserting query. Expected some fields.');

            $keys = array_keys($fields);
            $values = '?' . str_repeat(", ?", count($fields) - 1);

            $sql = "INSERT INTO {$table} (`". implode('`,`', $keys) ."`) VALUES ({$values})";

            $query = $this->query($sql, $fields);
            
            $this->lastId = $this->_pdo->lastInsertId();

            return $query->getError();

    }//END insert()

    /**
     * @param string $table
     * @param int    $id
     * @param array  $fields
     *
     * @return bool
     * @throws ErrorException
     */
    public function update( $table, $id, $fields )
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

        if ( !$this->query( $sql, $fields )->getError() ){
            return TRUE;
        }

        throw new ErrorException('Error updating query!');
    }


    /**
     * @return mixed
     */
    public function getResults()
    {
        return $this->_result;
    }


    /**
     * @return mixed
     */
    public function getFirst( ){
        $data = $this->getResults();
        return isset($data[0]) ? $data[0] : '';
    }


    /**
     * @return int
     */
    public function getCount()
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
     * @return string
     */
    public function getErrorInfo()
    {
        return $this->_errorInfo;
    }
}
