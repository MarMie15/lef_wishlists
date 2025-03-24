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
require_once plugin_dir_path(__FILE__) . 'includes/invite-tab.php';

if (!session_id()) {
    session_start();
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

//to show the amount of invites of a user
function lef_add_invite_notification_badge() {

    $user_id = get_current_user_id();
    global $wpdb;
    
    $invite_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->prefix}lef_groups_users 
        WHERE user_id = %d AND has_joined = 0
    ", $user_id));

    if ($invite_count > 0) {
        ?>
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            let accountButton = document.querySelector('[data-block-name="woocommerce/customer-account"]');
            if (accountButton && !document.querySelector(".lef-invite-badge")) {
                let badge = document.createElement("span");
                badge.textContent = "<?php echo $invite_count; ?>";
                badge.classList.add("lef-invite-badge");

                // Ensure the button wrapper is properly positioned
                accountButton.style.position = "relative";

                accountButton.appendChild(badge);
            }
        });
        </script>
        <style>
        .lef-invite-badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: red;
            color: white;
            font-size: 10px;
            font-weight: bold;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 10;
        }
        </style>
        <?php
    }
}
add_action('wp_footer', 'lef_add_invite_notification_badge');

function lef_handle_existing_user_invite() {
    error_log("lef_handle_existing_user_invite called");
    // Start the session if not already started
    if (!session_id()) {
        session_start();
        error_log("session started");
    }

    if (!isset($_GET['join_group']) || !isset($_GET['user_id'])) {
        return; // Exit if no invite URL is accessed
    }

    // Check if the invite has already been accepted
    if (isset($_SESSION['invite_accepted']) && $_SESSION['invite_accepted'] === true) {
        error_log("Invite already accepted. Skipping invite logic.");
        return; // Skip processing if the invite has already been accepted
    }

    // Check if the necessary GET parameters are set
    if (isset($_GET['join_group']) && isset($_GET['user_id'])) {
        $group_id = intval($_GET['join_group']);
        $user_id = intval($_GET['user_id']);

        error_log("Group ID: $group_id, User ID: $user_id");

        // Get the current logged-in user
        if (!is_user_logged_in()) {
            // User is not logged in, so redirect to login page and store the invite token
            error_log("User is not logged in. Redirecting to login page...");
            
            // Store the invite data in the session
            $_SESSION['invite_group_id'] = $group_id;
            $_SESSION['invite_user_id'] = $user_id;

            // Redirect to the login page
            wp_redirect(site_url("/index.php/my-account-2/"));
            exit;
        }
        
        $current_user = wp_get_current_user();

        // Check if the current user is the one invited
        if ($current_user->ID == $user_id) {
            error_log("User is logged in and matches the invited user.");

            // Accept the invite and add the user to the group
            global $wpdb;
            $table_name = $wpdb->prefix . 'lef_groups_users';

            // Check if the user is already in the group
            $existing_user = $wpdb->get_var($wpdb->prepare(
                "SELECT has_joined FROM $table_name WHERE group_id = %d AND user_id = %d",
                $group_id, $user_id
            ));

            if ($existing_user === null) {
                // If the user is not in the group, insert them into the group
                $wpdb->insert($table_name, [
                    'group_id' => $group_id,
                    'user_id' => $user_id,
                    'has_joined' => 1,
                    'added_at' => current_time('mysql')
                ]);
                error_log("User added to the group.");
            } elseif ($existing_user == 0) {
                // If the user is in the group but hasn't joined, update their status to 'joined'
                $wpdb->update(
                    $table_name,
                    ['has_joined' => 1],
                    ['group_id' => $group_id, 'user_id' => $user_id]
                );
                error_log("User's invite accepted and updated to 'has_joined'.");
            }

            // Set the session flag indicating that the invite has been accepted
            $_SESSION['invite_accepted'] = true;

            // Redirect to the group page
            wp_redirect(get_permalink($group_id));
            exit;
        } else {
            error_log("User email does not match invite. Redirecting to homepage...");
            wp_redirect(home_url());
            exit;
        }
    } else {
        error_log("Missing or invalid parameters in the invite URL.");
    }
}
add_action('template_redirect', 'lef_handle_existing_user_invite');

