## Synopsis

The WordPress HTTP API provides a number of methods which abstract away the details of handling 
remote data, but doesn’t provide a way of monitoring those interactions. Here is one approach 
when getting data from a remote resource.

## Code Example

$monitored_http = new \Focs_Monitored_WP_HTTP();
$downloaded_file = $monitored_http->get( 
   $remote_file_path, 
   array( $this, 'progress_monitor' ), //the callback function to display or log the progress
      array( 
         'timeout' => 360,
         'filename' => $local_archive_path, //the path to save to on the local machine
         'return_content' => false, //if set to true the content from the remote file will be passed to the calling module, rather than saving the file
         'stream' => true, //if to steam to a file. See https://developer.wordpress.org/reference/classes/wp_http/ for more detail
   ) 
);

## Motivation

Provide a monitoring mechanism for performing a file get using the WordPress HTTP API

## Installation

Reference the class in your project

require_once( <folder_which_has_the_file_in>.'/class-focs-monitored-wp-http.php' );
$monitored_http = new \Focs_Monitored_WP_HTTP();

e.g. 

## API Reference

http://foxdellfolio.com/monitoring-progress-using-wp_remote_get/

## Contributors

chris.odell@foxdellcodesmiths.com www.foxdellcodesmiths.com
https://wordpress.org/support/users/chrisodell/

## License

GPLv2 or later

