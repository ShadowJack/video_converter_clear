<?php
require_once 'DatabaseException.class.php';

/**
 * Class contains low-level functions
 * to work with db
 */
class Database
{
    /**
     * Db credentials
     *
     * @var string
     */
    private $dbAddress = 'mysql:host=127.0.0.1;dbname=videos_db';
    /**
     * User from which db is running
     *
     * @var string
     */
    private $dbUser = 'dbowner';
    /**
     * dbUser's password
     *
     * @var string
     */
    private $dbPassword = 'password';
    /** @var PDO */
    private $dbh; 
    
    /**
     * Class constructor
     *
     * @param string $dbAddress - db credentials 
     * @param string $dbUser - db user
     * @param string $dbPassword 
     *
     * @throws DatabaseException
     */
    public function __construct( $dbAddress = null, $dbUser = null, $dbPassword = null )
    {
        if ( $dbAddress )
        {
            $this->dbAddress = $dbAddress;
        }
        if ( $dbUser )
        {
            $this->dbUser = $dbUser;
        }
        if ( $dbPassword )
        {
            $this->dbPassword = $dbPassword;
        }
        try
        {
            $this->dbh = new PDO( $this->dbAddress, $this->dbUser, $this->dbPassword );
        }
        catch (Exception $e)
        {
            throw new DatabaseException('Error, while connecting to db', 0, $e);
        }
        
    }
    
    /**
     * Prepares incoming string for execution
     *
     * @param string $queryString string to prepare
     * @return PDOStatement that can be executed later
     */
    private function prepare( $queryString )
    {
        return $this->dbh->prepare( $queryString );
    }
    
    /**
     * Prepares query and executes it
     *
     * @param string $queryString to execute 
     * @return PDOStatement $statement after execution
     */
    public function execute( $queryString )
    {
        /** @var PDOStatement */
        $statement = $this->prepare( $queryString );
        $statement->execute();
        return $statement;
    }
    
    /**
     * Executes query and returns value 
     * from the first column
     * @param string $queryString from wich to fetch
     * @return mixed value
     */
    public function fetchColumn( $queryString )
    {
        /** @var PDOStatement */
        $statement = $this->execute( $queryString );
        return $statement->fetchColumn();
    }
    
    /**
     * Executes query and returns all rows
     *
     * @param string $queryString to execute
     * @return array of rows
     */
    public function fetchAll( $queryString )
    {
        /** @var PDOStatement */
        $statement = $this->dbh->prepare( $queryString );
        $statement->execute();
        return $statement->fetchAll();
    }
    
    /**
     * Executes query and returns one row
     *
     * @param string $queryString to execute
     * @return array result / false on failure
     */
    public function fetch( $queryString )
    {
        /** @var PDOStatement */
        $statement = $this->dbh->prepare( $queryString );
        $statement->execute();
        return $statement->fetch( PDO::FETCH_ASSOC );
    }
    
    /**
     * Begins SQL transaction
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->dbh->beginTransaction();
    }
    
    /**
     * Commits changes to db
     *
     * @return void
     */
    public function commit()
    {
        $this->dbh->commit();
    }
    
    /**
     * Rolls back changes in db
     *
     * @return void
     */
    public function rollBack()
    {
        $this->dbh->rollBack();
    }
    
    /**
     * Returns id of the last row that
     * was inserted or updated during 
     * current connection to db
     *
     * @return string
     */
    public function lastInsertId()
    {
        return $this->dbh->lastInsertId();
    }
}
?>
