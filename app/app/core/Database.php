<?php

/**
 * An error thrown by the Database
 */
class DatabaseException extends Exception
{
}

/**
 * PDO Database wrapper class
 * 
 * - Connect to Datebase
 * - Create Prepared Statements
 * - Bind Values
 * - Return Rows and Results
 */
class Database
{
    private $conn;
    private $stmt;

    public function __construct()
    {
        // Generate the DSN - Data Source Name
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';
        $options = array(
            //PDO::ATTR_PERSISTENT => true, // Persistent Connection
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw Exceptions on Errors
            PDO::FETCH_OBJ // Return Objects not Arrays
        );

        // Create a new PDO instance
        $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    /**
     * Dumps the last SQL query and its parameters 
     */
    public function debugDumpParams()
    {
        $this->stmt->debugDumpParams();
    }

    /**
     * Preparing the statement with the query
     *
     * @param string $sql The sql query to prepare
     */
    public function query(string $sql)
    {
        $this->stmt = $this->conn->prepare($sql);
    }

    /**
     * Binds a value to a corresponding named or question mark placeholder 
     * in the SQL statement that was used to prepare the statement.
     *
     * @param string $param The parameter that will be replaced in the SQL statement.
     * @param mixed $value The value to bind to the parameter.
     * @param int $type The PDO::PARAM_* constants that represents the type of the parameter. 
     */
    public function bind(string $param, $value, int $type = null)
    {
        // If type is not set - find out the type of the value
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }

        // Bind it to the statement
        $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * Execute the prepared statement
     *
     * @return integer
     */
    public function execute(): int
    {
        return $this->stmt->execute();
    }

    /**
     * The result as an array of objects
     *
     * @return array The result as an array of objects
     */
    public function all(): array
    {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    /**
     * The first row of the result set
     * 
     * @return object A single row as an object
     */
    public function single()
    {
        $this->execute();
        return $this->stmt->fetch();
    }

    /**
     * The number of rows affected by the last SQL statement
     *
     * @return int The number of rows
     */
    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }
}
