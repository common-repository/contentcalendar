<?php

class Content_Calendar_Ajax_Actions {
    
    public function __construct() {
        if ( Content_Calendar_Options::get_instance()->connected ) {
            add_action( 'wp_ajax_deactivation', array( $this, 'ajax_deactivation' ) );
        }
        else {
            add_action( 'wp_ajax_activation', array( $this, 'ajax_activation' ) );
            add_action( 'wp_ajax_activation_create_user', array( $this, 'ajax_activation_create_user' ) );
        }
        add_action( 'wp_ajax_activation_sync_categories', array( $this, 'ajax_activation_sync_categories' ) );
        add_action( 'wp_ajax_activation_sync_users', array( $this, 'ajax_activation_sync_users' ) );
        add_action( 'wp_ajax_activation_sync_posts', array( $this, 'ajax_activation_sync_posts' ) );
    }
    

    
    /**
     * First method executed during activation.
     * Will sync data important for blog in general.
     */
    public function ajax_activation() {

        $log = new Mk_Log( 'connection' );
        $status = true;
        $message = '';
        $api_key = '';
        
        if ( isset( $_POST['api_key'] ) && ! empty( $_POST['api_key'] ) ) {
            $api_key = $_POST['api_key'];
        }
        else {
            $status = false;
            $message = __( 'Please insert your api key and try again.', CONTENT_CALENDAR_SLUG );
        }
        
        //check wp nonce
        if ( ! check_ajax_referer( 'cc_activaction', 'cc_activaction_nonce', false ) ) {
            $status = false;
            $message = __( 'Sorry, your nonce did not verify.', CONTENT_CALENDAR_SLUG );
        }
        
        //if api key is not inserted, there is no make sense to proceed with the call
        $this->status_checker( $status, $message );
        
        $connection_request_parameters = array( 
            'apikey' => $api_key, 
            'blogname' => html_entity_decode( get_bloginfo( 'name' ) ),
            'timezone' => get_option( 'gmt_offset' ),
            'blogUrl' => get_site_url()
        );
        
        //loging request
        if ( CALENDAR_DEBUG ) {
            $log->log( 'INITIAL CONNECTION : POST_REQUEST :  '.CALENDAR_API . '/membership : ' . print_r( $connection_request_parameters, true ) );
        }
        
        try {
            //sending request and getting response
            $response = wp_remote_post( CALENDAR_API . '/membership', array(
                    'method' => 'POST',
                    'timeout' => 45,
                    'blocking' => true,
                    'body' => $connection_request_parameters
                )
            );

            //loging response
            if ( CALENDAR_DEBUG ) {
                $log->log( 'INITIAL CONNECTION : RESPONSE (POST) : ' . print_r( $response, true ) );
                $log->log( '****************************************************************' );
            }

            //parsing body of response
            $response_body = wp_remote_retrieve_body( $response );
            if ( is_wp_error( $response_body ) ) {
                $status = false;
                $message = $response_body->get_error_message();
            }
            else {
                $response_object = json_decode( $response_body );
                if ( $response_object->AccountId != 0 && $response_object->ClientBlogId != 0 && 'Success' == $response_object->ResponseCode  ) {
                    $options = array(
                        'api_key' => $api_key,
                        'account_id' => $response_object->AccountId,
                        'client_blog_id' => $response_object->ClientBlogId,
                        'name' => $response_object->Name,
                        'trial' => $response_object->Trial,
                        'trial_started' => $response_object->TrialStarted,
                        'trial_ends' => $response_object->TrialEnds,
                        'days_remaining_in_trial' => $response_object->DaysRemainingInTrial,
                        'package' => $response_object->Package
                    );
                    update_option( Content_Calendar_Options::get_instance()->options_slug, $options );
                    $status = true;
                }
                else {
                    $status = false;
                    $message = $response_object->ResponseCode;
                }
            }
        } catch ( Exception $ex ) {
            $status = false;
            $message = $ex->getMessage();
        }
        
       
        $return_array = array(
            'status' => $status,
            'message' => Content_Calendar_Options::get_instance()->generic_message . ': ' .$message,
        );
        
        if ( $status ) {
            $return_array['days_remaining_in_trial'] = $response_object->DaysRemainingInTrial;
            $return_array['trial'] = $response_object->Trial;
            $return_array['package'] = $response_object->Package;
        }

        wp_send_json( $return_array );
    }
    
    
    
