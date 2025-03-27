<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

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

// Register the settings submenu under "LEF Groups"
function lef_register_settings_submenu() {
    add_submenu_page(
        'lef_main_menu',      // Parent menu slug (LEF Groups)
        'LEF Settings',       // Page title
        'Settings',           // Menu title
        'manage_options',     // Capability (only admins)
        'lef_settings',       // Menu slug
        'lef_settings_page_html' // Callback function to render the page
    );
}
add_action('admin_menu', 'lef_register_settings_submenu');

//give post from groepen or wishlists the old editor 
function disable_gutenberg_for_groepen($use_block_editor, $post_type) {
    if ($post_type === 'lef_groepen' || $post_type === 'lef_wishlist') {
        return false; // Disable Gutenberg for 'groepen' post type
    }
    return $use_block_editor;
}
add_filter('use_block_editor_for_post_type', 'disable_gutenberg_for_groepen', 10, 2);

// Ensure the settings page can be accessed
function lef_settings_page_html() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    echo '<div class="wrap">';
    echo '<h1>LEF Settings</h1>';
    echo '<form method="post" action="options.php">';
    
    settings_fields('lef_settings_group'); // Security fields
    do_settings_sections('lef_settings'); // Output sections
    submit_button(); // Save button

    echo '</form>';
    echo '</div>';
}


// Register settings and color fields
function lef_register_settings() {
    register_setting('lef_settings_group', 'lef_primary_color');
    register_setting('lef_settings_group', 'lef_secondary_color');
    register_setting('lef_settings_group', 'lef_tertiary_color');
    register_setting('lef_settings_group', 'lef_text_color');
    register_setting('lef_settings_group', 'lef_font_color'); // New Font Color setting

    add_settings_section('lef_colors_section', 'Huisstijl Kleuren', null, 'lef_settings');

    add_settings_field(
        'lef_primary_color',
        'Primaire kleur',
        'lef_color_picker_callback',
        'lef_settings',
        'lef_colors_section',
        array('option_name' => 'lef_primary_color')
    );

    add_settings_field(
        'lef_secondary_color',
        'Secundaire kleur',
        'lef_color_picker_callback',
        'lef_settings',
        'lef_colors_section',
        array('option_name' => 'lef_secondary_color')
    );

    add_settings_field(
        'lef_tertiary_color',
        'Tertiaire kleur',
        'lef_color_picker_callback',
        'lef_settings',
        'lef_colors_section',
        array('option_name' => 'lef_tertiary_color')
    );
    
    add_settings_field(
        'lef_text_color',
        'Text kleur', 
        'lef_color_picker_callback',
        'lef_settings', 
        'lef_colors_section',
        array('option_name' => 'lef_text_color')
    );
}
add_action('admin_init', 'lef_register_settings');

// Callback function for color picker fields
function lef_color_picker_callback($args) {
    $option_name = $args['option_name'];
    $color_value = get_option($option_name);

    echo '<input type="text" class="lef-color-picker" id="'. esc_attr($option_name) .'" name="' . esc_attr($option_name) . '" value="' . esc_attr($color_value) . '" />';
    echo '<p>Current value: ' . esc_html($color_value) . '</p>'; // Debug output
}

// Add dynamic styles based on user-defined colors
function lef_dynamic_styles() {
    $primary = get_option('lef_primary_color', '#2594de');
    $secondary = get_option('lef_secondary_color', '#15c75f');
    $tertiary = get_option('lef_tertiary_color', '#cd3e2e');
    $text = get_option('lef_text_color', '#ffd000');

    echo "<style>
        :root {
            --lef-primary-color: {$primary};
            --lef-secondary-color: {$secondary};
            --lef-tertiary-color: {$tertiary};
            --lef-text-color: {$text};
        }
    </style>";
}
add_action('wp_head', 'lef_dynamic_styles');
