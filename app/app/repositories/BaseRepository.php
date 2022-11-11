<?php

require_once '../app/core/LogManager.php';

use Monolog\Logger;

/**
 * The base repository class that all repositories should extend
 * 
 * @link [Repository Design Pattern](https://medium.com/@pererikbergman/repository-design-pattern-e28c0f3e4a30)
 */
class BaseRepository
{
    protected PDO $db;
    protected LogManager $logger;

    /**
     * The base repository constructor that sets the database connection and logger
     * 
     * @param boolean $isMocking
     */
    public function __construct()
    {
        $this->initDb();
        $this->logger = new LogManager('php-repository-' . get_class($this));
    }

    /**
     * Initializes the database connection
     */
    private function initDb()
    {
        // $this->db = new PDO();
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
}
