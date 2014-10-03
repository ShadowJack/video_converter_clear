<?php
require_once 'config.inc.php';
require_once 'Database.class.php';

class Daemon
{
    /**
     * All children that
     * this daemon has
     *
     * @var array [ pid => db id, ... ]
     */
    private $child_processes;
    /**
     * Database connection
     *
     * @var Database
     */
    private $db;
    /**
     * Flag that indicates
     * when to stop daemon
     * 
     * @var boolean
     */
    public static $stop_daemon = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->child_processes = array();
        $this->db = new Database();
        echo "Connected to db!\n";

        //register new SIGTERM handler
        pcntl_signal( SIGTERM, array( 'Daemon', 'sigtermHandler' ) );
    }
    
    /**
     * Main loop
     *
     * @return void
     */
    public function run()
    {
        while ( !self::$stop_daemon ) 
        {
            $this->work();
        }
    }
    
    /**
     * One work cycle
     *
     * @return void
     */
    private function work()
    {
        echo ".";
        if ( count( $this->child_processes ) >= MAX_CONCURRENT_CONVERTIONS ) 
        {
            $this->checkChildren();
            sleep( WAIT_FOR_WORKERS_TIME );
            return;
        }
    
        //if we have availabe workers check for jobs in the db
        /** @var array */
        $video = $this->db->fetch( "SELECT * FROM video WHERE status='q'" );
        if ( $video )
        {
            $this->makeFork( $video );
        }
    
        //check if any of the children exited
        $this->checkChildren();
        sleep( WAIT_FOR_JOB_TIME );
    }
    
    /**
     * Forks a new child that converts video
     *
     * @param array $video 
     * @return void
     */
    private function makeFork( $video )
    {
        echo "I have a video to convert!\n";
        /** @var int */
        $pid = pcntl_fork();            

        if ( $pid == -1 ) 
        {
            error_log( "Can't fork child process\n" );
            return;
        } 
        elseif ( $pid ) // parent process
        {
            $this->doParentJob( $pid, $video );
        } 
        else            // child process
        {
            $this->doChildJob( $video );
        }
    }
    
    /**
     * The job that parent does
     *
     * @param array $video 
     * @return void
     */
    private function doParentJob( $pid, $video )
    {
        echo $pid;
        $this->child_processes[$pid] = $video['id'];
        // create new db connection as previous 
        // connection will be closed by child process
        $this->db = new Database();   
        $this->db->execute( "UPDATE video SET status='c' WHERE id=".$video['id'] );
    }
    
    /**
     * The job that child does
     *
     * @param array $video 
     * @return void
     */
    private function doChildJob( $video )
    {
        /** @var string */
        $id = $video['id'];
        exec( FFMPEG_PATH." -i upload/$id.flv -s " 
              .$video['dimensions'].
              ' -b:v '. $video['video_bitrate'] .' -ar '.
              $video['audio_bitrate']." upload/$id.mp4".
              " </dev/null >".dirname(__FILE__)."/ffmpeg.log 2>&1" );
        exit;
    }
    
    /**
     * Checks if any of the workers has finished
     *
     * @return void
     */
    private function checkChildren()
    {
        // WNOHANG - don't wait for children. Check them and go further.
        /** @var int */
        while ( $signaled_pid = pcntl_waitpid( -1, $status, WNOHANG ) ) 
        {
            if ( $signaled_pid == -1 ) 
            {
                // there is no children
                $this->child_processes = array();
                break;
            } 
            elseif( pcntl_wifexited( $status ) ) // normally finished
            {
                $this->onJobSuccess( $signaled_pid );
            }
            else                                // stopped because of error or signal
            {
                $this->onJobFailure( $signaled_pid );
            }
            unset( $this->child_processes[$signaled_pid] );
        }
    }
    
    /**
     * Updates info in db when video is converted
     *
     * @return void
     */
    private function onJobSuccess( $signaled_pid )
    {
        echo "Finished convertation!\n";
        /** @var string */
        $id = $this->child_processes[$signaled_pid];
        echo "Success ID: $id \n";
        $this->db->execute( "UPDATE video SET status='f',".
                      " mp4='upload/".$id.".mp4'".
                      " WHERE id=".$id );
    }
    
    /**
     * Adds video back to queue
     *
     * @return void
     */
    private function onJobFailure( $signaled_pid )
    {
        echo "Convertation unexpectedly stopped!\n";
        $this->db->execute( "UPDATE video SET status='q' WHERE id=".$this->child_processes[$signaled_pid] );
    }
    
    /**
     * SIGTERM handler
     *
     * @param int $signo 
     * @return void
     */
    private static function sigtermHandler($signo) 
    {
        if( $signo == SIGTERM ) 
        {
            self::$stop_daemon = true;
        }
        else
        {
            error_log( "Got signal: ".$signo."\n" );
        }
    }
        
}