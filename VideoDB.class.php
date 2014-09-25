<?php
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
            return $uploadPath;
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
    
    public function getFilePaths($id)
    {
        try 
        {  
            $this->connect();
            $statement = $this->dbh->prepare("SELECT FLV, MP4 FROM videos WHERE id = $id");
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
            error_log("Error while getting file names:".$e);
            return false;
        }
    }
    
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