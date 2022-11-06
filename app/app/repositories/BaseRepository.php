<?php

/**
 * The base repository class that all repositories should extend
 * 
 * @link [Repository Design Pattern](https://medium.com/@pererikbergman/repository-design-pattern-e28c0f3e4a30)
 */
class BaseRepository
{
    protected PDO $db;
    protected LogManager $logger;
    protected bool $isMocking;

    /**
     * The base repository constructor that sets the database connection and logger
     * 
     * @param boolean $isMocking
     */
    public function __construct(bool $isMocking)
    {
        $this->initDb();
        $this->logger = new LogManager('php-repository-' . get_class($this));
        $this->isMocking = $isMocking;
    }

    /**
     * Initializes the database connection
     */
    private function initDb()
    {
        // $this->db = new PDO();
    }
}
