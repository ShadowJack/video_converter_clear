<?php
require_once('Models/Video.class.php');

class VideosController
{
    
    /**
    * Print all uploaded videos
    **/
    public function index()
    {
        $videos = Video::all();
        if (is_string($videos))      // Video::all returned error
        {
            echo $videos;
        }
        else
        {
            include('Templates/index.tmpl.php');
        }
    }
    
    /**
    * Create entry in db about uploaded video,
    * save uploaded video on disk, 
    * add new job to converter queue.
    **/
    public function create()
    {
        echo Video::save($_POST['title'], $_FILES['newVideo']['tmp_name']);
    }
    
    /**
    * Form for new video upload
    **/
    public function newVideo()
    {
        include('Templates/newVideo.tmpl.php');
    }
    
    /**
     * Deletes both flv and mp4 videos from disk,
     * removes them from db
     *
     * @param video id in the db - $id
     */
    public function delete($id)
    {
        Video::delete($id);
    }
    
    public function flv($id)
    {
        
    }
    
    public function mp4($id)
    {
        
    }
    
    public function meta($id)
    {
        
    }
}