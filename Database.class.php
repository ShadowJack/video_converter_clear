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
    public function prepare( $queryString )
    {
        return $this->dbh->prepare( $queryString );
    }
    
    /**
     * Executes statement
     *
     * @param PDOStatement $statement to execute 
     * @return PDOStatement $statement after execution
     */
    public function execute( $statement )
    {
        $statement->execute();
        return $statement;
    }
    
    /**
     * Queries the db
     *
     * @param string $queryString to query
     * @return PDOStatement result of the query
     */
    public function query( $queryString )
    {
        return $this->dbh->query( $queryString );
    }
    
    /**
     * Gets value from column
     *
     * @param PDOStatement $statement from wich to fetch
     * @return mixed value
     */
    public function fetchColumn( $statement )
    {
        return $statement->fetchColumn();
    }
    
    /**
     * Fetches all rows from statement
     *
     * @param PDOStatement $statement 
     * @return array of rows
     */
    public function fetchAll( $statement )
    {
        return $statement->fetchAll();
    }
    
    /**
     * Fetches one row from statement with options
     *
     * @param PDOStatement $statement
     * @param PDO::FETCH_* constant $options - type of result 
     * @return mixed result
     */
    public function fetch( $statement, $options = null )
    {
        return $statement->fetch( $options );
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