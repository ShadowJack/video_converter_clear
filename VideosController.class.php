<?php
require_once('VideoModel.class.php');

class VideosController
{
    
    /**
    * Print all uploaded videos
    **/
    public function index()
    {
        $videoModel = new VideoModel();
        $videos = $videoModel->all();
        include('templates/index.tmpl.php');
    }
    
    /**
    * Create entry in db about uploaded video,
    * save uploaded video on disk, 
    * add new job to converter queue.
    **/
    public function create()
    {
        $videoModel = new VideoModel();
        echo $videoModel->create($_POST['title'], $_FILES['newVideo']['tmp_name']);
    }
    
    /**
    * Form for new video upload
    **/
    public function newVideo()
    {
        include('templates/newVideo.tmpl.php');
    }
    
    /**
     * Deletes both flv and mp4 videos from disk,
     * removes them from db
     *
     * @param video id in the db - $id
     */
    public function delete($id)
    {
        $videoModel = new VideoModel();
        $videoModel->delete($id);
    }
    
    /**
     * Check if flv file exists and send it
     *
     * @param string video $id 
     * @return void
     */
    public function flv($id)
    {
        Video::flv($id);
    }
    
    /**
     * Check if mp4 file exists and send it
     *
     * @param string video $id 
     * @return void
     */
    public function mp4($id)
    {
        Video::mp4($id);
    }
    
    /**
     * Get meta information about video
     * and print on screen
     * @param string $id
     * @return void
     */
    public function meta($id)
    {
        $meta = Video::meta($id);
        include('templates/meta.tmpl.php');
    }
}