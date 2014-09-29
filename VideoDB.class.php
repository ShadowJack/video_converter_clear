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
        $result = $this->database->fetchAll( 'SELECT * from video' );
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
            $row = $this->database->fetch( "SELECT $columns FROM video WHERE id = $id" );
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
            $count = $this->database->fetchColumn( "SELECT COUNT(*) FROM video WHERE status = 'c'" );
            $this->database->disconnect();
            return $count;
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
            $this->database->execute( "UPDATE video SET $str WHERE id=$id" );
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
            $id = $this->database->fetch( "SHOW TABLE STATUS LIKE 'video'" )['Auto_increment'];
            $uploadPath = "upload/$id.flv";
            $this->database->execute( 'INSERT INTO video( title, flv, dimensions, video_bitrate, audio_bitrate, created, status )'.
                         " VALUES( '$title', '$uploadPath', '$dimensions', '$videoBitrate', '$audioBitrate', NOW(), 'c' )" );
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
            if ( ( $deleted['flv'] == true ) && ( $deleted['mp4'] == true ) )   // delete entire entry from table
            {
                $this->database->execute( "DELETE FROM video WHERE id = $id"  );
                $completelyDeleted = true;
            }
            else
            {
                removeEachVideo( $id, $deleted );
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
    
    /**
     * Helper function updates info in video table
     * about each deleted file
     *
     * @param string $id 
     * @param string $deleted 
     * @return void
     */
    private function removeEachVideo( $id, $deleted )
    {
        $this->database->beginTransaction();
        if ( $deleted['flv'] == true )                                  // delete just path to FLV file
        {
            $this->database->execute( "UPDATE video SET flv=NULL WHERE id = $id" );
        }
        if ( $deleted['mp4'] == true )                                  // delete just path to MP4 file
        {
            $this->dbh->execute( "UPDATE video SET mp4=NULL WHERE id = $id" );
        }
        $this->database->commit();
    }
}
?>