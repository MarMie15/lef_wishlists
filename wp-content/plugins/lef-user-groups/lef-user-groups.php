<?php
/**
* Plugin Name: LEF wishlist & groups system
* Description: Custom system to allow users to make groups
* Version: 1.0.7
* Author: Marcel Miedema
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/scripts.php';
require_once plugin_dir_path(__FILE__) . 'includes/database.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';

//makes all the required tables within the database
register_activation_hook(__FILE__, 'create_custom_plugin_tables');

 // create custom post menu
function lef_custom_menu() {
    add_menu_page(
        'LEF groups', // Page Title
        'LEF groups', // Menu Title
        'manage_options', // Capability
        'lef_main_menu', // Menu Slug
        '', // No function needed, it's just a container
        'dashicons-star-filled', // Custom icon
        5
    );
}
add_action('admin_menu', 'lef_custom_menu');

//create custom post type lef_groepen
function create_group_post_type() {
    register_post_type('lef_groepen',
        array(
            'labels' => array(
                'name' => 'Groepen',
                'singular_name' => 'Groep',
                'add_new' => 'Nieuwe Groep',
                'edit_item' => 'Bewerk Groep',
                'all_items' => 'Alle Groepen',
                'view_item' => 'Bekijk Groep',
                'search_items' => 'Zoek Groepen',
                'not_found' => 'Geen groepen gevonden',
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'groepen'),
            'supports' => array('title', 'editor', 'author'),
            'show_in_rest' => true,
            'menu_position' => 5,
            'show_in_menu' => 'lef_main_menu', // Attach to custom LEF menu
        )
    );
}
add_action('init', 'create_group_post_type');

//create custom post type lef_wishlist
function create_wishlist_post_type() {
    register_post_type('lef_wishlist',
        array(
            'labels' => array(
                'name' => 'Wishlists',
                'singular_name' => 'Wishlist',
                'add_new' => 'Nieuwe Wishlist',
                'edit_item' => 'Bewerk Wishlist',
                'all_items' => 'Alle Wishlists',
                'view_item' => 'Bekijk Wishlist',
                'search_items' => 'Zoek Wishlists',
                'not_found' => 'Geen wishlists gevonden',
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'wishlists'),
            'supports' => array('title', 'editor', 'author'),
            'show_in_rest' => true,
            'menu_position' => 5,
            'show_in_menu' => 'lef_main_menu', // Attach to custom LEF menu
        )
    );
}
add_action('init', 'create_wishlist_post_type');

//give post from groepen or wishlists the old editor 
function disable_gutenberg_for_groepen($use_block_editor, $post_type) {
    if ($post_type === 'lef_groepen' || $post_type === 'lef_wishlist') {
        return false; // Disable Gutenberg for 'groepen' post type
    }
    return $use_block_editor;
}
add_filter('use_block_editor_for_post_type', 'disable_gutenberg_for_groepen', 10, 2);

//ensures that the creater of a group gets added as a user and creater of said group
function lef_add_group_creator_to_table($post_ID, $post, $update) {
    global $wpdb;

    // Only run on new posts, not updates  AND  Ensure it's only for the 'lef_groepen' post type
    if ($update || ($post->post_type !== 'lef_groepen')) {
        return;
    }

    // Get the author (creator) of the post
    $user_ID = $post->post_author;
    $group_ID = $post_ID;
    
    // Insert into 'wp_lef_groups_users'
    $wpdb->insert(
        "{$wpdb->prefix}lef_groups_users",
        [
            'group_ID'   => $group_ID,
            'user_ID'    => $user_ID,
            'is_owner'   => 1,
            'has_joined' => 1
        ],
        [
            '%d', '%d', '%d'
        ]
    );
}
// Hook into wp_insert_post
add_action('wp_insert_post', 'lef_add_group_creator_to_table', 10, 3);

/**
 * attach the following shortcodes to any wishlist post
 */
function lef_auto_append_shortcodes( $content ) {
    //attach the following shortcode's to single posts of lef_wishlist
    if ( is_singular( 'lef_wishlist' ) && is_main_query() ) {
        $wishlist_id = get_the_ID();

        // Append the wishlist form and wishlist items shortcodes.
        $content .= do_shortcode( '[lef_add_product_to_wishlist wishlist_id="' . $wishlist_id . '"] <hr>' );
        $content .= do_shortcode( '[lef_display_wishlist_items wishlist_id="' . $wishlist_id . '"] <hr>' );
    }

    //attach the following shortcode's to single posts of lef_groepen
    if ( is_singular( 'lef_groepen' ) && is_main_query() ) {
        $groepen_id = get_the_ID();

        // Append the wishlist form and wishlist items shortcodes.
        $content .= do_shortcode( '[lef_show_group_users groepen_id="' . $groepen_id . '"]	<hr>' );
        $content .= do_shortcode( '[lef_add_wishlist_to_group group_id="' . $groepen_id . '"] <hr>' );
        $content .= do_shortcode( '[lef_display_group_wishlists groepen_id="' . $groepen_id . '"] <hr>' );
        $content .= do_shortcode( '[lef_delete_group_button groepen_id="' . $groepen_id . '"]' );
    }
    return $content;
}
add_filter( 'the_content', 'lef_auto_append_shortcodes' );

