<?php
require_once 'vendor/autoload.php';
require_once 'VideoDB.class.php';
require_once 'config.inc.php';

use Symfony\Component\Process\Process;

/**
 * Video Model
 * contains methods that help 
 * to work with Video data
 */
class VideoModel
{
    /**
     * Fetch all videos from db
     *
     * @return array of videos
     */
    public static function fetchAll()
    {
        $db = new VideoDB();
        return $db->fetchAll();
    }
    
    /**
     * Save info about file into db,
     * move file from tmp folder to uploads/
     * and send it to conversion
     *
     * @param string $title video title
     * @param string $tmpPath path to uploaded file
     * @return response string
     */
    public static function createVideo( $title, $tmpPath )
    {   
        $db = new VideoDB();
        // check if there is less then 5 processes   
        $count = $db->getConvertingCount();
        if ( $count === false )
        {
            return 'Error while getting ConvertingCount!';
        }
        elseif ( $count >= 5 )
        {
            return 'Please try later - there is too many files converting right now.';
        }
        // Get info about file using ffprobe
        exec( FFPROBE_PATH.' -v quiet -print_format json -show_streams '.$tmpPath, $output );
        $videoStream = json_decode( implode( '', $output ), true )['streams'][0];
        $audioStream = json_decode( implode( '', $output ), true )['streams'][1];
        $dimensions = $videoStream['width'].'x'.$videoStream['height'];
        $videoBitrate = $videoStream['bit_rate'];
        $audioBitrate = $audioStream['bit_rate'];

        // Add new entry to DB       
        $id = $db->insertVideo( $title, $dimensions, $videoBitrate, $audioBitrate );

        // Move file from temporary dir to upload/
        if ( move_uploaded_file( $tmpPath, "upload/$id.flv" ) )
        {
            //create new converting Process
            $process = new Process( FFMPEG_PATH." -i upload/$id.flv -s $dimensions".
                                    ' -b:v '.ceil($videoBitrate/1000).'k -ar '.
                                    ceil($audioBitrate/1000)."k upload/$id.mp4" );
            $process->setTimeout( 3600 ); // kill the process after an hour
            $process->run();
            if ( $process->isSuccessful() )
            {
                $db->updateCols( $id, Array( 'mp4' => "'upload/$id.mp4'", 'status' => "'f'" ) );
            }
            else
            {
                error_log( $process->getIncrementalErrorOutput() );
            }
            return "<p>Your file was successfully uploaded!</p><a href=''> Go to index </a>";
        }
        else
        {
            $db->removeVideo( $id, Array( 'mp4' => true, 'mp4' => true ) );
            return "Couldn't upload your file\n";
        }
    }
    
    /**
     * Deletes both flv and mp4 videos from disk,
     * removes them from db
     *
     * @param string $id
     * @return void
     */
    public static function deleteVideo( $id )
    {  
        $db = new VideoDB();
        $paths = $db->fetchCols( $id, Array( 'flv', 'mp4' ) );
        if ( !$paths ) // fetchCols was unsuccessfull
        {
            return;
        }
        
        // delete from disk
        $deleted = Array( 'flv' => true, 'mp4' => true );
        if ( ( $paths['flv'] != null ) && ( $paths['flv'] != '' ) )
        {
            if ( !unlink( $paths['flv'] ) )
            {
                $deleted['flv'] = false;
            }
        }
        if ( ( $paths['mp4'] != null ) && ( $paths['mp4'] != '' ) )
        {
            if ( !unlink( $paths['mp4'] ) )
            {
                $deleted['mp4'] = false;
            }
        }
        
        return $db->removeVideo( $id, $deleted );
    }
    
    /**
     * Get the file entry in db,
     * if flv file exists on disk - send it
     * 
     * @param string $id 
     * @return true on success/false in other case
     */
    public static function getFlv( $id )
    {
        $db = new VideoDB();
        $row = $db->fetchCols( $id, Array( 'title', 'flv' ) );
        $filePath = $row['flv'];
        $title = $row['title'];
        if ( !$filePath || !file_exists( $filePath ) )
        {
            return false;
        }
        else
        {
            header( $_SERVER['SERVER_PROTOCOL'] . ' 200 OK' );
            header( 'Cache-Control: public' );
            header( 'Content-Type: video/x-flv' );
            header( 'Content-Transfer-Encoding: Binary' );
            header( 'Content-Length:'.filesize( $filePath ) );
            header( "Content-Disposition: attachment; filename=$title" );
            readfile( $filePath );
            return true;
        }
    }
    
    /**
     * Get the file entry in db,
     * if mp4 file exists on disk - send it
     * 
     * @param string $id 
     * @return true on success/false in other case
     */
    public static function getMp4( $id )
    {
        $db = new VideoDB();
        $row = $db->fetchCols( $id, Array( 'title', 'mp4' ) );
        $filePath = $row['mp4'];
        $title = $row['title'];
        if ( !$filePath || !file_exists( $filePath ) )
        {
            return false;
        }
        else 
        {
            header( $_SERVER['SERVER_PROTOCOL'] . ' 200 OK' );
            header( 'Cache-Control: public' );
            header( 'Content-Type: video/mp4' );
            header( 'Content-Transfer-Encoding: Binary' );
            header( 'Content-Length:'.filesize( $filePath ) );
            header( "Content-Disposition: attachment; filename=$title" );
            readfile( $filePath );
            return true;
        }
    }
    
    /**
     * Get metadata from files
     *
     * @param string $id 
     * @return array of title, dimensions, 
     * video bitrate and audio bitrate
     */
    public static function getMeta( $id )
    {
        $db = new VideoDB();
        return $db->fetchCols( $id, Array( 'title', 'dimensions', 'video_bitrate', 'audio_bitrate' ) );
    }
}
    
?>