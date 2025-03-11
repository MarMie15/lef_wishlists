<?php
/*
Plugin Name: plugin-test
Plugin URI: http://www.startthema.test/
Description: A simple WordPress plugin
Version: 1.0
Author: Marcel Miedema
Author URI: http://www.startthema.test/
License: GPL2
*/

function add_custom_message($content) {
    if (is_single()) {
        $content .= '<p style="color:blue; font-weight:bold;">Thanks for reading!</p>';
    }
    return $content;
}
add_filter('the_content', 'add_custom_message');
