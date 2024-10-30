<?php

class Content_Calendar {
   
    
    /**
     * This is where everything starts, where magic happens!
     * 
     * @param string $plugin_path
     */
    public function __construct( $plugin_path ) {
        
        //setting up important variables (and creating Content_Calendar_Options instance)
        Content_Calendar_Options::get_instance()->file = $plugin_path;
        Content_Calendar_Options::get_instance()->plugin_dir = plugin_dir_path( $plugin_path );
        Content_Calendar_Options::get_instance()->plugin_url = plugin_dir_url( $plugin_path );
        
        //registering activation and deactivation hooks
        register_activation_hook( Content_Calendar_Options::get_instance()->file, array( $this, 'activation_hook' ) );
        register_deactivation_hook( Content_Calendar_Options::get_instance()->file, array( $this, 'deactivation_hook' ) );
        
        //creating instance of class which will handle our actions
        new Content_Calendar_Actions();
        new Content_Calendar_Ajax_Actions();
        
        //calling other clases instances
        new Content_Calendar_Admin_Panel(
            array(
                'page_title' => 'ContentCalendar',
                'menu_title' => 'ContentCalendar',
                'capability' => 'manage_options',
                'menu_slug' => 'cc_settings',
                'position' => 99
            )
        );
        
    }
    
    
    //method which will be executed when plugin is activated
    public function activation_hook() {
        
        if ( Content_Calendar_Options::get_instance()->connected ) {
            $log = new Mk_Log( 'connection' );
            
            $cc_user_credentials = Content_Calendar_Ajax_Actions::create_cc_user();
            if ( empty( $cc_user_credentials ) || is_wp_error( $cc_user_credentials['user_id'] ) ) {
                return;
            }
            
            //create user, and send update user to server side
            $options_database = get_option( Content_Calendar_Options::get_instance()->options_slug );
            $options_database['username'] = $cc_user_credentials['username'];
            $options_database['user_id'] = $cc_user_credentials['user_id'];
            
            try {
                
                $request_parameters = array( 
                    'apikey' => Content_Calendar_Options::get_instance()->api_key, 
                    'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id,
                    'blogname' => get_bloginfo( 'name' ),
                    'timezone' => get_option( 'gmt_offset' ),
                    'blogUrl' => get_site_url(),
                    'apiuser' => $cc_user_credentials['username'],
                    'apipass' => $cc_user_credentials['password']
                );

                //loging request
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'REACTIVATON CONNECTION : PUT_REQUEST :  '.CALENDAR_API . '/membership : ' . print_r( $request_parameters, true ) );
                }
                
                //sending request and getting response
                $response = wp_remote_post( CALENDAR_API . '/membership', array(
                        'method' => 'PUT',
                        'timeout' => 45,
                        'blocking' => true,
                        'body' => $request_parameters
                    )
                );
                
                //loging response
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'REACTIVATON CONNECTION : RESPONSE (PUT) : ' . print_r( $response, true ) );
                    $log->log( '****************************************************************' );
                }
                
                //sync categories
                Content_Calendar_Ajax_Actions::activation_sync_categories();
                
                //sync authors
                Content_Calendar_Ajax_Actions::activation_sync_users();
                
                //sync posts
                Content_Calendar_Ajax_Actions::activation_sync_posts();
                
                
                $options_database['connected'] = 1;
                update_option( Content_Calendar_Options::get_instance()->options_slug, $options_database );
                
            } 
            catch ( Exception $ex ) {

            }
            
        }
        
    }
    
    
    public function deactivation_hook() {
        
        //if its connected, we need to send status change (disconnected) to the cc side
        if ( Content_Calendar_Options::get_instance()->connected ) {
            
            $log = new Mk_Log( 'deactivation' );

            try {

                $request_parameters = array(
                    'syncStatus' => -1, 
                    'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id,
                    'apikey' => Content_Calendar_Options::get_instance()->api_key,
                    'message' => 'Plugin deactivated'
                );

                //loging request
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'DEACTIVATION (PLUGIN DEACTIVATION) : POST_REQUEST :  '.CALENDAR_API . '/PLUGIN : ' . print_r( $request_parameters, true ) );
                }

                //sending request and getting response
                $response = wp_remote_post( CALENDAR_API . '/PLUGIN', array(
                        'method' => 'POST',
                        'timeout' => 45,
                        'blocking' => true,
                        'body' => $request_parameters
                    )
                );

                 //loging response
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'DEACTIVATION (PLUGIN DEACTIVATION) : RESPONSE (POST) : ' . print_r( $response, true ) );
                    $log->log( '****************************************************************' );
                }

                //we dont even need to parse body request in this case, because this is plugin deactivation and we need to remove user
                //we created (we dont want to leave created user after plugin is deactivated)
                wp_delete_user( Content_Calendar_Options::get_instance()->user_id );
            } 
            catch ( Exception $ex ) {
                //because this is deactivation of the plugin we are not displaying error message anywhere
            }
        }
        //if plugin is not connected we dont need to do anything
    }
}

