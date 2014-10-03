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
    work( $child_processes, $db );
}

function work( &$child_processes, &$db )
{
    echo ".";
    if ( count( $child_processes ) >= MAX_CONCURRENT_CONVERTIONS ) 
    {
        checkChildren( $child_processes, $db );
        sleep( WAIT_FOR_WORKERS_TIME );
        return;
    }
    
    //check for jobs available in the db
    $video = $db->fetch( "SELECT * FROM video WHERE status='q'" );
    if ( $video )
    {
        doJob( $child_processes, $db, $video );
    }
    
    //check if any of the children exited
    checkChildren( $child_processes, $db );
    sleep( WAIT_FOR_JOB_TIME );
}

function doJob( &$child_processes, &$db, $video )
{
    echo "I have a video to convert!\n";
    $pid = pcntl_fork();            

    if ( $pid == -1 ) 
    {
        error_log( "Can't fork child process\n" );
        return;
    } 
    elseif ( $pid ) // parent process
    {
        $child_processes[$pid] = $video['id'];
        // create new db connection as previous 
        // connection will be closed by child process
        $db = new Database();   
        $db->execute( "UPDATE video SET status='c' WHERE id=".$video['id'] );
        //var_dump( $result->errorInfo() );
    } 
    else            // child process
    {
        $id = $video['id'];
        exec( FFMPEG_PATH." -i upload/$id.flv -s " . $video['dimensions'].
                                        ' -b:v '. $video['video_bitrate'] .' -ar '.
                                        $video['audio_bitrate']." upload/$id.mp4".
                                        " </dev/null >".dirname(__FILE__)."/ffmpeg.log 2>&1" );
        exit;
    }
}

function checkChildren( &$child_processes, &$db )
{
    // WNOHANG - don't wait for children. Check them and go further.
    while ( $signaled_pid = pcntl_waitpid( -1, $status, WNOHANG ) ) 
    {
        if ( $signaled_pid == -1 ) 
        {
            // there is no children
            $child_processes = array();
            break;
        } 
        elseif( pcntl_wifexited( $status ) ) // normally finished
        {
            echo "Finished convertation!\n";
            $id = $child_processes[$signaled_pid];
            $db->execute( "UPDATE video SET status='f',".
                          " mp4='upload/".$id.".mp4'".
                          " WHERE id=".$id );
            //var_dump( $result->errorInfo() );
        }
        else                                // stopped because of error or signal
        {
            echo "Convertation unexpectedly stopped!\n";
            $db->execute( "UPDATE video SET status='q' WHERE id=".$child_processes[$signaled_pid] );
        }
        unset( $child_processes[$signaled_pid] );
    }
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
