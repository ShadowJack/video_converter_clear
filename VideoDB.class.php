<?php

require_once 'Database.class.php';

/**
 * Class that manipulates with data in the db
 * Schema:
 * id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
 * title VARCHAR(100)
 * flv VARCHAR(100)
 * mp4 VARCHAR(100)
 * dimensions VARCHAR(10)
 * video_bitrate VARCHAR(10)
 * audio_bitrate VARCHAR(10)
 * created DATETIME DEFAULT NULL
 * status VARCHAR(1) - c: converting, f - finished, q - queued
 *
 */
class VideoDB
{
    /**
     * Db driver
     *
     * @var Database
     */
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
            try
            {
                $this->database = new Database();
            }
            catch ( DatabaseException $e )
            {
                error_log( "Can't create db connection: ".$e );
                die( "Sorry, error has occured! Try later." );
            }
        }
    }
    
    /**
     * Gets all videos from db
     *
     * @return array of all videos
     */
    public function fetchAll()
    {
        return $this->database->fetchAll( 'SELECT * from video' );
    }
    
    /**
     * Gets requested fields from video
     *
     * @param string $id 
     * @param array $columns fields to return
     * @return row from db/ false in case of error
     */
    public function getVideoById( $id )
    {
        return $this->database->fetch( "SELECT * FROM video WHERE id = $id" );
    }
    
    /**
     * Gets the number of videos that are
     * being converted
     *
     * @return number of videos
     */
    public function getConvertingCount()
    {
        return $this->database->fetchColumn( "SELECT COUNT(*) FROM video WHERE status = 'c'" );
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
        /** @var array */
        $arr = array();
        foreach( $cols as $k => $v )
        {
            array_push( $arr, $k.'='.$v );
        }
        /** @var string */
        $str = implode( ', ', $arr );

        $this->database->execute( "UPDATE video SET $str WHERE id=$id" );
        return true;
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
        $this->database->beginTransaction();
        $this->database->execute( 'INSERT INTO video( title, dimensions, video_bitrate, audio_bitrate, created, status )'.
                                  " VALUES( '$title', '$dimensions', '$videoBitrate', '$audioBitrate', NOW(), 'c' )" );
        /** @var string */
        $id = $this->database->lastInsertId();
        /** @var string */
        $uploadPath = "upload/$id.flv";
        $this->database->execute("UPDATE video SET flv='$uploadPath' WHERE id=$id");
        $this->database->commit();
        return $id;
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
        /** @var boolean */
        $completelyDeleted = false;
        if ( ( $deleted['flv'] == true ) && ( $deleted['mp4'] == true ) )   // delete entire entry from table
        {
            $this->database->execute( "DELETE FROM video WHERE id = $id"  );
            $completelyDeleted = true;
        }
        else
        {
            $this->removeEachVideo( $id, $deleted );
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
        if ( $deleted['flv'] == true )
        {
            $this->database->execute( "UPDATE video SET flv=NULL WHERE id = $id" );
        }
        if ( $deleted['mp4'] == true )
        {
            $this->dbh->execute( "UPDATE video SET mp4=NULL WHERE id = $id" );
        }
    }
}
?>