    /**
     * Second method executed during activation.
     * This method will insert a user which content callendar will use to preform actions and
     * will sync it with api side.
     */
    public function ajax_activation_create_user() {
        
        $log = new Mk_Log( 'connection' );
        $status = true;
        $message = '';
        
        //check wp nonce
        if ( ! check_ajax_referer( 'cc_activaction', 'cc_activaction_nonce', false ) ) {
            $status = false;
            $message = __( 'Sorry, your nonce did not verify.', CONTENT_CALENDAR_SLUG );
        }
        
        //if nonce fails
        $this->status_checker( $status, $message );
        
        $cc_user_credentials = self::create_cc_user();
        if ( empty( $cc_user_credentials ) || is_wp_error( $cc_user_credentials['user_id'] ) ) {
            $status = false;
            $message = __( 'Content Calendar was not able to create a user.', CONTENT_CALENDAR_SLUG );
        }
        //if user is not created for some kind of reason, there is no make sense to proceed with the call
        $this->status_checker( $status, $message );
        
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
                $log->log( 'INITIAL CONNECTION : PUT_REQUEST :  '.CALENDAR_API . '/membership : ' . print_r( $request_parameters, true ) );
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
                $log->log( 'INITIAL CONNECTION : RESPONSE (PUT) : ' . print_r( $response, true ) );
                $log->log( '****************************************************************' );
            }
            
