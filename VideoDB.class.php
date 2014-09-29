<?php

require_once 'Database.class.php';
/**
 * Class that manipulates with data in the db
 * Schema:
 * id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
 * title VARCHAR(100)
 * FLV VARCHAR(100)
 * MP4 VARCHAR(100)
 * created DATETIME DEFAULT NULL
 * status VARCHAR(1) - c: converting, f - finished, q - queued
 *
 */
class VideoDB
{
    
    private $database;
    
    /**
     * Constructor
     *
     * @param Database $db
     */
    public function __construct( $db = null )
    {
        if ( $db !== null )
        {
            $this->database = $db;
        }
        else
        {
            $this->database = new Database();
        }
    }
    
    /**
     * Gets all videos from db
     *
     * @return array of all videos
     */
    public function fetchAll()
    {
        $this->database->connect();
        $statement = $this->database->prepare( 'SELECT * from videos' );
        $statement = $this->database->execute( $statement );
        $result = $this->database->fetchAll( $statement );
        $this->database->disconnect();
        return $result;
    }
    
    /**
     * Gets requested fields from video
     *
     * @param string $id 
     * @param array $columns fields to return
     * @return row from db/ false in case of error
     */
    public function fetchCols( $id, $columns )
    {
        try 
        {  
            $this->database->connect();
            $columns = implode( ', ', $columns );
            $statement = $this->database->prepare( "SELECT $columns FROM videos WHERE id = $id" );
            $statement = $this->database->execute( $statement );
            $row = $this->database->fetch( $statement, PDO::FETCH_ASSOC );
            $this->database->disconnect();
            return $row;
        } catch ( Exception $e ) 
        {
            if ( $this->database->isConnected() )
            {
                $this->database->disconnect();
            }
            error_log( "Error while getting $columns:".$e );
            return false;
        }
    }
    
    /**
     * Gets the number of videos that are
     * being converted
     *
     * @return number of videos/false if error
     */
    public function getConvertingCount()
    {
        try 
        {  
            $this->database->connect();
            $count = $this->database->query( "SELECT COUNT(*) FROM videos WHERE status = 'c'" );
            $this->database->disconnect();
            return $this->database->fetchColumn( $count );
        } catch ( Exception $e ) 
        {
            if ( $this->database->isConnected() )
            {
                $this->database->disconnect();
            }
            error_log( 'Error while getting number of converting videos: '.$e );
            return false;
        }
    }
    
    /**
     * Update row in a table
     *
     * @param string $id 
     * @param array $cols ['col_name' => new_value, ...]
     * @return boolean success
     */
    public function updateCols( $id, $cols )
    {
        $arr = array();
        foreach( $cols as $k => $v )
        {
            array_push( $arr, $k.'='.$v );
        }
        $str = implode( ', ', $arr );
        try
        {
            $this->database->connect();
            $this->database->query( "UPDATE videos SET $str WHERE id=$id" );
            $this->database->disconnect();
            return true;
        } catch ( Exception $e ) 
        {
            if ( $this->database->isConnected() )
            {
                $this->database->disconnect();
            }
            error_log( "Error while updating rows: $str :".$e );
            return false;
        }
    }
    
    /**
     * Creates new video entry in db
     *
     * @param string $title 
     * @param string $dimensions 
     * @param string $videoBitrate 
     * @param string $audioBitrate 
     * @return string path where file should be moved
     **/
    public function insertVideo( $title, $dimensions, $videoBitrate, $audioBitrate )
    {
        try 
        {  
            $this->database->connect();
            $this->database->beginTransaction();
            $statement = $this->database->prepare( "SHOW TABLE STATUS LIKE 'videos'" );
            $statement = $this->database->execute( $statement );
            $id = $this->database->fetch( $statement, PDO::FETCH_ASSOC )['Auto_increment'];
            $uploadPath = "upload/$id.flv";
            $statement = $this->database->prepare( 'INSERT INTO videos( title, FLV, dimensions, bv, ba, created, status )'.
                         " VALUES( '$title', '$uploadPath', '$dimensions', '$videoBitrate', '$audioBitrate', NOW(), 'c' )" );
            $statement = $this->database->execute( $statement );
            $this->database->commit();
            $this->database->disconnect();
            return $id;
        } catch ( Exception $e ) 
        {
            if ( $this->database->isConnected() )
            {
                $this->database->rollBack();
                $this->database->disconnect();
            }
            throw $e;
        }
    }
    
    /**
     * Removes entire video entry
     * if both files were removed from disk.
     * Updates entry if only one file was removed.
     *
     * @param string $id 
     * @param array $deleted - ['flv' => true, 'mp4' => true]
     * @return True if entry was completely removed
     *         False otherwise
     */
    public function removeVideo( $id, $deleted )
    {
        $completelyDeleted = false;
        try
        {
            $this->database->connect();
            if ( ( $deleted['FLV'] == true ) && ( $deleted['MP4'] == true ) )   // delete entire entry from table
            {
                $statement = $this->database->prepare( "DELETE FROM videos WHERE id = $id" );
                $this->database->execute( $statement );
                $completelyDeleted = true;
            }
            else
            {
                $this->database->beginTransaction();
                if ( $deleted['FLV'] == true )                                  // delete just path to FLV file
                {
                    $statement = $this->database->prepare( "UPDATE videos SET FLV=NULL WHERE id = $id" );
                    $this->database->execute( $statement );
                }
                if ( $deleted['MP4'] == true )                                  // delete just path to MP4 file
                {
                    $statement = $this->dbh->prepare( "UPDATE videos SET MP4=NULL WHERE id = $id" );
                    $this->database->execute( $statement );
                }
                $this->database->commit();
            }
            $this->database->disconnect();
            
        } catch ( Exception $e )
        {
            if( $this->database->isConnected() )
            {
                $this->database->disconnect();
            }
            error_log( 'Error while deleting video from db '.$e );
            $completelyDeleted = false;
        }
        return $completelyDeleted;
    }
}
?>