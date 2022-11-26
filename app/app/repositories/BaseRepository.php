<?php

require_once '../app/core/LogManager.php';
require_once '../app/core/Database.php';

use Monolog\Logger;

/**
 * The base repository class that all repositories should extend
 * 
 * @link [Repository Design Pattern](https://medium.com/@pererikbergman/repository-design-pattern-e28c0f3e4a30)
 */
class BaseRepository
{
    protected Database $db;
    protected LogManager $logger;

    /**
     * The base repository constructor that sets the database connection and logger
     * 
     * @param boolean $isMocking
     */
    public function __construct()
    {
        $this->logger = new LogManager('php-repository-' . get_class($this));
        $this->initDb();
    }

    /**
     * Initializes the database connection
     */
    private function initDb()
    {
        try {
            $this->db = new Database();
        } catch (Exception $ex) {
            $this->logger->log('Unable to connect to the database. See inner exception: ' . $ex->getMessage(), Logger::ERROR);
            throw $ex;
        }
    }

    /**
     * Loads a model from the models folder (`app/models`) and returns it
     *
     * @param string $model The model to load
     * @return Model The model that was loaded
     */
    protected function loadModel(string $model)
    {
        return loadModelHelper($model, $this->logger);
    }

    /**
     * Loads a enum from the models folder (`app/models`) and returns it
     *
     * @param string $enum The enum to load
     * @param mixed $value The value to get from the enum
     * @return Enum The enum that was loaded
     */
    protected function loadEnum(string $enum, $value)
    {
        return loadEnumHelper($enum, $value, $this->logger);
    }

    /**
     * Throws an exception and logs it
     *
     * @param string $error The error message
     * @throws Exception The exception that was thrown
     */
    protected function throwError(string $error)
    {
        $this->logger->log($error, Logger::ERROR);
        throw new Exception($error);
    }
}
