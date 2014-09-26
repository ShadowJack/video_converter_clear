<?php
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
    // MySQL db credentials
    protected $dbAddress = 'mysql:host=127.0.0.1;dbname=videos_db';
    protected $dbUser = 'dbowner';
    protected $dbPassword = 'password';
    protected $dbh; 
    
    public function __construct( $dbAddress = null, $dbUser = null, $dbPassword = null)
    {
        if ($dbAddress)
        {
            $this->dbAddress = $dbAddress;
        }
        if ($dbUser)
        {
            $this->dbUser = $dbUser;
        }
        if ($dbPassword)
        {
            $this->dbPassword = $dbPassword;
        }
    }
    
    /**
     * Connects to db
     *
     * @return void
     */
    protected function connect()
    {
        $this->dbh = new PDO($this->dbAddress, $this->dbUser, $this->dbPassword);
    }
    
    /**
     * Disconnects from db
     *
     * @return void
     */
    protected function disconnect()
    {
        $this->dbh = null;
    }
    
    /**
     * Gets all videos from db
     *
     * @return array of all videos
     */
    public function fetchAll()
    {
        $this->connect();
        $statement = $this->dbh->prepare('SELECT * from videos');
        $statement->execute();
        $result = $statement->fetchAll();
        $this->disconnect();
        return $result;
    }
    
    /**
     * Gets requested fields from video
     *
     * @param video:            $id 
     * @param fields to return: $columns 
     * @return row
     */
    public function fetchCols($id, $columns)
    {
        try 
        {  
            $this->connect();
            $columns = implode(", ", $columns);
            $statement = $this->dbh->prepare("SELECT $columns FROM videos WHERE id = $id");
            $statement->execute();
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            $this->disconnect();
            return $row;
        } catch (Exception $e) 
        {
            if($this->dbh)
            {
                $this->disconnect();
            }
            error_log("Error while getting $columns:".$e);
            return false;
        }
    }
    
    /**
     * Gets the number of videos that are
     * being converted
     * @return number of videos
     */
    public function getConvertingCount()
    {
        try 
        {  
            $this->connect();
            $count = $this->dbh->query("SELECT COUNT(*) FROM videos WHERE status = 'c'");
            $this->disconnect();
            return $count->fetchColumn();
        } catch (Exception $e) 
        {
            if($this->dbh)
            {
                $this->disconnect();
            }
            error_log("Error while getting number of converting videos:".$e);
            return -1;
        }
    }
    
    /**
     * Update row in table
     *
     * @param string $id 
     * @param string $cols - array('col_name' => new_value, ...)
     * @return boolean
     */
    public function updateCols($id, $cols)
    {
        $arr = array();
        foreach($cols as $k => $v)
        {
            array_push($arr, $k."=".$v);
        }
        $str = implode(", ", $arr);
        error_log($str);
        try
        {
            $this->connect();
            $this->dbh->query("UPDATE videos SET $str WHERE id=$id");
            $this->disconnect();
            return true;
        } catch (Exception $e) 
        {
            if($this->dbh)
            {
                $this->disconnect();
            }
            error_log("Error while updating rows: $str :".$e);
            return false;
        }
    }
    
    /**
     * Inserts new video into db
     *
     * @param string $title 
     * @param string $dimensions 
     * @param string $videoBitrate 
     * @param string $audioBitrate 
     * @return path where file should be moved
     */
    public function insertVideo($title, $dimensions, $videoBitrate, $audioBitrate)
    {
        try 
        {  
            $this->connect();
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->dbh->beginTransaction();
            $statement = $this->dbh->prepare("SHOW TABLE STATUS LIKE 'videos'");
            $statement->execute();
            $id = $statement->fetch(PDO::FETCH_ASSOC)['Auto_increment'];
            $uploadPath = "upload/$id.flv";
            $statement = $this->dbh->prepare("INSERT INTO videos(title, FLV, dimensions, bv, ba, created)".
                         " VALUES('$title', '$uploadPath', '$dimensions', '$videoBitrate', '$audioBitrate', NOW())");
            $statement->execute();
            $this->dbh->commit();
            $this->disconnect();
            return $id;
        } catch (Exception $e) 
        {
            if($this->dbh)
            {
                $this->dbh->rollBack();
                $this->disconnect();
            }
            throw $e;
        }
    }
    
    /**
     * Removes entire video entry
     * if both files were removed from disk.
     * Updates entry if only one file was removed.
     *
     * @param video $id 
     * @param the files that was $deleted 
     * @return True if entry was completely removed
     *         False otherwise
     */
    public function removeVideo($id, $deleted)
    {
        $completelyDeleted = false;
        try
        {
            $this->connect();
            if ( ( $deleted['FLV'] == true ) && ( $deleted['MP4'] == true ) )   // delete entire entry from table
            {
                $statement = $this->dbh->prepare("DELETE FROM videos WHERE id = $id");
                $statement->execute();
                $completelyDeleted = true;
            }
            else
            {
                $this->dbh->beginTransaction();
                if ( $deleted['FLV'] == true )                                  // delete just path to FLV file
                {
                    $statement = $this->dbh->prepare("UPDATE videos SET FLV=NULL WHERE id = $id");
                    $statement->execute();
                }
                if ( $deleted['MP4'] == true )                                  // delete just path to MP4 file
                {
                    $statement = $this->dbh->prepare("UPDATE videos SET MP4=NULL WHERE id = $id");
                    $statement->execute();
                }
                $this->dbh->commit();
            }
            $this->disconnect();
            
        } catch (Exception $e)
        {
            if($this->dbh)
            {
                $this->disconnect();
            }
            error_log("Error while deleting video from db".$e);
            $completelyDeleted = false;
        }
        return $completelyDeleted;
    }
    
    
}
?>