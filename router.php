<?php 
require_once 'VideosController.class.php';  

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
    $videosController->delete( $args[0] );
}
elseif ( ( count( $args ) == 2 ) && ( preg_match( '/^\d+$/', $args[0] ) ) && ( $method == 'GET' ) )      // GET: /videos/:id/:action
{
    matchVideosAction( $args[0], $args[1] );
}
else
{
    print_r( "No such action in VideosController" );
}

function matchVideos($method)
{
    $videosController = new VideosController();
    
    switch ( $method )
    {
        case 'GET':
            $videosController->index();
            break;
        case 'POST':
            $videosController->create();
            break;
        default:
            print_r( "No such action in VideosController" );
    }
}
function matchVideosAction($id, $action)
{
    $videosController = new VideosController();
    
    switch ( $action )
    {
        case 'flv':
            $videosController->flv( $id );
            break;
        case 'mp4':
            $videosController->mp4( $id );
            break;
        case 'meta':
            $videosController->meta( $id );
            break;
        default:
            print_r( "No such action in VideosController" );
    }
}
?>
