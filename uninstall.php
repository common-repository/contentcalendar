<?php
//if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

require_once( 'framework/modules/cc-admin-panel/init.php' );
Content_Calendar_Options::get_instance()->options_slug = 'content_calendar_options';        
Content_Calendar_Options::get_instance()->populate_options();

try {
    //sending request and getting response
    $response = wp_remote_post( CALENDAR_API . '/disconnect', array(
            'method' => 'POST',
            'timeout' => 45,
            'blocking' => true,
            'body' => $request_parameters
        )
    );
} 
catch ( Exception $ex ) {
    //error handler
}
        
//remove options
delete_option( Content_Calendar_Options::get_instance()->options_slug );

//remove user
wp_delete_user( Content_Calendar_Options::get_instance()->user_id );
    

