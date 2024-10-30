<?php

class Content_Calendar_Options {
    
    private $options = array();

   
    public function __construct() {
        $this->options_slug = 'content_calendar_options';
        $this->populate_options();
    }
    
    
    /**
     * This is static function for creating instance.
     * Simulating singleton class (we need only one instance of this class through the code)
     * 
     * @global Content_Calendar_Options $cc_options
     * @return \Content_Calendar_Options
     */
    public static function get_instance(){
        global $cc_options;

        if ( $cc_options == null ) {
            $cc_options = new Content_Calendar_Options();
        }
                
        return $cc_options;
    }

    
    //im setting things
    public function __set( $key, $value ){
        $this->options[$key] = $value;
    }

    
    //dude above setting things. im getting stuffs for you.
    public function __get($key){
        return $this->options[$key];
    }
    
    
    /**
     * This method will fetch options from database (based on the global options_slug string)
     * and populate those options into property (array) of this class which will be accessible for
     * us through the code.
     */
    public function populate_options() {
        $options = $this->options();
        $db_options = get_option( $this->options_slug );
        
        foreach ( $options as $option ) {
            $option_id = $option['id'];
            if ( $option['database'] ) {
                if ( isset( $db_options[ $option_id ] ) ) {
                    $this->$option_id = $db_options[ $option_id ];
                }  
                else {
                    $this->$option_id = $option['default'];
                }
            }
            else {
                $this->$option_id = $option['default'];
            }
        }
    }
    
    
    /**
     * This method contains hardcoded options. If we want to add more options, we will need to adjust
     * this method only.
     * 
     * TODO: Consider using framework for the options instead of using them this way. Reuse sensei-options? 
     * @return array
     */
    private function options() {
        
        return array(
            array(
                'id' => 'api_key',
                'database' => true,
                'default' => ''
            ),
            array(
                'id' => 'connected',
                'database' => true,
                'default' => 0
            ),
            array(
                'id' => 'account_id',
                'database' => true,
                'default' => 0
            ),
            array(
                'id' => 'client_blog_id',
                'database' => true,
                'default' => ''
            ),
            array(
                'id' => 'package',
                'database' => true,
                'default' => ''
            ),
            array(
                'id' => 'days_remaining_in_trial',
                'database' => true,
                'default' => ''
            ),
            array(
                'id' => 'trial',
                'database' => false,
                'default' => ''
            ),
            array(
                'id' => 'username',
                'database' => true,
                'default' => ''
            ),
            array(
                'id' => 'user_id',
                'database' => true,
                'default' => ''
            ),
            array(
                'id' => 'generic_message',
                'database' => false,
                'default' => __( 'An error occurred', CONTENT_CALENDAR_SLUG )
            ),
        );
         
    }
    
}