            //parsing body of response
            $response_body = wp_remote_retrieve_body( $response );
            if ( is_wp_error( $response_body ) ) {
                $status = false;
                $message = $response_body->get_error_message();
            }
            else {
                $response_object = json_decode( $response_body );
                if ( 'Success' == $response_object->ResponseCode ) {
                    $options_database = get_option( Content_Calendar_Options::get_instance()->options_slug );
                    $options_database['username'] = $cc_user_credentials['username'];
                    $options_database['user_id'] = $cc_user_credentials['user_id'];
                    $options_database['connected'] = 1;
                    update_option( Content_Calendar_Options::get_instance()->options_slug, $options_database );
                    $status = true;
                }
                else {
                    $status = false;
                    $message = $response_object->ResponseCode;
                }
                
            }
        } 
        catch ( Exception $ex ) {
            $status = false;
            $message = $ex->getMessage();
        }
        
        $return_array = array(
            'status' => $status,
            'message' => Content_Calendar_Options::get_instance()->generic_message . ': ' .$message,
        );

        wp_send_json( $return_array );
        
    }
    
    
    
    /**
     * Third method executed during activation.
     * Will initialize syncing categories. 
     */
    public function ajax_activation_sync_categories() {
        
        $status = true;
        $message = '';
        
        //check wp nonce
        if ( ! check_ajax_referer( 'cc_activaction', 'cc_activaction_nonce', false ) ) {
            $status = false;
            $message = __( 'Sorry, your nonce did not verify.', CONTENT_CALENDAR_SLUG );
        }
        
        //if nonce fails
        $this->status_checker( $status, $message );

        $r_params = self::activation_sync_categories();    
         
        $return_array = array(
            'status' => $r_params['status'],
            'message' => Content_Calendar_Options::get_instance()->generic_message . ': ' .$r_params['message'],
        );

        wp_send_json( $return_array );
    }
    
    
    
    /**
     * This method sync categories.
     * 
     * @return array
     */
    public static function activation_sync_categories() {
        
        $log = new Mk_Log( 'cat_sync' );
        $cats_to_send = array();
        $status = true;
        $message = '';
        
        
        $cat_args = array(
            'type'    => 'post',
            'orderby' => 'name',
            'order'   => 'ASC',
            'hide_empty' => 0,
        );
        $categories = get_categories( $cat_args );

        if ( ! empty( $categories ) ) {
            
            foreach ( $categories as $category ) {
                $cats_to_send[] = array(
                    'operation'             => 'Add',
                    'apikey' => Content_Calendar_Options::get_instance()->api_key,
                    'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id,
                    'wp_category_id'        => $category->term_id,
                    'wp_parent_category_id' => $category->parent,
                    'category_name'         => $category->name,
                    'category_description'  => $category->description,
                    'category_url'          => $category->slug
                );
            }
            
            try {
                
                //loging request
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'CAT SYNC : PUT_REQUEST : '.CALENDAR_API . '/category : ' . print_r( $cats_to_send, true ) );
                }

                //sending request and getting response
                $response = wp_remote_post( CALENDAR_API . '/category', array(
                        'method' => 'PUT',
                        'headers' => array( 'Content-Type' => 'application/json' ),
                        'timeout' => 45,
                        'blocking' => true,
                        'body' => json_encode( $cats_to_send )
                    )
                );
                
                //loging response
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'CAT SYNC : RESPONSE (PUT) : ' . print_r( $response, true ) );
                    $log->log( '****************************************************************' );
                }
                
                //parsing body of response
                $response_body = wp_remote_retrieve_body( $response );
                if ( is_wp_error( $response_body ) ) {
                    $status = false;
                    $message = $response_body->get_error_message();
                }
            } 
            catch ( Exception $ex ) {
                $status = false;
                $message = $ex->getMessage();
            }
        }
        else {
            //loging empty cat sync
            if ( CALENDAR_DEBUG ) {
                $log->log( 'CAT SYNC : There is no category to sync at this moment.' );
            }
        }
        
        return array(
            'message' => $message,
            'status' => $status
        );
    }
    
    
    
    /**
     * This method will initialize syncing users.
     * Forth method executed during activation.
     */
    public function ajax_activation_sync_users() {
        
        $status = true;
        $message = '';
        
        //check wp nonce
        if ( ! check_ajax_referer( 'cc_activaction', 'cc_activaction_nonce', false ) ) {
            $status = false;
            $message = __( 'Sorry, your nonce did not verify.', CONTENT_CALENDAR_SLUG );
        }
        
        //if nonce fails
        $this->status_checker( $status, $message );
        
        $r_parameters = self::activation_sync_users();
        
        $return_array = array(
            'status' => $r_parameters['status'],
            'message' => Content_Calendar_Options::get_instance()->generic_message . ': ' .$r_parameters['message'],
        );

        wp_send_json( $return_array );

    }
    
    
    
    /**
     * This method will sync users and return status about it.
     * 
     * @return array
     */
    public static function activation_sync_users() {
        $log = new Mk_Log( 'users_sync' );
        $status = true;
        $message = '';
        $users_to_send = array();
        
        $users = get_users();
        if ( ! empty( $users ) ) {
            
            foreach ($users as $user) {
                
                if ( $user->ID == Content_Calendar_Options::get_instance()->user_id ) {
                    continue;
                }
                
                $users_to_send[] = array(
                    'operation'         => 'Add',
                    'apikey' => Content_Calendar_Options::get_instance()->api_key,
                    'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id,
                    'UserID'            => $user->ID,
                    'LoginName'         => $user->user_nicename,
                    'DisplayName'       => $user->display_name,
                    'EmailAddress'      => $user->user_email,
                    'Role'              => $user->roles[0]
                );
            }
            
            //loging request
            if ( CALENDAR_DEBUG ) {
                $log->log( 'USER SYNC : PUT_REQUEST : '.CALENDAR_API . '/user : ' . print_r( $users_to_send, true ) );
            }

            //sending request and getting response
            $response = wp_remote_post( CALENDAR_API . '/user', array(
                    'method' => 'PUT',
                    'headers' => array( 'Content-Type' => 'application/json' ),
                    'timeout' => 45,
                    'blocking' => true,
                    'body' => json_encode( $users_to_send )
                )
            );
            
            //loging response
            if ( CALENDAR_DEBUG ) {
                $log->log( 'USER SYNC SYNC : RESPONSE (PUT) : ' . print_r( $response, true ) );
                $log->log( '****************************************************************' );
            }

            //parsing body of response
            $response_body = wp_remote_retrieve_body( $response );
            if ( is_wp_error( $response_body ) ) {
                $status = false;
                $message = $response_body->get_error_message();
            }
        }
        else {
            //loging empty user sync
            if ( CALENDAR_DEBUG ) {
                $log->log( 'USERS SYNC : There is no users to sync at this moment.' );
            }
        }
        
        return array(
            'message' => $message,
            'status' => $status
        );
    }
    
    
    
    /**
     * This method will initialize syncing posts
     * Fifth method ececuted during activation.
     */
    public function ajax_activation_sync_posts() {
        
        $status = true;
        $message = '';
        
        //check wp nonce
        if ( ! check_ajax_referer( 'cc_activaction', 'cc_activaction_nonce', false ) ) {
            $status = false;
            $message = __( 'Sorry, your nonce did not verify.', CONTENT_CALENDAR_SLUG );
        }
        
        //if nonce fails
        $this->status_checker( $status, $message );
        
        $r_parameters = self::activation_sync_posts();

        $return_array = array(
            'status' => $r_parameters['status'],
            'message' => Content_Calendar_Options::get_instance()->generic_message . ': ' .$r_parameters['message'],
        );

        wp_send_json( $return_array );        
    }
    
    
    
    /**
     * This method will sync posts and will return status about it.
     * 
     * @global object $post
     * @return array
     */
    public static function activation_sync_posts() {
        
        $log = new Mk_Log( 'posts_sync' );
        $status = true;
        $message = '';
        
        $posts_parameters = array(
            'post_type' => 'post',
            'post_status' => 'any',
            'orderby' => 'date',
            'order' => 'desc',
            'posts_per_page' => -1
        );
        $all_posts = new WP_Query( $posts_parameters );

        $posts_to_send = array();
        global $post;
        $counter = 1;
        
        if ( $all_posts->have_posts() ) {
            
            while ( $all_posts->have_posts() ) {
                $all_posts->the_post();

                $posts_to_send[] = array(
                    'BlogUrl' => get_site_url(),
                    'apikey' => Content_Calendar_Options::get_instance()->api_key,
                    'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id,
                    'PostId' => $post->ID,
                    'Title' => $post->post_title,
                    'Body' => $post->post_content,
                    'PostDateTime' => $post->post_date,
                    'PostDateTimeGmt' => $post->post_date_gmt,
                    'Status' => $post->post_status,
                    'Author' => $post->post_author,
                    'Categories' => wp_get_post_categories($post->ID)
                );

                if ( count( $posts_to_send ) == 30 ) {
                    
                    //loging request
                    if ( CALENDAR_DEBUG ) {
                        $log->log( 'POSTS SYNC : POST_REQUEST('.$counter.') : '.CALENDAR_API . '/SYNC : ' . print_r( $posts_to_send, true ) );
                    }

                    //sending request and getting response
                    $response = wp_remote_post( CALENDAR_API . '/SYNC', array(
                            'method' => 'POST',
                            'headers' => array( 'Content-Type' => 'application/json' ),
                            'timeout' => 45,
                            'blocking' => true,
                            'body' => json_encode( $posts_to_send )
                        )
                    );
                    //loging response
                    if ( CALENDAR_DEBUG ) {
                        $log->log( 'POSTS SYNC : RESPONSE('.$counter.') : '.CALENDAR_API . '/SYNC : ' . print_r( $response, true ) );
                    }
                    
                    $response_body = wp_remote_retrieve_body( $response );

                    if ( is_wp_error( $response_body ) ) {
                        $message = $response_body->get_error_message();
                        $status = false;
                        break;
                    }

                    $posts_to_send = array();
                    $counter++;
                }
            }
            
            wp_reset_query();

            if ( count( $posts_to_send ) != 0 && $status ) {
                
                $counter++;
                
                //loging request
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'POSTS SYNC : POST_REQUEST('.$counter.') : '.CALENDAR_API . '/SYNC : ' . print_r( $posts_to_send, true ) );
                }

                //sending request and getting response
                $response = wp_remote_post( CALENDAR_API . '/SYNC', array(
                        'method' => 'POST',
                        'headers' => array( 'Content-Type' => 'application/json' ),
                        'timeout' => 45,
                        'blocking' => true,
                        'body' => json_encode( $posts_to_send )
                    )
                );

                $response_body = wp_remote_retrieve_body( $response );
                //loging response
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'POSTS SYNC : RESPONSE('.$counter.') : '.CALENDAR_API . '/SYNC : ' . print_r( $response, true ) );
                }

                if ( is_wp_error( $response_body ) ) {
                    $message = $response_body->get_error_message();
                    $status = false;
                }
            }
        }
        else {
            //loging empty user sync
            if ( CALENDAR_DEBUG ) {
                $log->log( 'POSTS SYNC : There is no posts to sync at this moment.' );
            }
        }
        
        
        //changing status to synced
        $request_parameters = array( 
            'syncStatus' => 1, 
            'apikey' => Content_Calendar_Options::get_instance()->api_key,
            'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id,
            'message' => __( 'Categories, users and posts synced.', CONTENT_CALENDAR_SLUG )
        );
        
        //loging request
        if ( CALENDAR_DEBUG ) {
            $log->log( 'STATUS CHANGE SYNCED : POST_REQUEST : '.CALENDAR_API . '/PLUGIN : ' . print_r( $request_parameters, true ) );
        }
        
        //sending request and getting response
        $response = wp_remote_post( CALENDAR_API . '/PLUGIN', array(
                'method' => 'POST',
                'timeout' => 45,
                'blocking' => true,
                'body' => $request_parameters
            )
        );

        $response_body = wp_remote_retrieve_body( $response );
         //loging response
        if ( CALENDAR_DEBUG ) {
            $log->log( 'STATUS CHANGE SYNCED : RESPONSE : '.CALENDAR_API . '/PLUGIN : ' . print_r( $response, true ) );
        }

        if ( is_wp_error( $response_body ) ) {
            $message = $response_body->get_error_message();
            $status = false;
        }
        
        return array(
            'message' => $message,
            'status' => $status
        );
    }
    
    
    
    /**
     * Ajax method which will execute upon deactivation.
     */
    public function ajax_deactivation() {
        
        $log = new Mk_Log( 'disconnect' );
        $status = true;
        $message = '';
        
        //check wp nonce
        if ( ! check_ajax_referer( 'cc_deactivaction', 'cc_deactivaction_nonce', false ) ) {
            $status = false;
            $message = __( 'Sorry, your nonce did not verify.', CONTENT_CALENDAR_SLUG );
        }
        
        //if api key is not inserted, there is no make sense to proceed with the call
        $this->status_checker( $status, $message );
        
        //remove user
        wp_delete_user( Content_Calendar_Options::get_instance()->user_id );
        
        //send request
        $request_parameters = array( 
            'apikey' => Content_Calendar_Options::get_instance()->api_key,
            'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id
        );
        
        //loging request
        if ( CALENDAR_DEBUG ) {
            $log->log( 'DISCONECT : POST_REQUEST : '.CALENDAR_API . '/disconnect : ' . print_r( $request_parameters, true ) );
        }
        
        try {
            //sending request and getting response
            $response = wp_remote_post( CALENDAR_API . '/disconnect', array(
                    'method' => 'POST',
                    'timeout' => 45,
                    'blocking' => true,
                    'body' => $request_parameters
                )
            );
            
            //loging response
            if ( CALENDAR_DEBUG ) {
                $log->log( 'DISCONECT RESPONSE : POST_REQUEST : '.CALENDAR_API . '/disconnect : ' . print_r( $response, true ) );
            }
        } 
        catch ( Exception $ex ) {
            //log error (we are going to clean things on our side anyway, just we want to log an error
            if ( CALENDAR_DEBUG ) {
                $log->log( 'DISCONECT FAILED ON CC SIDE WITH MESSAGE: '.$ex->getMessage() );
            }
        }
        
        //remove options
        delete_option( Content_Calendar_Options::get_instance()->options_slug );
        
        $return_array = array(
            'status' => true
        );

        wp_send_json( $return_array ); 
        
    }
    
    
    
    private function status_checker( $status, $message ) {
        if ( ! $status ) {
            $return_array = array(
                'status' => $status,
                'message' => Content_Calendar_Options::get_instance()->generic_message . ': ' .$message,
            );
            wp_send_json( $return_array );
        }
    }
    
    
    
    /**
     * Static helper. This funcion will create username string
     * which we are going to insert as admin user which will handle content calendar task.
     * Works with self::insert_cc_user
     * 
     * @return array
     */
    public static function create_cc_user() {
        
        $return_array = array();
        
        for( $i = 0; $i <= 100; $i++ ) {
            
            if( $i == 0 ) {
                $username = 'contentcalendar';
            }
            else {
                $username = 'contentcalendar'.$i;
            }
            
            $user_id = username_exists( $username );
            if ( ! $user_id ) {
                $return_array = self::insert_cc_user( $username );
                break;
            }
        }
        
        return $return_array;
        
    }
    
    
    
    /**
     * This method will use $username created by create_cc_user
     * and populate it within db.
     * 
     * @param string $username
     * @return array()
     */
    private static function insert_cc_user( $username ) {
        $random_password = wp_generate_password( 20 );
        $user_id = wp_create_user( $username, $random_password );
        
        if ( is_int( $user_id ) ) {
            $wp_user_object = new WP_User( $user_id );
            $wp_user_object->set_role('administrator');
        }
        
        return array(
            'user_id' => $user_id,
            'username' => $username,
            'password' => $random_password
        );
    }
    
}
