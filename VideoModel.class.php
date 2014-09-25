<?php
require_once 'vendor/autoload.php';
require_once 'VideoDB.class.php';

use Symfony\Component\Process\Process;

class VideoModel
{
    
    protected $db;
    
    /**
     * Constructor sets $db
     *
     * @param initialized $db 
     */
    public function __construct( $db = null ) 
    {
        $this->db = ( ( $db == null ) ? new VideoDB() : $db );
    } 
    
    /**
     * Fetch all videos from db
     *
     * @return array of videos
     */
    public function all()
    {
        return $this->db->fetchAll();
    }
    
    /**
     * Save info about file into db,
     * move file from tmp folder to uploads/
     * and send it to conversion
     * @param  video title           -  $title
     * @param path to uploaded file  -  $tmpPath
     * @return response string
     */
    public function create($title, $tmpPath)
    {   
        $response = "";
        
        //TODO: do something with path to ffprobe - set in config file
        exec('/usr/local/Cellar/ffmpeg/2.3.3/bin/ffprobe -v quiet -print_format json -show_streams '.$tmpPath, $output);
        $videoStream = json_decode(implode('', $output), true)['streams'][0];
        $audioStream = json_decode(implode('', $output), true)['streams'][1];
        $dimensions = $videoStream['width']."x".$videoStream['height'];
        $videoBitrate = $videoStream['bit_rate'];
        $audioBitrate = $audioStream['bit_rate'];
        
        $uploadPath = $this->db->insertVideo($title, $dimensions, $videoBitrate, $audioBitrate);
        
        if (move_uploaded_file($tmpPath, $uploadPath))
        {
            $response = "<p>Your file was successfully uploaded!</p><a href=''> Go to index </a>";
        }
        else
        {
            //TODO: delete entry from db
            $response = "Couldn't upload your file\n";
        }
        //TODO: send file to conversion queue
        return $response;
    }
    
    /**
     * Deletes both flv and mp4 videos from disk,
     * removes them from db
     *
     * @param id in the db - $id
     * @return true if it was successful
     */
    public function delete($id)
    {  
        $paths = $this->db->getFilePaths($id);
        if ( $paths === false ) // getFilePaths was unsuccessfull
        {
            return false;
        }
        
        // delete from disk
        $deleted = Array('FLV' => true, 'MP4' => true);
        if ( ( $paths['FLV'] != null ) && ( $paths['FLV'] != '' ) )
        {
            if ( !unlink( $paths['FLV'] ) )
            {
                $deleted['FLV'] = false;
            }
        }
        if ( ( $paths['MP4'] != null ) && ( $paths['MP4'] != '' ) )
        {
            if ( !unlink( $paths['MP4'] ) )
            {
                $deleted['MP4'] = false;
            }
        }
        
        return $this->db->removeVideo( $id, $deleted );
    }
    
    /**
     * Get the file entry in db,
     * if flv file exists on disk - send it
     * 
     * @param string video $id 
     * @return void
     */
    public static function flv($id)
    {
        $dbh = new PDO(Video::$db, Video::$dbUser, Video::$dbPassword);
        try
        {
            $query = $dbh->query("SELECT title, FLV FROM videos WHERE id = $id");
            $row = $query->fetch(PDO::FETCH_ASSOC);
            $filePath = $row['FLV'];
            $title = $row['title'];
            if (file_exists($filePath)) {
                header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
                header("Cache-Control: public");
                header("Content-Type: video/x-flv");
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length:".filesize($filePath));
                header("Content-Disposition: attachment; filename=$title");
                readfile($filePath);
                die();        
            } 
            else 
            {
                die("Sorry: File not found.");
            }
        } catch (Exception $e) 
        {
            return false;
        }
        $dbh = null;
        
    }
    
    /**
     * Get the file entry in db,
     * if mp4 file exists on disk - send it
     * 
     * @param string video $id 
     * @return void
     */
    public static function mp4($id)
    {
        $dbh = new PDO(Video::$db, Video::$dbUser, Video::$dbPassword);
        try
        {
            $query = $dbh->query("SELECT title, FLV FROM videos WHERE id = $id");
            $row = $query->fetch(PDO::FETCH_ASSOC);
            $filePath = $row['MP4'];
            $title = $row['title'];
            if (file_exists($filePath)) {
                header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
                header("Cache-Control: public");
                header("Content-Type: video/mp4");
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length:".filesize($filePath));
                header("Content-Disposition: attachment; filename=$title");
                readfile($filePath);
                die();        
            } 
            else 
            {
                die("Sorry: File not found.");
            }
        } catch (Exception $e) 
        {
            return false;
        }
        $dbh = null;
    }
    
    
    public static function meta($id)
    {
        $dbh = new PDO(Video::$db, Video::$dbUser, Video::$dbPassword);
        try
        {
            $query = $dbh->query("SELECT title, dimensions, bv, ba FROM videos WHERE id = $id");
            return $query->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) 
        {
            return false;
        }
        $dbh = null;
    }
    
    
    
}
    
?>