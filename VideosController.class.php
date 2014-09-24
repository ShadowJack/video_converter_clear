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
        include('Templates/index.tmpl.php');
    }
    
    /**
    * Create entry in db about uploaded video,
    * save uploaded video on disk.
    **/
    public function create()
    {
        // creates new entry in db + moves file from tmp folder to Uploads
        Video::save($_POST['title'], $_FILES['newVideo']['tmp_name']);

    }
    
    /**
    * Form for upload of new video
    **/
    public function newVideo()
    {
        include('Templates/newVideo.tmpl.php');
    }
    
    public function delete($id)
    {
        
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