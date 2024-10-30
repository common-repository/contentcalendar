<?php

class Content_Calendar_Actions {
    
    
    public function __construct() {
        
        //add actions only if its connected
        if ( Content_Calendar_Options::get_instance()->connected ) {
            add_action( 'user_register', array( $this, 'cc_user_added' ), 10, 1 );
            add_action( 'profile_update', array( $this, 'cc_user_updated' ), 10, 2 );
            add_action( 'delete_user', array( $this, 'cc_user_deleted' ) );
            add_action( 'create_category', array( $this, 'cc_cat_added' ) );
            add_action( 'edited_category', array( $this, 'cc_cat_updated' ) );
            add_action( 'delete_term_taxonomy', array( $this, 'cc_cat_deleted' ) );
            add_action( 'transition_post_status', array( $this, 'cc_post_status_changes' ), 10, 3 );
        }
 
    }
    
    
     //when new user is registered/added
    public function cc_user_added( $user_id ) {

        $log = new Mk_Log( 'users_sync' );
        $added_user = get_user_by( 'id', $user_id );

        $request_params = array(
            array(
                'operation'         => 'Add',
                'apikey' => Content_Calendar_Options::get_instance()->api_key,
                'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id,
                'UserID'            => $user_id,
                'LoginName'         => $added_user->user_login,
                'DisplayName'       => $added_user->display_name,
                'EmailAddress'      => $added_user->user_email,
                'Role'              => $added_user->roles[0]
            )   
        );

        try {
            //loging request
            if ( CALENDAR_DEBUG ) {
                $log->log( 'USER ADD : PUT_REQUEST : '.CALENDAR_API . '/user : ' . print_r( $request_params, true ) );
            }
            
            $response = wp_remote_request( CALENDAR_API . "/user", array(
                'method' => 'PUT', 
                'headers' => array( 'Content-Type' => 'application/json' ), 
                'body' => json_encode( $request_params )
            ));
            
            //loging response
            if ( CALENDAR_DEBUG ) {
                $log->log( 'USER ADD : RESPONSE (PUT) : ' . print_r( $response, true ) );
                $log->log( '****************************************************************' );
            }
            
            //parsing body of response
            $response_body = wp_remote_retrieve_body( $response );
            if ( is_wp_error( $response_body ) ) {
                $error_message = $response_body->get_error_message();
                //loging wp error occurred
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'USER ADD : Error ocurred : ' . $error_message );
                    $log->log( '****************************************************************' );
                }
            }
        } 
        catch ( Exception $ex ) {
            //loging wp error occurred
            if ( CALENDAR_DEBUG ) {
                $log->log( 'USER ADD : Error ocurred : ' . $ex->getMessage() );
                $log->log( '****************************************************************' );
            }
        }
    }
    
    
    //when user is updated
    public function cc_user_updated( $user_id, $old_user_data ) {

        $log = new Mk_Log( 'users_sync' );
        $updated_user = get_user_by( 'id', $user_id );
        
        $request_params = array(
            array(
                'operation'         => 'Update',
                'apikey' => Content_Calendar_Options::get_instance()->api_key,
                'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id,
                'UserID'            => $user_id,
                'LoginName'         => $updated_user->user_login,
                'DisplayName'       => $updated_user->display_name,
                'EmailAddress'      => $updated_user->user_email,
                'Role'              => $updated_user->roles[0]
            )
        );
        
        try {
            //loging request
            if ( CALENDAR_DEBUG ) {
                $log->log( 'USER UPDATE : PUT_REQUEST : '.CALENDAR_API . '/user : ' . print_r( $request_params, true ) );
            }
            
            $response = wp_remote_request( CALENDAR_API . "/user", array(
                'method' => 'PUT', 
                'headers' => array( 'Content-Type' => 'application/json' ), 
                'body' => json_encode( $request_params )
            ));
            
            //loging response
            if ( CALENDAR_DEBUG ) {
                $log->log( 'USER UPDATE : RESPONSE (PUT) : ' . print_r( $response, true ) );
                $log->log( '****************************************************************' );
            }
            
            //parsing body of response
            $response_body = wp_remote_retrieve_body( $response );
            if ( is_wp_error( $response_body ) ) {
                $error_message = $response_body->get_error_message();
                //loging wp error occurred
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'USER UPDATE : Error ocurred : ' . $error_message );
                    $log->log( '****************************************************************' );
                }
            }
        } 
        catch ( Exception $ex ) {
            //loging wp error occurred
            if ( CALENDAR_DEBUG ) {
                $log->log( 'USER UPDATE : Error ocurred : ' . $ex->getMessage() );
                $log->log( '****************************************************************' );
            }
        }
    }
    
    
    //when user is deleted
    public function cc_user_deleted( $user_id ) {
        
        if ( $user_id == Content_Calendar_Options::get_instance()->user_id ) {
            //an user which handles cc operation deleted
            $this->cc_admin_deleted();
        }
        else {
            //regular user deleted
            $this->cc_regular_user_deleted( $user_id );
        }
    }
    
    
    
    private function cc_admin_deleted() {
        //if its connected, we need to send status change (disconnected) to the cc side
        if ( Content_Calendar_Options::get_instance()->connected ) {
            
            $log = new Mk_Log( 'deactivation' );

            try {

                $request_parameters = array(
                    'syncStatus' => -1, 
                    'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id,
                    'apikey' => Content_Calendar_Options::get_instance()->api_key,
                    'message' => 'Content Calendar user deleted: ' . Content_Calendar_Options::get_instance()->username
                );

                //loging request
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'DEACTIVATION (CC ADMIN DELETED) : POST_REQUEST :  '.CALENDAR_API . '/PLUGIN : ' . print_r( $request_parameters, true ) );
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
                    $log->log( 'DEACTIVATION (CC ADMIN DELETED) : RESPONSE (POST) : ' . print_r( $response, true ) );
                    $log->log( '****************************************************************' );
                }

                delete_option( Content_Calendar_Options::get_instance()->options_slug );
            } 
            catch ( Exception $ex ) {
                //where happy things happens
            }
        }
    }
    
    
    private function cc_regular_user_deleted( $user_id ) {
        
        $log = new Mk_Log( 'users_sync' );
        $deleted_user = get_user_by( 'id', $user_id );
        
        $request_params = array(
            array(
                'operation'         => 'Delete',
                'apikey' => Content_Calendar_Options::get_instance()->api_key,
                'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id,
                'UserID'            => $user_id,
                'LoginName'         => $deleted_user->user_login,
                'DisplayName'       => $deleted_user->display_name,
                'EmailAddress'      => $deleted_user->user_email,
                'Role'              => $deleted_user->roles[0]
            )
        );
        
        try {
            //loging request
            if ( CALENDAR_DEBUG ) {
                $log->log( 'USER DELETE : PUT_REQUEST : '.CALENDAR_API . '/user : ' . print_r( $request_params, true ) );
            }
            
            $response = wp_remote_request( CALENDAR_API . "/user", array(
                'method' => 'PUT', 
                'headers' => array( 'Content-Type' => 'application/json' ), 
                'body' => json_encode( $request_params )
            ));
            
            //loging response
            if ( CALENDAR_DEBUG ) {
                $log->log( 'USER DELETE : RESPONSE (PUT) : ' . print_r( $response, true ) );
                $log->log( '****************************************************************' );
            }
            
            //parsing body of response
            $response_body = wp_remote_retrieve_body( $response );
            if ( is_wp_error( $response_body ) ) {
                $error_message = $response_body->get_error_message();
                //loging wp error occurred
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'USER DELETE : Error ocurred : ' . $error_message );
                    $log->log( '****************************************************************' );
                }
            }
        } 
        catch ( Exception $ex ) {
            //loging wp error occurred
            if ( CALENDAR_DEBUG ) {
                $log->log( 'USER DELETE : Error ocurred : ' . $ex->getMessage() );
                $log->log( '****************************************************************' );
            }
        }
    }
    
    
    //when new category is added
    public function cc_cat_added( $cat_id ) {

        $log = new Mk_Log( 'cat_sync' );
        $category = get_term_by( 'id', $cat_id, 'category' );

        $request_params = array(
            array(
                'operation'             => 'Add',
                'apikey' => Content_Calendar_Options::get_instance()->api_key,
                'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id,
                'wp_category_id'        => $cat_id,
                'wp_parent_category_id' => $category->parent,
                'category_name'         => $category->name,
                'category_description'  => $category->description,
                'category_url'          => $category->slug
            )
        );

        try {
            //loging request
            if ( CALENDAR_DEBUG ) {
                $log->log( 'CAT ADD : PUT_REQUEST : '.CALENDAR_API . '/category : ' . print_r( $request_params, true ) );
            }
            
            //sending request and getting response
            $response = wp_remote_post( CALENDAR_API . '/category', array(
                    'method' => 'PUT',
                    'headers' => array( 'Content-Type' => 'application/json' ),
                    'timeout' => 45,
                    'blocking' => true,
                    'body' => json_encode( $request_params )
                )
            );
            
            //loging response
            if ( CALENDAR_DEBUG ) {
                $log->log( 'CAT ADD : RESPONSE (PUT) : ' . print_r( $response, true ) );
                $log->log( '****************************************************************' );
            }
            
            //parsing body of response
            $response_body = wp_remote_retrieve_body( $response );
            if ( is_wp_error( $response_body ) ) {
                $error_message = $response_body->get_error_message();
                //loging wp error occurred
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'CAT ADD : Error ocurred : ' . $error_message );
                    $log->log( '****************************************************************' );
                }
            }
            
        } 
        catch ( Exception $ex ) {
            //loging exception error
            if ( CALENDAR_DEBUG ) {
                $log->log( 'CAT ADD : Error ocurred : ' . $ex->getMessage() );
                $log->log( '****************************************************************' );
            }
        }
    }
    
    
    //when cat is updated
    function cc_cat_updated( $cat_id ) {
        
        $log = new Mk_Log( 'cat_sync' );
        $category = get_term_by( 'id', $cat_id, 'category' );
        if ( ! is_object( $category ) ) {
            return;
        }

        $request_params = array(
            array(
                'operation'             => 'Update',
                'apikey' => Content_Calendar_Options::get_instance()->api_key,
                'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id,
                'wp_category_id'        => $cat_id,
                'wp_parent_category_id' => $category->parent,
                'category_name'         => $category->name,
                'category_description'  => $category->description,
                'category_url'          => $category->slug
            )
        );

        try {
            //loging request
            if ( CALENDAR_DEBUG ) {
                $log->log( 'CAT UPDATE : PUT_REQUEST : '.CALENDAR_API . '/category : ' . print_r( $request_params, true ) );
            }
            
            //sending request and getting response
            $response = wp_remote_post( CALENDAR_API . '/category', array(
                    'method' => 'PUT',
                    'headers' => array( 'Content-Type' => 'application/json' ),
                    'timeout' => 45,
                    'blocking' => true,
                    'body' => json_encode( $request_params )
                )
            );
            
            //loging response
            if ( CALENDAR_DEBUG ) {
                $log->log( 'CAT UPDATE : RESPONSE (PUT) : ' . print_r( $response, true ) );
                $log->log( '****************************************************************' );
            }
            
            //parsing body of response
            $response_body = wp_remote_retrieve_body( $response );
            if ( is_wp_error( $response_body ) ) {
                $error_message = $response_body->get_error_message();
                //loging wp error occurred
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'CAT UPDATE : Error ocurred : ' . $error_message );
                    $log->log( '****************************************************************' );
                }
            }
            
        } 
        catch ( Exception $ex ) {
            //loging exception error
            if ( CALENDAR_DEBUG ) {
                $log->log( 'CAT UPDATE : Error ocurred : ' . $ex->getMessage() );
                $log->log( '****************************************************************' );
            }
        }
    }
    
    
    //when cat is deleted
    public function cc_cat_deleted( $cat_id ) {

        $log = new Mk_Log( 'cat_sync' );
        $category = get_term_by( 'id', $cat_id, 'category' );
        
        if ( ! is_object( $category ) ) {
            return;
        }

        $request_params = array(
            array(
                'operation'             => 'Delete',
                'apikey' => Content_Calendar_Options::get_instance()->api_key,
                'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id,
                'wp_category_id'        => $cat_id,
                'wp_parent_category_id' => $category->parent,
                'category_name'         => $category->name,
                'category_description'  => $category->description,
                'category_url'          => $category->slug
            )
        );

        try {
            //loging request
            if ( CALENDAR_DEBUG ) {
                $log->log( 'CAT DELETE : PUT_REQUEST : '.CALENDAR_API . '/category : ' . print_r( $request_params, true ) );
            }
            
            //sending request and getting response
            $response = wp_remote_post( CALENDAR_API . '/category', array(
                    'method' => 'PUT',
                    'headers' => array( 'Content-Type' => 'application/json' ),
                    'timeout' => 45,
                    'blocking' => true,
                    'body' => json_encode( $request_params )
                )
            );
            
            //loging response
            if ( CALENDAR_DEBUG ) {
                $log->log( 'CAT DELETE : RESPONSE (PUT) : ' . print_r( $response, true ) );
                $log->log( '****************************************************************' );
            }
            
            //parsing body of response
            $response_body = wp_remote_retrieve_body( $response );
            if ( is_wp_error( $response_body ) ) {
                $error_message = $response_body->get_error_message();
                //loging wp error occurred
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'CAT DELETE : Error ocurred : ' . $error_message );
                    $log->log( '****************************************************************' );
                }
            }
            
        } 
        catch ( Exception $ex ) {
            //loging exception error
            if ( CALENDAR_DEBUG ) {
                $log->log( 'CAT DELETE : Error ocurred : ' . $ex->getMessage() );
                $log->log( '****************************************************************' );
            }
        }
    }
    
    
    //when post status change
    function cc_post_status_changes( $new_status, $old_status, $post ) {

        if ( 'post' != get_post_type( $post->ID ) ) {
            return;
        }
        
        if (
            $new_status != 'draft' && 
            $new_status != 'publish' &&
            $new_status != 'trash' && 
            $new_status != 'future'
        ) {
            return;
        }
        
        $log = new Mk_Log( 'posts_sync' );


        $request_params = array(
            'apikey' => Content_Calendar_Options::get_instance()->api_key,
            'ClientBlogId' => Content_Calendar_Options::get_instance()->client_blog_id,
            'PostId' => $post->ID,
            'Title' => $post->post_title,
            'Body' => $post->post_content,
            'PostDateTime' => $post->post_date,
            'PostDateTimeGmt' => $post->post_date_gmt,
            'Status' => $new_status,
            'Author' => $post->post_author,
            'Categories' => wp_get_post_categories( $post->ID )
        );

        try {

            //log request
            if ( CALENDAR_DEBUG ) {
                $log->log( 'POST STATUS TRANSITION : POST_REQUEST : '.CALENDAR_API . '/SYNC : ' . print_r( $request_params, true ) );
            }

            $response = wp_remote_request( CALENDAR_API . '/SYNC', array(
                    'method' => 'PUT', 
                    'headers' => array( 'Content-Type' => 'application/json' ), 
                    'body' => json_encode( $request_params )
                ) 
            );

            //log request
            if ( CALENDAR_DEBUG ) {
                $log->log( 'POST STATUS TRANSITION : POST_RESPONSE : '.CALENDAR_API . '/SYNC : ' . print_r( $response, true ) );
            }

            //parsing body of response
            $response_body = wp_remote_retrieve_body( $response );
            if ( is_wp_error( $response_body ) ) {
                $error_message = $response_body->get_error_message();
                //loging wp error occurred
                if ( CALENDAR_DEBUG ) {
                    $log->log( 'POST STATUS TRANSITION : Error ocurred : ' . $error_message );
                    $log->log( '****************************************************************' );
                }
            }

        } 
        catch ( Exception $ex ) {
        }



        $response = wp_remote_retrieve_body( $request );

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
        }

    }
    
    
}

