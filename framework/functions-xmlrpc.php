<?php 

add_filter( 'xmlrpc_methods', 'content_calendar_xmlrpc_methods' );


function content_calendar_xmlrpc_methods( $methods ) {
    $methods['cc.delete_blog'] = 'cc_delete_blog';
    return $methods;   
}


function cc_delete_blog( $args ) {
    global $wp_xmlrpc_server;
    $wp_xmlrpc_server->escape( $args );

    if (
        ! isset( $args[0] ) ||
        ! is_numeric( $args[0] ) ||
        ! isset( $args[1] ) ||
        ! isset( $args[1] )
    ) {
        return array(
            'status' => false,
            'message' => 'One or more parameters are wrong.' 
        );
    }

    $blog_id  = $args[0];
    $username = $args[1];
    $password = $args[2];

    if ( ! $user = $wp_xmlrpc_server->login( $username, $password ) )
        return $wp_xmlrpc_server->error;

    // If we are here that means that authentication was succesfull

    require_once( 'modules/cc-admin-panel/init.php' );
    Content_Calendar_Options::get_instance()->options_slug = 'content_calendar_options';        
    Content_Calendar_Options::get_instance()->populate_options();

    //remove options
    delete_option( Content_Calendar_Options::get_instance()->options_slug );

    //remove user
    wp_delete_user( Content_Calendar_Options::get_instance()->user_id );

    return array(
        'status' => true,
        'message' => 'OK'
    );
       
}
