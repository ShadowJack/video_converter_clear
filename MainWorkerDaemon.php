<?php
require_once 'Daemon.class.php';
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

$daemon = new Daemon();
$daemon->run();
