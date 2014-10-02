<?php
require_once 'config.inc.php';
require_once 'Database.class.php';

// unbind process from terminal
$child_pid = pcntl_fork();
if( $child_pid ) 
{
    exit;  
}

// make current process a session leader
// so it can fork children
posix_setsid();
declare(ticks=1);


$baseDir = dirname(__FILE__);
fclose(STDIN);
fclose(STDOUT);
fclose(STDERR);
$STDIN = fopen('/dev/null', 'r');
$STDOUT = fopen($baseDir.'/application.log', 'ab');
$STDERR = fopen($baseDir.'/daemon.log', 'ab');

$stop_server = false;

$child_processes = array();
$db = new Database();
echo "Connected to db!\n";

// main loop
while ( !$stop_server ) 
{
    //check for jobs available in the db
    if ( !$stop_server && ( count( $child_processes ) < MAX_CONCURRENT_CONVERTIONS ) ) 
    {
        $video = $db->fetch( "SELECT * FROM video WHERE status='q'" );
        if ( $video )
        {
            // we have a job
            echo "I have a video to convert!\n";
            $pid = pcntl_fork();            
        
            if ( $pid == -1 ) 
            {
                error_log( "Can't fork child process\n" );
                continue;
            } 
            elseif ( $pid ) // master process
            {
                $child_processes[$pid] = $video['id'];
                // create new db connection as previous 
                // connection will be closed by child process
                $db = new Database();   
                $db->execute( "UPDATE video SET status='c' WHERE id=".$video['id'] );
            } 
            else            // worker process
            {
                $pid = getmypid();
                // convert video
                echo "I'm a child";
                $id = $video['id'];
                exec( FFMPEG_PATH." -i upload/$id.flv -s " . $video['dimensions'].
                                                ' -b:v '. $video['video_bitrate'] .' -ar '.
                                                $video['audio_bitrate']." upload/$id.mp4".
                                                " </dev/null >".dirname(__FILE__)."/ffmpeg.log 2>&1" );
                exit;
            }
        }
    } 
    else 
    {
        //check every 5 seconds if any worker is available
        sleep( WAIT_FOR_WORKERS_TIME ); 
    }
    
    echo "I'm a master and I'm trying to check my slaves!\n";
    //check if any of the children exited
    while ( $signaled_pid = pcntl_waitpid( -1, $status, WNOHANG ) ) 
    {
        if ( $signaled_pid == -1 ) 
        {
            //детей не осталось
            $child_processes = array();
            break;
        } 
        elseif( pcntl_wifexited( $status ) ) 
        {
            echo "Finished convertation!\n";
            //var_dump( $db );
            $sql = "UPDATE video SET status='f',".
                          " mp4='upload/".$child_processes[$signaled_pid].".mp4'".
                          " WHERE id=".$child_processes[$signaled_pid];
            echo $sql . "\n";
            $result = $db->execute( $sql );
            var_dump( $result->errorInfo() );
        }
        else
        {
            echo "Convertation unexpectedly stopped!\n";
            $db->execute( "UPDATE video SET status='q' WHERE id=".$child_processes[$signaled_pid] );
        }
        unset( $child_processes[$signaled_pid] );
    }
    sleep( WAIT_FOR_JOB_TIME );
}

function sigHandler($signo) 
{
    global $stop_server;
    if( $signo == SIGTERM ) 
    {
        $stop_server = true;
    }
    else
    {
        echo "Got signal: ".$signo."\n";
    }

}
//регистрируем обработчик
pcntl_signal(SIGTERM, "sig_handler");
