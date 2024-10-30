<?php

class Content_Calendar_Admin_Panel {


    public function __construct( $page_parameters ) {
        $this->menu_page_parameters = $page_parameters;
        add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
    }


    public function register_menu_page() {
        $menu_page = add_menu_page(
            $this->menu_page_parameters['page_title'],
            $this->menu_page_parameters['menu_title'],
            $this->menu_page_parameters['capability'],
            $this->menu_page_parameters['menu_slug'],
            array( $this, 'render_page' ),
            Content_Calendar_Options::get_instance()->plugin_url . '/framework/modules/cc-admin-panel/assets/img/icon.png',
            $this->menu_page_parameters['position']
        );

        add_action( 'load-' . $menu_page, array( $this, 'scripts_loader' ) );
    }


    public function scripts_loader() {
        add_action( 'admin_enqueue_scripts', array( $this, 'load_js' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'load_css' ) );
    }


    public function load_js() {
        wp_enqueue_script( 'cc-admin-panel', Content_Calendar_Options::get_instance()->plugin_url.'framework/modules/cc-admin-panel/assets/js/cc-admin-panel.js', array( 'jquery' ), '1.0' );
    }


    public function load_css() {
        wp_enqueue_style( 'cc-admin-panel', Content_Calendar_Options::get_instance()->plugin_url.'framework/modules/cc-admin-panel/assets/css/cc-admin-panel.css', false, '1.0' );
    }
    
    
    /**
     * TODO: Within version v1.0.1 this method needs to render multiple options from an array.
     * Idea is to have this module totally independent based on Content_Calendar_Options class. Options class needs to contain
     * options within an array, and this module needs only to display those options and nothing else
     * 
     * Method which renders our settings page (for content calendar plugin)
     */
    public function render_page() {
        ?>
        <div class="cc-container">
            
            <div class="cc-header">
                
                <div class="cc-logo-container">
                    <div class="cc-logo">
                        <img src="<?php echo Content_Calendar_Options::get_instance()->plugin_url; ?>framework/modules/cc-admin-panel/assets/img/cc-logo.png" />
                    </div>
                    <div class="cc-logo-description">
                        <h3>Your favorite editorial calendar</h3> 
                    </div>
                </div>
                
               <!--TODO: Package div back here -->
                
                <div class="clearfix"></div>
                
                <div class="cc-line-separator"></div>
                
            </div>
            
            <div class="cc-plugin-description">
			 <?php 
                if ( Content_Calendar_Options::get_instance()->connected ) {
                    ?>
					 <p>ContentCalendar is an editorial calendar plugin which is used to schedule and manage blog posts on a drag and drop calendar. 
Your ContentCalendar account is now connected to this blog.					
                  </p>
					<?php
                }
                else {
                    ?>
				 <p>ContentCalendar is an editorial calendar plugin which is used to schedule and manage blog posts on a drag and drop calendar. 
					You need to create a free account at <a href="http://app.contentcalendar.io/Account/Register" target="_blank">ContentCalendar.io</a> in order to sync your blog to the application.
					
                  </p>
				<?php
                }
                ?>

            </div>
            
            <h2 id="cc-plugin-form-title" class="cc-plugin-form-title">
                <?php 
                if ( Content_Calendar_Options::get_instance()->connected ) {
                    echo 'Your blog is connected - <a href="http://app.contentcalendar.io/Calendar?cbid='.Content_Calendar_Options::get_instance()->client_blog_id.'" target="_blank">View on App</a>';
                }
                else {
                    echo 'Connect your blog';
                }
                ?>
            </h2>

            <form id="content-calendar-form" class="content-calendar-form" style="<?php if ( Content_Calendar_Options::get_instance()->connected ) { echo 'display:none;'; } ?>" >

                <input type="hidden" name="action" value="activation" />
                <?php wp_nonce_field( 'cc_activaction', 'cc_activaction_nonce', false ); ?>

                <div>
                    <div class="cc-field-label field-text-label">
                        API KEY: 
                    </div>

                    <div class="cc-field-container field-text-container">
                        <input class="cc-field field-text" type="text" id="api_key" name="api_key" value="" />
                        <p class="cc-field-description field-text-description">
                            Find info on how to get the API key <a href="#apikey" >below</a>.
                        </p>
                    </div>
                    
                    <div class="clearfix"></div>
                </div>
                
                <div style="margin-top:15px;">
                    <div class="cc-field-container field-text-container">
                        <div style="float:left;">
                            <input type="checkbox" id="confirmation" name="confirmation" />
                        </div>
                        <p class="cc-field-description field-text-description" style="float:left; margin-left: 15px;">
                            Content Calendar will create user in order to be able to sync blog posts remotely. Do you aprove? <a href="http://persist.agency/contentcalendar-plugin/#plugin-admin-user" target="_blank">Learn more</a>
                        </p>
                        <div class="clearfix"></div>
                    </div>
                </div>

                <div class="clearfix"></div>

                <div class="cc-option-container">                        
                    <input type="submit" id="cc-submit" class="cc-submit" name="cc_activate" value="Connect" />
                    <div class="clear"></div>
                    <div id="cc-connection-message"></div>
                </div>

            </form>
            
            <?php 
                $active_class = ''; 
                if ( Content_Calendar_Options::get_instance()->connected ) {
                    $active_class = 'active';
                }
            ?>
            
			 <?php 
                if ( !Content_Calendar_Options::get_instance()->connected ) {
               ?>
			
            <div id="progressbar-container" class="cc-option-container" style="<?php if ( Content_Calendar_Options::get_instance()->connected ) { echo 'display:block;'; } ?>">
                
                <div id="progress-spinner-container" class="cc-field-label" style="width:150px; <?php if ( Content_Calendar_Options::get_instance()->connected ) { echo 'display:none;'; } ?>">
                    <div id="progressbar-title" class="progressbar-title">PLEASE WAIT</div> 
                    <div id="cc-spiner" class="spinner"></div>
                    <div class="clearfix"></div>
                </div>
                
                <ul id="progressbar">
                    <li class="active">
                        <span>Validating API key</span>
                    </li>
                    <li class="<?php echo $active_class; ?> progressbar-item" id="progressbar-connecting">
                        <span>Connecting</span>
                    </li>
                    <li class="<?php echo $active_class; ?> progressbar-item" id="progressbar-categories">
                        <span>Syncing categories</span>
                    </li>
                    <li class="<?php echo $active_class; ?> progressbar-item" id="progressbar-users">
                        <span>Syncing users</span>
                    </li>
                    <li class="<?php echo $active_class; ?> progressbar-item" id="progressbar-posts">
                        <span>Syncing posts</span>
                    </li>
                </ul>
                <div class="clearfix"></div>
                
            </div>
                <?php
				}
            ?>
			
            <div id="disconnect-container" class="disconnect-container" style="<?php if ( Content_Calendar_Options::get_instance()->connected ) { echo 'display:block;'; } ?>">

                <form id="content-calendar-form-deactivation">

                    <input type="hidden" name="action" value="deactivation" />
                    <?php wp_nonce_field( 'cc_deactivaction', 'cc_deactivaction_nonce', false ); ?>

                    <div class="cc-option-container">                        
                        <input type="submit" id="cc-submit-deactivate" class="cc-submit" name="cc_deactivate" value="Disconnect and remove your data from ContentCalendar" />
                        <div id="cc-spiner-deactivate" class="spinner"></div>
                        <div class="clear"></div>
                    </div>

                    <div>
						If you wish to disconnect your blog and remove your data from ContentCalendar please click the above button. 
						Please note that all data including parked post ideas on ContentCalendar will be deleted. You can connect your blog again but you will not have access to your old activity on ContentCalendar
						
                    </div>

                </form>

            </div>
            
            <div style="margin-top:35px; margin-bottom:35px;" class="cc-line-separator"></div>
            
            <div class="cc-plugin-description">
                <h2>
                    HELPFUL LINKS
                </h2>
                <ul>
                   <li><a href="http://persist.agency/contentcalendar-plugin/" target="_blank">Plugin Info Page</a></li>
                </ul>
                <h2>
                   HOW TO GET API KEY
                </h2>
                <div>
                    In order to get an API KEY you need to create a free account on <a href="http://app.contentcalendar.io/Account/Register" target="_blank">ContentCalendar.io</a>
                    <ul>
                      <li>Login to <a href="http://app.contentcalendar.io/"  target="_blank">ContentCalendar app</a></li>
                      <li>Click on the + icon on the left sidebar or click on "Add New Calendar" button on the dashboard</li>
                      <li>Click on "Next" once you verify you have the plugin installed and activated</li>
                      <li>API KEY will be displayed on the second step</li>
                    </ul>
                </div>
            </div>
            
        </div>
        <?php
    }
    
    
}

