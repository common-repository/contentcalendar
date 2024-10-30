<?php
/*
  Plugin Name: ContentCalendar
  Description: Manage your blog posts with a drag and drop calendar
  Version: 1.0.7
  Author: contentcalendar
  Author URI: http://contentcalendar.io
  Plugin URI: http://contentcalendar.io/wordpress-plugin/
 */

//defining constants/globals
define( 'CALENDAR_API', 'http://api-contentcalendar.azurewebsites.net/api' );
define( 'CONTENT_CALENDAR_SLUG', 'content-calendar' );
define( 'CALENDAR_DEBUG', true );

//including modules
require_once( 'framework/modules/cc-admin-panel/init.php' );
require_once( 'framework/modules/mklog/init.php' );

//including main classes
require_once( 'framework/functions-xmlrpc.php' );
require_once( 'framework/class-content-calendar-actions.php' );
require_once( 'framework/class-content-calendar-ajax-actions.php' );
require_once( 'framework/class-content-calendar.php' );
new Content_Calendar( __FILE__ );