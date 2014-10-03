<?php
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
        /** @var VideoDB */
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
     * @return string response
     */
    public static function createVideo( $title, $tmpPath )
    {   
        /** @var VideoDB */
        $db = new VideoDB();
        /** @var mixed integer/false */
        $count = $db->getConvertingCount();
        if ( $count === false )
        {
            return 'Error while getting ConvertingCount!';
        }
        elseif ( $count >= MAX_CONCURRENT_CONVERTIONS )
        {
            return 'Please try later - there is too many files converting right now.';
        }
        
        /** @var array */
        $metaData = self::getMetaFromFile( $tmpPath );
        /** @var string */
        $id = $db->insertVideo( $title,
                                $metaData['dimensions'],
                                $metaData['videoBitrate'].'k',
                                $metaData['audioBitrate'].'k'
                                );

        // Move file from temporary dir to upload/
        if ( !move_uploaded_file( $tmpPath, "upload/$id.flv" ) )
        {
            $db->removeVideo( $id, Array( 'mp4' => true, 'mp4' => true ) );
            return "Couldn't upload your file";
        }
        return "Your file is sent to conversion";
    }
    
    /**
     * Deletes both flv and mp4 videos from disk,
     * removes them from db
     *
     * @param string $id
     * @return boolean - true if entry was completely deleted
     */
    public static function deleteVideo( $id )
    {  
        /** @var VideoDB */
        $db = new VideoDB();
        /** @var mixed array/false */
        $video = $db->getVideoById( $id );
        if ( !$video ) // fetchCols was unsuccessfull
        {
            return false;
        }
        
        /** @var array */
        $deleted = Array();
        $deleted['flv'] = self::tryDeleteVideo( $video['flv'] );
        $deleted['mp4'] = self::tryDeleteVideo( $video['mp4'] );
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
        self::sendFile( $id, 'flv' );
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
        self::sendFile( $id, 'mp4' );
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
        /** @var VideoDB */
        $db = new VideoDB();
        /** @var mixed array/false */
        $video = $db->getVideoById( $id );
        return array($video['title'], 
                     $video['dimensions'], 
                     $video['video_bitrate'], 
                     $video['audio_bitrate']);
    }
    
    
    /**
     * Sends file to user
     *
     * @param string $id 
     * @param string $type - flv/mp4
     * @return boolean success
     */
    private static function sendFile( $id, $type )
    {
        /** @var VideoDB */
        $db = new VideoDB();
        /** @var mixed array/false */
        $video = $db->getVideoById( $id );
        /** @var string */
        $filePath = $video[$type];
        /** @var string */
        $title = $video['title'];
        if ( !$filePath || !file_exists( $filePath ) )
        {
            return false;
        }
        else
        {
            header( $_SERVER['SERVER_PROTOCOL'] . ' 200 OK' );
            header( 'Cache-Control: public' );
            header( 'Content-Type: video/' . ( ($type == 'flv') ? 'x-flv' : 'mp4' ) );
            header( 'Content-Transfer-Encoding: Binary' );
            header( 'Content-Length:'.filesize( $filePath ) );
            header( "Content-Disposition: attachment; filename=$title" );
            readfile( $filePath );
            return true;
        }
    }
    
    /**
     * Gets meta data from the file
     * using ffprobe 
     *
     * @param string $pathToFile 
     * @return array
     */
    private static function getMetaFromFile( $pathToFile )
    {
        /** @var string */
        $output;
        exec( FFPROBE_PATH.' -v quiet -print_format json -show_streams '.$pathToFile, $output );
        /** @var array */
        $videoStream = json_decode( implode( '', $output ), true )['streams'][0];
        /** @var array */
        $audioStream = json_decode( implode( '', $output ), true )['streams'][1];
        /** @var string */
        $dimensions = $videoStream['width'].'x'.$videoStream['height'];
        /** @var string */
        $videoBitrate = ceil( $videoStream['bit_rate'] / 1000 );
        /** @var string */
        $audioBitrate = ceil( $audioStream['bit_rate'] / 1000 );
        return array( 'dimensions'   => $dimensions,
                      'videoBitrate' => $videoBitrate,
                      'audioBitrate' => $audioBitrate
                    );
    }
    
    /**
     * Deletes video at the $path
     * from disk
     *
     * @param string $path 
     * @return boolean - true on success
     */
    private static function tryDeleteVideo( $path )
    {
        if ( ( $path == null ) || ( $path == '' ) || !file_exists( $path ) )
        {
            return true;
        }
        if ( !unlink( $path ) )
        {
            return false;
        }
        return true;
    }
}
    
?>
