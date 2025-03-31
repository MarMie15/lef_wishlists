<?php
// Prevent direct access
if (!defined('ABSPATH')) exit;

function lef_enqueue_scripts() {
    // Enqueue the main plugin CSS file for the entire site
    wp_enqueue_style(
        'lef-main-style',
        plugin_dir_url(__FILE__) . '../css/lef_style.css', // Adjust path if needed
        array(), // No dependencies
        null // No versioning, forces latest version
    );

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
            'wishlist_id' => intval(get_the_ID()), // Get current wishlist post ID
            'placeholder_image' => wc_placeholder_img_src()
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
}
add_action('wp_enqueue_scripts', 'lef_enqueue_scripts');

function lef_admin_enqueue_scripts($hook) {
    // Check the actual hook value
    if ($hook !== 'lef-groups_page_lef_settings') {
        error_log("Skipping script enqueue. Current page hook: " . $hook);
        return;
    }

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    // Check if the file exists before enqueuing
    $script_path = plugin_dir_path(__FILE__) . 'js/color-picker.js';
    if (file_exists($script_path)) {
        wp_enqueue_script(
            'lef-color-picker-js', 
            plugin_dir_url(__FILE__) . 'js/color-picker.js', 
            array('jquery', 'wp-color-picker'), 
            null, 
            true
        );
    } else {
        error_log("Error: color-picker.js not found at " . $script_path);
    }
}
add_action('admin_enqueue_scripts', 'lef_admin_enqueue_scripts');
