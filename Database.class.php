<?php
/**
 * Class contains low-level functions
 * to work with db
 */
class Database
{
    private $dbAddress = 'mysql:host=127.0.0.1;dbname=videos_db';
    private $dbUser = 'dbowner';
    private $dbPassword = 'password';
    private $dbh; 
    
    /**
     * Class constructor
     *
     * @param string $dbAddress - db credentials 
     * @param string $dbUser - db user
     * @param string $dbPassword 
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
    }
    
    /**
     * Connects to db
     *
     * @return void
     */
    public function connect()
    {
        $this->dbh = new PDO( $this->dbAddress, $this->dbUser, $this->dbPassword );
    }
    
    /**
     * Disconnects from db
     *
     * @return void
     */
    public function disconnect()
    {
        $this->dbh = null;
    }
    
    /**
     * Checks if there is live connection to db
     *
     * @return boolean connected
     */
    public function isConnected()
    {
        return ( $this->dbh !== null );
    }
    
    /**
     * Prepares incoming string for execution
     *
     * @param string $queryString string to prepare
     * @return PDOStatment that can be executed later
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
        $statement = $this->dbh->prepare( $queryString );
        $statement->execute();
        return $statement->fetchAll();
    }
    
    /**
     * Executes query and returns one row
     *
     * @param string $queryString to execute
     * @return array result
     */
    public function fetch( $queryString )
    {
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
}
?>