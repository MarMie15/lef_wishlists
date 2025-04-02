<?php
/**
* Plugin Name: LEF wishlist & groups system
* Description: Custom system to allow users to make groups
* Version: 1.0.8
* Author: Marcel Miedema
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/scripts.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/database.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'includes/invite-tab.php';

if (!session_id()) {
    session_start();
}


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
        $content .= do_shortcode( '[lef_display_wishlist_items wishlist_id="' . $wishlist_id . '"] ' );
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
            //make sure the endpoint exists, its currently going to the woocommerce account
            wp_redirect(site_url("/index.php/my-account-2/"));
            exit;
        }
        
        $current_user = wp_get_current_user();

        // Check if the current user is the one invited
        if ($current_user->ID == $user_id) {
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

function lef_handle_new_user_invite() {
    if (!session_id()) {
        session_start();
        error_log("Session started successfully");
    }

    if (!isset($_GET['token']) || !isset($_GET['email'])) {
        return;
    }    

    global $wpdb;
    $token = sanitize_text_field($_GET['token']);
    $email = sanitize_email($_GET['email']);

    // Retrieve invite using the token
    $invite = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_lef_group_invites WHERE invite_token = %s", $token));

    if (!$invite) {
        error_log("No invite found for token: " . $token);
        return;
    }
    
    error_log("Invite found for email: " . $invite->email . " in group ID: " . $invite->group_ID);

    if (!$invite) {
        error_log("No invite found for token: " . $token);
        wp_redirect(site_url("/"));
        exit;
    }

    error_log("Invite found for email: " . $invite->email);

    if (!is_user_logged_in()) {
        $_SESSION['lef_invite_token'] = $token;
        $_SESSION['lef_invite_email'] = $invite->email;
        $_SESSION['lef_invite_group_id'] = $invite->group_id;

        // Redirect to the login page        
        //make sure the endpoint exists, its currently going to the woocommerce account
        wp_redirect(site_url("/index.php/my-account-2/"));
        exit;
    }
    exit;
}
add_action('template_redirect', 'lef_handle_new_user_invite');

function lef_process_invite_after_registration($user_id) {
    if (!session_id()) {
        session_start();
    }

    if (!isset($_SESSION['lef_invite_token'], $_SESSION['lef_invite_email'])) {
        error_log("No invite session data found. Skipping invite processing.");
        //current issue ends here
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'lef_group_invites';
    $token = $_SESSION['lef_invite_token'];
    $email = $_SESSION['lef_invite_email'];

    // Perform the query
    $query = $wpdb->prepare(
        "SELECT group_id FROM $table_name WHERE email = %s AND invite_token = %s",
        $email, $token
    );

    $group_id = $wpdb->get_var($query);

    if (!$group_id) {
        error_log("No matching invite found for email: {$email} and invite_token: {$token}");
    }

    // Get the newly created user data
    $user = get_userdata($user_id);

    if (!$user || strtolower($user->user_email) !== strtolower($email)) {
        error_log("Registered user email does not match invite email. Invite ignored.");
        return;
    }

    // Add the user to the group
    $wpdb->insert("{$wpdb->prefix}lef_groups_users", [
        'group_id'   => $group_id,
        'user_id'    => $user_id,
        'has_joined' => 1,
        'added_at'   => current_time('mysql')
    ]);

    // Remove the invite from the database
    $wpdb->delete("{$wpdb->prefix}lef_group_invites", ['invite_token' => $token]);

    // error_log("New user added to group $group_id via invite.");

    // Clear the session variables
    unset($_SESSION['lef_invite_token'], $_SESSION['lef_invite_email'], $_SESSION['lef_invite_group_id']);

    // Redirect to the group page
    wp_redirect(get_permalink($group_id));
    exit;
}
add_action('user_register', 'lef_process_invite_after_registration');

function lef_process_invite_after_login($user_login, $user) {
    // Check if there is an invite stored in the session
    if (isset($_SESSION['invite_group_id']) && isset($_SESSION['invite_user_id'])) {
        global $wpdb;
        $group_id = $_SESSION['invite_group_id'];
        $user_id = $_SESSION['invite_user_id'];

        // error_log("Processing invite for Group ID: $group_id, User ID: $user_id");

        // If $user isn't an instance of WP_User, get the current user object
        if (is_string($user) || !is_object($user) || !isset($user->ID)) {
            $user = wp_get_current_user();
        }

        // Get the current logged-in user
        if ($user->ID == $user_id) {
            // error_log("found Id User ID: $user_id");
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
                // error_log("User added to the group.");
            } elseif ($existing_user == 0) {
                // If the user is in the group but hasn't joined, update their status to 'joined'
                $wpdb->update(
                    $table_name,
                    ['has_joined' => 1],
                    ['group_id' => $group_id, 'user_id' => $user_id]
                );
                // error_log("User's invite accepted and updated to 'has_joined'.");
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

function lef_add_custom_colors_to_head() {
    $primary_color   = get_option('lef_primary_color', '#3498db');
    $secondary_color = get_option('lef_secondary_color', '#2ecc71');
    $tertiary_color  = get_option('lef_tertiary_color', '#e74c3c');

    echo "<style>
        :root {
            --lef-primary-color: {$primary_color};
            --lef-secondary-color: {$secondary_color};
            --lef-tertiary-color: {$tertiary_color};
        }
    </style>";
}
add_action('wp_head', 'lef_add_custom_colors_to_head');


register_activation_hook(__FILE__, 'lef_create_wishlist_page');
function lef_create_wishlist_page() {
    $page_title = 'LEF Wishlists';
    $page_slug = 'lef-wishlists';

    // Check if the page exists
    $page = get_page_by_path($page_slug);
    if (!$page) {
        $page_id = wp_insert_post([
            'post_title'    => $page_title,
            'post_name'     => $page_slug,
            'post_content'  => '[lef_wishlist_dashboard]', // Shortcode for content
            'post_status'   => 'publish',
            'post_type'     => 'page',
        ]);
    }
}

function lef_create_groups_dashboard_page() {
    $page_title = 'LEF Groups';
    $page_slug = 'lef-groups';
    $existing_page = get_page_by_title($page_title);

    if ($existing_page) {
        // If the page already exists, update the option
        update_option('lef_groups_dashboard_page_id', $existing_page->ID);
    } else {
        // Create the page with the correct shortcode
        $page_id = wp_insert_post(array(
            'post_title'    => $page_title,
            'post_content'  => '[lef_wishlist_dashboard]', // Keep shortcode unless changing it later
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_type'     => 'page',
            'post_name'     => $page_slug
        ));

        if ($page_id) {
            update_option('lef_groups_dashboard_page_id', $page_id);
        }
    }

    // Force a permalink flush so the URL works immediately
    flush_rewrite_rules();
}
// Run function on plugin activation
register_activation_hook(__FILE__, 'lef_create_groups_dashboard_page');
