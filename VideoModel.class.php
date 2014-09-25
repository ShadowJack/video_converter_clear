<?php
require_once 'vendor/autoload.php';
require_once 'VideoDB.class.php';

use Symfony\Component\Process\Process;

class VideoModel
{
    /**
     * Fetch all videos from db
     *
     * @return array of videos
     */
    public static function all()
    {
        $db = new VideoDB();
        return $db->fetchAll();
    }
    
    /**
     * Save info about file into db,
     * move file from tmp folder to uploads/
     * and send it to conversion
     * @param  video title           -  $title
     * @param path to uploaded file  -  $tmpPath
     * @return response string
     */
    public static function create($title, $tmpPath)
    {       
        // Get info about file using ffprobe
        //TODO: do something with path to ffprobe - set in config file
        exec( '/usr/local/Cellar/ffmpeg/2.3.3/bin/ffprobe -v quiet -print_format json -show_streams '.$tmpPath, $output );
        $videoStream = json_decode( implode( '', $output ), true )['streams'][0];
        $audioStream = json_decode( implode( '', $output ), true )['streams'][1];
        $dimensions = $videoStream['width']."x".$videoStream['height'];
        $videoBitrate = $videoStream['bit_rate'];
        $audioBitrate = $audioStream['bit_rate'];
        
        // Add new entry to DB
        $db = new VideoDB();
        $id = $db->insertVideo( $title, $dimensions, $videoBitrate, $audioBitrate );
        
        // Move file from temporary dir to upload/
        if ( move_uploaded_file( $tmpPath, "upload/$id.flv" ) )
        {
            //TODO: send file to conversion queue
            return "<p>Your file was successfully uploaded!</p><a href=''> Go to index </a>";
        }
        else
        {
            $db->removeVideo( $id, Array( 'FLV' => true, 'MP4' => true ) );
            return "Couldn't upload your file\n";
        }
    }
    
    /**
     * Deletes both flv and mp4 videos from disk,
     * removes them from db
     *
     * @param id in the db - $id
     * @return true if it was successful
     */
    public static function delete($id)
    {  
        $db = new VideoDB();
        $paths = $db->fetchCols($id, Array('FLV', 'MP4'));
        if ( $paths === false ) // fetchCols was unsuccessfull
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
        
        return $db->removeVideo( $id, $deleted );
    }
    
    /**
     * Get the file entry in db,
     * if flv file exists on disk - send it
     * 
     * @param string video $id 
     * @return false if fetching from DB was unsuccessfull
     */
    public static function flv($id)
    {
        $db = new VideoDB();
        $row = $db->fetchCols($id, Array('title', 'FLV'));
        $filePath = $row['FLV'];
        $title = $row['title'];
        if ( !$filePath )
        {
            return false;
        }
        if (file_exists($filePath)) 
        {
            header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
            header("Cache-Control: public");
            header("Content-Type: video/x-flv");
            header("Content-Transfer-Encoding: Binary");
            header("Content-Length:".filesize($filePath));
            header("Content-Disposition: attachment; filename=$title");
            readfile($filePath);
            die();        
        }
    }
    
    /**
     * Get the file entry in db,
     * if mp4 file exists on disk - send it
     * 
     * @param string video $id 
     * @return void
     */
    public static function mp4( $id )
    {
        $db = new VideoDB();
        $row = $db->fetchCols( $id, Array( 'title', 'MP4' ) );
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
    }
    
    
    public static function meta($id)
    {
        $db = new VideoDB();
        return $db->fetchCols($id, Array('title', 'dimensions', 'bv', 'ba'));
    }
    
    
    
}
    
?>