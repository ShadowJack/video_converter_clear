<?php
require_once('VideoModel.class.php');
//TODO: переделать конфиг в объявление констант
class VideosController
{
    
    /**
    * Prints all uploaded videos
    *
    * @return void
    **/
    public function getIndex()
    {
        $videos = VideoModel::fetchAll();
        include( 'templates/index.tmpl.php' );
    }
    
    /**
    * Create new entry in db,
    * save uploaded video on disk 
    * and convert it into mp4.
    *
    * @return void
    **/
    public function createVideo()
    {
        if( !$_POST['title'] || !$_FILES['newVideo']['tmp_name'] )
        {
            include( 'templates/validationError.tmpl.php' );
        }
        else
        {
            echo VideoModel::createVideo( $_POST['title'], $_FILES['newVideo']['tmp_name'] );
        }
    }
    
    /**
    * Form for new video upload
    *
    * @return void
    **/
    public function newVideo()
    {
        include( 'templates/newVideo.tmpl.php' );
    }
    
    /**
     * Deletes both flv and mp4 videos from disk,
     * removes them from db
     *
     * @param string $id
     * @return void
     */
    public function deleteVideo( $id )
    {
        VideoModel::deleteVideo( $id );
    }
    
    /**
     * Check if flv file exists and send it
     *
     * @param string $id 
     * @return void
     */
    public function getFlv( $id )
    {
        $resp = VideoModel::getFlv( $id );
        if ( !$resp )
        {
            echo 'File not found';
        }
    }
    
    /**
     * Check if mp4 file exists and send it
     *
     * @param string $id 
     * @return void
     */
    public function getMp4( $id )
    {
        $resp = VideoModel::getMp4( $id );
        if ( !$resp )
        {
            echo 'File not found';
        }
    }
    
    /**
     * Get meta information about video
     * and print on screen
     *
     * @param string $id
     * @return void
     */
    public function getMeta( $id )
    {
        $meta = VideoModel::getMeta( $id );
        include( 'templates/meta.tmpl.php' );
    }
}