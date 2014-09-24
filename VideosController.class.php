<?php
class VideosController
{
    public function __construct() {} 
    
    public function index()
    {
        try {
            $dbh = new PDO('mysql:host=127.0.0.1;dbname=videos_db', 'dbowner', 'password');
            echo "<table><thead><td>Id</td><td>Title</td><td>FLV</td><td>MP4</td><td>Dimensions</td><td>Video bitrate</td><td>Audio bitrate</td></thead><tbody>";
            foreach($dbh->query('SELECT * from videos') as $row) {
                print "<tr><td>".$row['id']."</td><td>".$row['title']."</td><td>".$row['FLV']."</td><td>".$row['MP4']."</td><td>".$row['dimensions']."</td><td>".$row['bv']."</td><td>".$row['ba']."</td></tr>";
            }
            echo "</tbody></table>";
            $dbh = null;
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
        
    }
    
    public function create()
    {
        
    }
    
    public function newVideo()
    {
        
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