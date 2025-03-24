<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add settings page under the LEF menu
function lef_add_settings_page() {
    add_submenu_page(
        'lef_main_menu', // Parent menu (LEF menu)
        'LEF Instellingen', // Page title
        'Instellingen', // Menu title
        'manage_options', // Capability
        'lef_settings', // Slug
        'lef_render_settings_page' // Callback function
    );
}
add_action('admin_menu', 'lef_add_settings_page');

// Render the settings page
function lef_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>LEF Instellingen</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('lef_settings_group');
            do_settings_sections('lef_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings and color fields
function lef_register_settings() {
    register_setting('lef_settings_group', 'lef_primary_color');
    register_setting('lef_settings_group', 'lef_secondary_color');
    register_setting('lef_settings_group', 'lef_tertiary_color');

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
}
add_action('admin_init', 'lef_register_settings');

// Callback function for color picker fields
function lef_color_picker_callback($args) {
    $option_name = $args['option_name'];
    $color_value = get_option($option_name, '#000000'); // Default to black

    echo '<input type="text" class="lef-color-picker" name="' . esc_attr($option_name) . '" value="' . esc_attr($color_value) . '">';
}

// Enqueue color picker script
function lef_enqueue_color_picker($hook) {
    if ($hook !== 'lef_main_menu_page_lef_settings') {
        return;
    }
    
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script(
        'lef-color-picker-script',
        plugins_url('../js/color-picker.js', __FILE__), // Adjusted path
        array('wp-color-picker'),
        false,
        true
    );
}
add_action('admin_enqueue_scripts', 'lef_enqueue_color_picker');

// Add dynamic styles based on user-defined colors
function lef_dynamic_styles() {
    $primary = get_option('lef_primary_color', '#000000');
    $secondary = get_option('lef_secondary_color', '#ffffff');
    $tertiary = get_option('lef_tertiary_color', '#cccccc');

    echo "<style>
        .lef-primary { color: {$primary}; }
        .lef-secondary { color: {$secondary}; }
        .lef-tertiary { color: {$tertiary}; }
    </style>";
}
add_action('wp_head', 'lef_dynamic_styles');
