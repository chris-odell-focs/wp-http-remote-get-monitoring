<?php

class Focs_Monitored_WP_HTTP {

    /**
     * Summary of get
     * @param mixed $url The url to perform the get 
     * @param mixed $monitor_callback A valid callback to pass the download progress to
     *  The callback must implement two args the first being the bytes downloaded so far and the 
     *  second being the file size
     * 
     * @param mixed $args A mix of custom args and args supplied for WP_Http get   
     * 
     * Custom args are
     *  @type bool $return_content whether or not to return the file contents.
     *                      When set to false returns the filename
     * 
     * @see WP_Http request method for valid $args values for the 
     * 
     */
    public function get( $url, $monitor_callback, $args = array() ){
        
        $this->init();       

        $defaults = array(
			'method' => 'GET', 'timeout' => 5,
			'redirection' => 5, 'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(), 'body' => null, 'cookies' => array()
		);

		$r = wp_parse_args( $args, $defaults );

        //see WP_HTTP request method
        if ( isset( $r[ 'stream' ] ) && empty( $r[ 'filename' ] ) ) {
			
            $r[ 'filename' ] = get_temp_dir() . wp_unique_filename( get_temp_dir(), basename( $url ) );
		}

        $result = $r[ 'filename' ];

        //get the header info to get the file length
        $header_info = wp_remote_head( $url );
        if( is_wp_error( $header_info ) ) {
            
            return $header_info;
        }

        if( isset( $header_info[ 'response' ][ 'code' ] ) && 200 !== $header_info[ 'response' ][ 'code' ] ) {
        
            return new WP_Error( 99099, 'Headers did not return an Ok response code', $header_info[ 'response' ] );
        }

        $file_length = $header_info[ 'headers' ][ 'content-length' ];

        //do a post to start a (potentially)blocking file download
        $post_args = array(
                    'action' => FOCS_MONITORED_WP_HTTP_REMOTE_REQUEST,
                    'focs-mwh-args' => $r,
                    'focs-mwh-url' => $url
                );

        $admin_url = admin_url( 'admin-ajax.php' );
        wp_remote_post( $admin_url, array( 'body' => $post_args, 'blocking' => false ) );

        //while the file size is less than the filesize in the header
        //loop
        $last_file_size = 0;
        $keep_checking = true;
        $last_time_check = time();
        $timeout = strtotime( 's', $r[ 'timeout' ] );
        while( $last_file_size < $file_length && $keep_checking ) {
            
            set_time_limit(30); //reset the timeout for the script

            $filesize = $this->safe_filesize( $result );
            call_user_func( $monitor_callback, $filesize, $file_length );

            if( $last_file_size === $filesize ) {
                
                //if the filesize hasn't changed in the timeout then 
                //stop looping
                
                if( ( $last_time_check + $timeout ) < time() ) {
                    
                    $keep_checking = false;
                }

            } else {
                
                $last_time_check = time();
            }
            
            $last_file_size = $filesize;
            //take a breather
            usleep( 1000 );
        }

        if( !empty( $args[ 'return_content' ] ) && true === $args[ 'return_content' ] ) {
        
            //open the file and return the contents
            $fh = fopen( $result, 'rb' );
            $result = fread( $fh, filesize( $result ) );
        }

        return $result;
    }

    public static function remote_request() {
       
        $url = isset( $_POST[ 'focs-mwh-url' ] ) ? $_POST[ 'focs-mwh-url' ] : '';
        $r = isset( $_POST[ 'focs-mwh-args' ] ) ? $_POST[ 'focs-mwh-args' ] : null;
        if( null !== $r && '' !== $url ) {
            
            switch( $r[ 'method' ] ) {
                
                case 'GET' :
                    wp_remote_get( $url, $r );
                    break;
                case 'POST' :
                    wp_remote_post( $url, $r );
                    break;
                case 'HEAD' :
                    wp_remote_head( $url, $r );
                    break;
                default:
                    wp_remote_request( $url, $r );
                    break;
            }
        }

        wp_die();
    }

    private function init() {
    
        self::do_defines();
        self::setup_hooks();
    }

    private static function do_defines() {
    
        if( !defined( 'FOCS_MONITORED_WP_HTTP_REMOTE_REQUEST' ) ) {
            
            define( 'FOCS_MONITORED_WP_HTTP_REMOTE_REQUEST', 'focs_mwh_remote_request' );
        }
    }

    public static function setup_hooks() {
    
        //if( !has_action( 'wp_ajax_'.FOCS_MONITORED_WP_HTTP_REMOTE_REQUEST ) ) {           

        self::do_defines();

        add_action( 'wp_ajax_'.FOCS_MONITORED_WP_HTTP_REMOTE_REQUEST, array( 'Focs_Monitored_WP_HTTP', 'remote_request' ) );
        add_action( 'wp_ajax_nopriv_'.FOCS_MONITORED_WP_HTTP_REMOTE_REQUEST, array( 'Focs_Monitored_WP_HTTP', 'remote_request' ) );


       // }        
    }

    private function safe_filesize( $filename ) {
        
        clearstatcache();

        $size = 0;
        if( file_exists( $filename ) ) {
            
            return filesize( $filename );
        }

        return $size;
    }
}