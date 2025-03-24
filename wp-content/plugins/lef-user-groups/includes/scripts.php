<?php
// Prevent direct access
if (!defined('ABSPATH')) exit;

function lef_enqueue_scripts() {
    // Enqueue groups.js only for group pages
    if (is_singular('lef_groepen')) {
        wp_enqueue_script(
            'lef-groups-js', 
            plugin_dir_url(__FILE__) . 'js/groups.js', 
            array('jquery'), 
            null, 
            true);

        wp_localize_script('lef-groups-js', 'lefWishlistData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
        ));
    }

    // Enqueue wishlist.js only for wishlist pages
    if (is_singular('lef_wishlist')) {
        wp_enqueue_script(
            'lef-wishlist',
            plugin_dir_url(__FILE__) . 'js/wishlist.js',
            array('jquery'), 
            null, 
            true
        );

        // Pass necessary data (AJAX URL and Wishlist ID if available)
        wp_localize_script('lef-wishlist', 'lefWishlistData', array(
            'ajax_url'    => admin_url('admin-ajax.php'),
            'wishlist_id' => intval(get_the_ID()) // Get current wishlist post ID
        ));
    }
    
    wp_enqueue_script(
        'lef-delete-js', 
        plugin_dir_url(__FILE__) . 'js/delete.js', 
        array('jquery'), 
        null, 
        true
    );

    // Localize delete.js with AJAX URL
    wp_localize_script('lef-delete-js', 'lefDeleteData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
    
    wp_enqueue_script(
        'lef-invites-js', 
        plugin_dir_url(__FILE__) . 'js/invites.js', 
        array('jquery'), 
        null, 
        true
    );

    wp_localize_script('lef-invites-js', 'lefInvitesData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
        
    wp_enqueue_script(
        'lef-color-picker',
        plugin_dir_url(__FILE__) . 'js/color-picker.js',
        array('jquery'), 
        null, 
        true
    );
    
    wp_localize_script('lef-color-picker', 'lefCustomData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));

}
add_action('wp_enqueue_scripts', 'lef_enqueue_scripts');
