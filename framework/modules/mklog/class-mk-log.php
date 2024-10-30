<?php

class Mk_Log 
{
    // Name of the file where the message logs will be appended.
    private $filename;
    
    private $log_status = true;
		
	
    // CONSTRUCTOR
    function __construct( $filename ) {
        $this->filename = Content_Calendar_Options::get_instance()->plugin_dir.'framework/modules/mklog/logs/'.$filename.'.log';
    }
	 
	
    // Private method that will write the text logs into the $LOGFILENAME.
    public function log( $logText = '', $logLevel = 'INFO') {
        
        WP_Filesystem();
        global $wp_filesystem;
        
        if ( $this->log_status && $wp_filesystem->is_writable( Content_Calendar_Options::get_instance()->plugin_dir.'framework/modules/mklog/logs/' ) ) {
            $datetime = date( 'Y-m-d H:i:s' );
            file_put_contents( $this->filename, "[{$datetime}] -- [{$logLevel}]  {$logText} \r\n\r\n", FILE_APPEND );
        }
    }
}