function lef_process_invite_after_login($user_login, $user) {
    error_log("lef_process_invite_after_login");
    // Check if there is an invite stored in the session
    if (isset($_SESSION['invite_group_id']) && isset($_SESSION['invite_user_id'])) {
        global $wpdb;
        $group_id = $_SESSION['invite_group_id'];
        $user_id = $_SESSION['invite_user_id'];

        error_log("Processing invite for Group ID: $group_id, User ID: $user_id");

        // If $user isn't an instance of WP_User, get the current user object
        if (is_string($user) || !is_object($user) || !isset($user->ID)) {
            $user = wp_get_current_user();
        }

        // Get the current logged-in user
        if ($user->ID == $user_id) {
            error_log("found Id User ID: $user_id");
            // Accept the invite and add the user to the group
            $table_name = $wpdb->prefix . 'lef_groups_users';

            // Check if the user is already in the group
            $existing_user = $wpdb->get_var($wpdb->prepare(
                "SELECT has_joined FROM $table_name WHERE group_id = %d AND user_id = %d",
                $group_id, $user_id
            ));

            if ($existing_user === null) {
                // If the user is not in the group, insert them into the group
                $wpdb->insert($table_name, [
                    'group_id' => $group_id,
                    'user_id' => $user_id,
                    'has_joined' => 1,
                    'added_at' => current_time('mysql')
                ]);
                error_log("User added to the group.");
            } elseif ($existing_user == 0) {
                // If the user is in the group but hasn't joined, update their status to 'joined'
                $wpdb->update(
                    $table_name,
                    ['has_joined' => 1],
                    ['group_id' => $group_id, 'user_id' => $user_id]
                );
                error_log("User's invite accepted and updated to 'has_joined'.");
            }

            // Set the session flag indicating that the invite has been accepted
            $_SESSION['invite_accepted'] = true;

            // Redirect to the group page
            wp_redirect(get_permalink($group_id));
            exit;
        }else {
            // If the user ID doesn't match, log an error and do not process
            error_log("User ID doesn't match invite. Ignoring invite processing. ".$user->ID);
        }
    }
}
add_action('wp_login', 'lef_process_invite_after_login', 10, 2);

function lef_handle_new_user_invite() {
    error_log("lef_handle_new_user_invite called");
    if (isset($_GET['accept_invite'])) {
        global $wpdb;
        $token = sanitize_text_field($_GET['accept_invite']);

        // Retrieve invite using the token
        $invite = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lef_group_invites WHERE token = %s", $token
        ));

        if (!$invite) {
            wp_redirect(site_url("/"));
            error_log("no invite");
            exit;
        }

        if (!is_user_logged_in()) {
            // If the user is not logged in, save the token in a session and redirect to the login/register page
            $_SESSION['lef_invite_token'] = $token;  // Store token in session
            wp_redirect(site_url("/index.php/my-account-2/"));
            error_log("user is not logged in");
            exit;
        }

        // If user is logged in, verify the email and add the user to the group
        $user = wp_get_current_user();
        if (strtolower($user->user_email) === strtolower($invite->email)) {
            $wpdb->insert("{$wpdb->prefix}lef_groups_users", [
                'group_id' => $invite->group_id,
                'user_id' => $user->ID,
                'has_joined' => 1,
                'added_at' => current_time('mysql')
            ]);

            // Remove the invite from the database after it has been processed
            $wpdb->delete("{$wpdb->prefix}lef_group_invites", ['token' => $token]);

            // Redirect to the group page
            wp_redirect(site_url("/group-page/?group_id=" . $invite->group_id));
            exit;
        }

        // If the email doesn't match, redirect to the homepage
        wp_redirect(site_url("/"));
        exit;
    }
}
add_action('init', 'lef_handle_new_user_invite');

function lef_accept_invite_after_login($user_login, $user) {
    error_log("lef_accept_invite_after_login called");
    // Check if invite token is stored in session (from the earlier redirection)
    if (isset($_SESSION['lef_invite_token'])) {
        global $wpdb;
        $token = sanitize_text_field($_SESSION['lef_invite_token']);

        // Get invite details using token
        $invite = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lef_group_invites WHERE token = %s", $token
        ));

        if ($invite && strtolower($user->user_email) === strtolower($invite->email)) {
            // Add user to the group
            $wpdb->insert("{$wpdb->prefix}lef_groups_users", [
                'group_id' => $invite->group_id,
                'user_id' => $user->ID,
                'has_joined' => 1,
                'added_at' => current_time('mysql')
            ]);

            // Remove the invite from the database and clear the invite token from the session
            $wpdb->delete("{$wpdb->prefix}lef_group_invites", ['token' => $token]);
            unset($_SESSION['lef_invite_token']);  // Clear session after processing

            // Redirect to the group page after joining
            wp_redirect(site_url("/group-page/?group_id=" . $invite->group_id));
            exit;
        } else {
            // If email doesn't match the invite, redirect to the homepage
            wp_redirect(site_url("/"));
            exit;
        }
    }
}
add_action('wp_login', 'lef_accept_invite_after_login', 10, 2);