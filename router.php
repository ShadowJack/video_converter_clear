<?php 
require_once 'VideosController.class.php';  

function exception_handler($exception) {
  echo "Uncaught exception: " , $exception->getMessage(), "\n";
}

set_exception_handler('exception_handler');


$method = $_SERVER['REQUEST_METHOD'];
$args = explode('/', rtrim($_REQUEST['init_req'], '/'));

if ( ( count( $args ) == 1 ) && ( $args[0] == '' ) )     // matches /videos
{
    matchVideos($method);
}
elseif ( ( count( $args ) == 1 ) && ( $args[0] == 'new' ) && ( $method == 'GET' ) )      // GET: /videos/new
{
    $videosController = new VideosController();
    $videosController->newVideo();
}
elseif ( ( count( $args ) == 1 ) && ( preg_match( '/^\d+$/', $args[0] ) ) && ( $method == 'DELETE' ) )    // DELETE: /videos/:id
{
    $videosController = new VideosController();
    $videosController->deleteVideo( $args[0] );
}
elseif ( ( count( $args ) == 2 ) && ( preg_match( '/^\d+$/', $args[0] ) ) && ( $method == 'GET' ) )      // GET: /videos/:id/:action
{
    matchVideosAction( $args[0], $args[1] );
}
else
{
    print_r( "No such action in VideosController" );
}

/**
 * Matches paths to /videos
 *
 * GET: /videos, POST: /videos
 * @param string $method http method
 * @return void
 */
function matchVideos($method)
{
    $videosController = new VideosController();
    
    switch ( $method )
    {
        case 'GET':
            $videosController->getIndex();
            break;
        case 'POST':
            $videosController->createVideo();
            break;
        default:
            print_r( 'No such action in VideosController' );
    }
}

/**
 * Matches /videos/:id/[flv, mp4, meta]
 *
 * @param string $id part of url
 * @param string $action [flv, mp4, meta]
 * @return void
 */
function matchVideosAction($id, $action)
{
    $videosController = new VideosController();
    
    switch ( $action )
    {
        case 'flv':
            $videosController->getFlv( $id );
            break;
        case 'mp4':
            $videosController->getMp4( $id );
            break;
        case 'meta':
            $videosController->getMeta( $id );
            break;
        default:
            print_r( 'No such action in VideosController' );
    }
}
?>
