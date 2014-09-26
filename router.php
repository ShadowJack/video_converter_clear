<?php require_once 'VideosController.class.php'; ?>
<html>
    <head>
        <title> REST api </title>
        <script type="text/javascript" src="/video_converter/statics/jquery-2.1.1.min.js"></script>
        <script type="text/javascript" src="/video_converter/statics/application.js"></script>
    </head>
    <body>
        <?php 
        $videosController = new VideosController();

        $method = $_SERVER['REQUEST_METHOD'];
        $args = explode('/', rtrim($_REQUEST['init_req'], '/')); // details are in .htaccess file
        
        if ( ( count( $args ) == 1 ) && ( $args[0] == '' ) )     // matches /videos
        {
            switch ( $method )
            {
                case 'GET':
                    $videosController->index();
                    break;
                case 'POST':
                    $videosController->create();
                    break;
                default:
                    print_r( "Return 404 in header cause there is no such action in VideosController" );
            }
        }
        elseif ( ( count( $args ) == 1 ) && ( $args[0] == 'new' ) && ( $method == 'GET' ) )      // GET: /videos/new
        {
            $videosController->newVideo();
        }
        elseif ( ( count( $args ) == 1 ) && ( preg_match( '/^\d+$/', $args[0] ) ) && ( $method == 'DELETE' ) )    // DELETE: /videos/:id
        {
            $videosController->delete( $args[0] );
        }
        elseif ( ( count( $args ) == 2 ) && ( preg_match( '/^\d+$/', $args[0] ) ) && ( $method == 'GET' ) )      // GET: /videos/:id/:action
        {
            switch ( $args[1] )
            {
                case 'flv':
                    $videosController->flv( $args[0] );
                    break;
                case 'mp4':
                    $videosController->mp4( $args[0] );
                    break;
                case 'meta':
                    $videosController->meta( $args[0] );
                    break;
                default:
                    print_r( "No such action in VideosController" );
            }
            
        }
        else
        {
            print_r( "No such action in VideosController" );
        }
        ?>
    </body>
</html>