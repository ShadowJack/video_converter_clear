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
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
    }
    
    /**
     * Save info about file into db + move file from tmp folder to Uploads/
     * @param title - video title
     * @param tmpPath - path to uploaded file
     * @return void
     */
    public static function save($title, $tmpPath)
    {   
        
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
                echo '<p>Your file was successfully uploaded!</p>';
                echo '<a href="" > Go to list </a>';
                
            }
            else
            {
                $dbh->rollback();
                echo "Couldn't upload your file\n";
            }
  
        } catch (Exception $e) {
          $dbh->rollBack();
          echo "Error: " . $e->getMessage();
        }
        $dbh = null;
        
        //TODO: send file to convertion queue
        
    }
    
}
    
?>