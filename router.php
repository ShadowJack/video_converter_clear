<?php require_once 'VideosController.class.php'; ?>
<html>
    <head>
        <title> REST api </title>
        <!--TODO: do something with absolute paths-->
        <script type="text/javascript" src="../statics/jquery-2.1.1.min.js"></script>
        <script type="text/javascript" src="../statics/application.js"></script>
    </head>
    <body>
        <?php 
        /**
        * GET: /videos - получить список всех видео
        * GET: /videos/new - форма для добавления нового файла
        * POST: /videos - залить файл
        * GET: /videos/:id/flv - скачать flv
        * GET: /videos/:id/mp4 - скачать mp4
        * GET: /videos/:id/meta - получить информацию о файле
        * DELETE: /videos/:id/- удалить flv и mp4 видеозаписи, соответствующие id
        **/
        $videosController = new VideosController();

        $method = $_SERVER['REQUEST_METHOD'];
        $args = explode('/', rtrim($_REQUEST['init_req'], '/')); // details are in .htaccess file
        
        if ( count($args) == 1 && $args[0] == '' )     // matches /videos
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
        else if ( count($args) == 1 && $args[0] == 'new' && $method == 'GET' )      // matches GET: /videos/new
        {
            $videosController->newVideo();
        }
        else if ( count($args) == 1 && preg_match( '/^\d+$/', $args[0] ) && $method == 'DELETE' )    // matches DELETE: /videos/:id
        {
            $videosController->delete( $args[0] );
        }
        else if ( count($args) == 2 && preg_match( '/^\d+$/', $args[0] ) && $method == 'GET' )      // matches GET: /videos/:id/:action
        {
            switch ( $args[1] )
            {
                case 'flv':
                    //print_r( "You will go to VideosController#flv" );
                    $videosController->flv( $args[0] );
                    break;
                case 'mp4':
                    //print_r( "You will go to VideosController#mp4" );
                    $videosController->mp4( $args[0] );
                    break;
                case 'meta':
                    //print_r( "You will go to VideosController#meta" );
                    $videosController->meta( $args[0] );
                    break;
                default:
                    print_r( "Return 404 in header cause there is no such action in VideosController" );
            }
            
        }
        else
        {
            print_r( "Return 404 in header cause there is no such action in VideosController" );
        }
        ?>
    </body>
</html>