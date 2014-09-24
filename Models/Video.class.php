<?php
class Video
{
    
    // MySQL db credentials
    protected static $db = 'mysql:host=127.0.0.1;dbname=videos_db';
    protected static $dbUser = 'dbowner';
    protected static $dbPassword = 'password';

    /**
     * Constructor 
     * 
     * @return void
     */
    public function __construct() {} 
    
    /**
     * Fetch all videos from db
     *
     * @return array of videos
     */
    public static function all()
    {
        try
        {
            $dbh = new PDO(Video::$db, Video::$dbUser, Video::$dbPassword);
            $result = $dbh->query('SELECT * from videos');
            $dbh = null;
            return $result;
        } catch (PDOException $e) 
        {
            return "Error!: " . $e->getMessage() . "<br/>";
        }
    }
    
    /**
     * Save info about file into db + move file from tmp folder to Uploads/
     * @param  video title           -  $title
     * @param path to uploaded file  -  $tmpPath
     * @return response string
     */
    public static function save($title, $tmpPath)
    {   
        $response = "";
        
        //TODO: do something with path to ffprobe        
        exec('/usr/local/Cellar/ffmpeg/2.3.3/bin/ffprobe -v quiet -print_format json -show_streams '.$tmpPath, $output);
        $videoStream = json_decode(implode('', $output), true)['streams'][0];
        $audioStream = json_decode(implode('', $output), true)['streams'][1];
        $dimensions = $videoStream['width']."x".$videoStream['height'];
        $videoBitrate = $videoStream['bit_rate'];
        $audioBitrate = $audioStream['bit_rate'];
        
        $dbh = new PDO(Video::$db, Video::$dbUser, Video::$dbPassword);
        try 
        {  
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Save new entry in db
            $dbh->beginTransaction();
            $query = $dbh->query("SHOW TABLE STATUS LIKE 'videos'");
            $id = $query->fetch(PDO::FETCH_ASSOC)['Auto_increment'];
            $uploadPath = "Upload/$id.flv";
            $dbh->query("INSERT INTO videos(title, FLV, dimensions, bv, ba, created)".
                      " VALUES('$title', '$uploadPath', '$dimensions', '$videoBitrate', '$audioBitrate', NOW())");
            
            if (move_uploaded_file($tmpPath, $uploadPath))
            {
                $dbh->commit();
                $response = "<p>Your file was successfully uploaded!</p><a href=''> Go to index </a>";
            }
            else
            {
                $dbh->rollback();
                $response = "Couldn't upload your file\n";
            }
  
        } catch (Exception $e) 
        {
          $dbh->rollBack();
          $response = "Error: " . $e->getMessage();
        }
        $dbh = null;
        
        //TODO: send file to convertion queue
        return $response;
    }
    
    /**
     * Deletes both flv and mp4 videos from disk,
     * removes them from db
     *
     * @param video id in the db - $id
     * @return true if it was successful
     */
    public static function delete($id)
    {
        $dbh = new PDO(Video::$db, Video::$dbUser, Video::$dbPassword);
       
        try
        {
            $dbh->beginTransaction();
            $query = $dbh->query("SELECT FLV, MP4 FROM videos WHERE id = $id");
            $row = $query->fetch(PDO::FETCH_ASSOC);
            // delete from disk
            if ( $row['FLV'] != null || $row['FLV'] != '')
            {
                unlink( $row['FLV'] );
            }
            if ( $row['MP4'] != null || $row['MP4'] != '')
            {
                unlink( $row['FLV'] );
            }
            $query = $dbh->query("DELETE FROM videos WHERE id = $id");
            $dbh->commit();
            
        } catch (Exception $e) 
        {
            $dbh->rollBack();
            return false;
        }
        $dbh = null;
        return true;
    }
}
    
?>