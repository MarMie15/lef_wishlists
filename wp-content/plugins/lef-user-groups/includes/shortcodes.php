<?php
// Prevent direct access
if (!defined('ABSPATH')) exit;

/**
 * shortcode to:
 * Displays a front-end form to create a wishlist.
 */
function lef_create_wishlist_submission_form() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to create a wishlist.</p>';
    }

    // Get current user ID
    $user_id = get_current_user_id();

    // Form HTML
    ob_start();
    ?>
    <form id="lef-wishlist-form" class="lef-form-item" method="post">
        <input type="text" id="wishlist_title" placeholder="Wishlist name" name="wishlist_title" required>
        <input type="hidden" name="lef_wishlist_nonce" value="<?php echo wp_create_nonce('lef_wishlist_nonce'); ?>">
        <button type="submit">Create Wishlist</button>
    </form>
    
    <?php
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lef_wishlist_nonce'])) {
        if (!wp_verify_nonce($_POST['lef_wishlist_nonce'], 'lef_wishlist_nonce')) {
            echo '<p>Security check failed. Please try again.</p>';
            return ob_get_clean();
        }

        if (!empty($_POST['wishlist_title'])) {
            $wishlist_title = sanitize_text_field($_POST['wishlist_title']);

            // Insert new post
            $post_data = array(
                'post_title'   => $wishlist_title,
                'post_type'    => 'lef_wishlist',
                'post_status'  => 'publish',
                'post_author'  => $user_id,
            );

            $post_id = wp_insert_post($post_data);

            if ($post_id) {
                //if a post is made, redirect the user to this post
                wp_redirect(get_permalink($post_id));
                echo '<p>Wishlist created successfully!</p>';
                exit;
            } else {
                echo '<p>Failed to create wishlist. Please try again.</p>';
            }
        } else {
            echo '<p>Please enter a wishlist name.</p>';
        }
    }
    return ob_get_clean();
}
// Register the shortcode
add_shortcode('lef_create_wishlist_form', 'lef_create_wishlist_submission_form');

function lef_display_wishlist_items_shortcode($atts) {
    //loop trough all the items associated with the current post wishlist
    global $wpdb;

    // Get the wishlist ID (defaults to the current post ID)
    $atts = shortcode_atts(array(
        'wishlist_id' => get_the_ID(),
    ), $atts);

    $wishlist_id = intval($atts['wishlist_id']);

    if ($wishlist_id <= 0) {
        return '<p>Error: Invalid wishlist ID.</p>';
    }

    // Define the table name
    $table_name = $wpdb->prefix . 'lef_wishlist_items';

    // Query for all product IDs in this wishlist
    $product_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT product_id FROM $table_name WHERE wishlist_id = %d", $wishlist_id
    ));

    if (empty($product_ids)) {
        return '<p class="lef-empty-wishlist">No items in this wishlist.</p>';
    }

    // Start output buffering
    ob_start();
    echo '<ul class="lef-wishlist-items">';
    // Loop through product IDs and fetch product details
    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product) continue; // Skip if product does not exist

        $product_url   = get_permalink($product_id);
        $product_image = get_the_post_thumbnail($product_id, 'thumbnail');
        $product_title = $product->get_name();
        $product_price = $product->get_price_html(); // Get formatted price

        echo '<li class="lef-wishlist-item">';
        echo '<a href="' . esc_url($product_url) . '">';
        echo '<div class="lef-item-image">' . ($product_image ? $product_image : '<img src="' . esc_url(wc_placeholder_img_src()) . '" alt="No Image">') . '</div>';
        echo '<div class="lef-item-details">';
        echo '<span class="lef-item-title">' . esc_html($product_title) . '</span>';
        echo '<span class="lef-item-price">' . wp_kses_post($product_price) . '</span>';
        echo '</div>';
        echo '</a>';
        echo '<span class="lef-delete-button" data-type="delete_wishlist_item" data-wishlist-id="'. $wishlist_id . '" data-product-id="' . $product_id . '"> ❌ </span>';
        echo '</li>';
        }

    echo '</ul>';
    echo '</div>';

    return ob_get_clean(); // Return the buffered output
}
add_shortcode( 'lef_display_wishlist_items', 'lef_display_wishlist_items_shortcode' );

/**
 * Shortcode to allow a user to add products to their wishlist via a form.
 */
function lef_add_product_to_wishlist_form( $atts ) {
    // Extract the wishlist_id from shortcode attributes
    $atts = shortcode_atts( array(
        'wishlist_id' => get_the_ID(), // Default to current post ID
    ), $atts );

    $wishlist_id = intval( $atts['wishlist_id'] );

    if ( $wishlist_id === 0 ) {
        return '<p>Error: Wishlist ID not found.</p>';
    }

    ob_start();
    ?>
    <div id="wishlist-form" class="lef-form-item" >
        <input type="text" id="wishlist-item-search" placeholder="Type to add product">
        <ul id="wishlist-search-results" class="lef-wishlist-items"></ul>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'lef_add_product_to_wishlist', 'lef_add_product_to_wishlist_form' );

/**
 * shortcode to show a user the wishlists they are in
 */
function lef_display_user_wishlists_shortcode( $atts ) {
    if (!is_user_logged_in()) {
        return '<p class="lef-no-wishlists">You must be logged in to see your wishlists.</p>';
    }

    $user_id = get_current_user_id();

    $args = array(
        'post_type'      => 'lef_wishlist',
        'post_status'    => 'publish',
        'author'         => $user_id,
        'posts_per_page' => -1, // Get all wishlists
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $wishlists = get_posts($args);

    if (empty($wishlists)) {
        return '<p class="lef-no-wishlists">You have no wishlists.</p>' ;
    }

    // Build the output
    $output = '<ul class="lef-user-wishlists">';
    foreach ($wishlists as $wishlist) {
        $output .= '<li class="lef-list-item lef-user-wishlist-item">';
        $output .= '<a href="' . get_permalink($wishlist->ID) . '">';
        $output .= esc_html($wishlist->post_title);
        $output .= '</a>';
        $output .= '<span class="lef-delete-button" data-id="' . esc_attr($wishlist->ID) . '" data-type="delete_wishlist">❌</span>';
        $output .= '</li>';
    }
    $output .= '</ul>';

    return $output;
}
add_shortcode( 'lef_display_user_wishlists', 'lef_display_user_wishlists_shortcode' );

//shortcode for groups
/**
 * shortcode to:
 * Displays a front-end form to create a wishlist.
 */
function lef_create_group_submission_form() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to create a group.</p>';
    }

    // Get current user ID
    $user_id = get_current_user_id();

    // Form HTML
    ob_start();
    ?>
    <form id="lef-group-form" class="lef-form-item" method="post">
        <input type="text" id="group_title" placeholder="Create group here!" name="group_title" required>
        <input type="hidden" name="lef_group_nonce" value="<?php echo wp_create_nonce('lef_group_nonce'); ?>">
        <button type="submit">Create Group</button>
    </form>
    
    <?php
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lef_group_nonce'])) {
        if (!wp_verify_nonce($_POST['lef_group_nonce'], 'lef_group_nonce')) {
            echo '<p>Security check failed. Please try again.</p>';
            return ob_get_clean();
        }

        if (!empty($_POST['group_title'])) {
            $group_title = sanitize_text_field($_POST['group_title']);

            // Insert new post
            $post_data = array(
                'post_title'   => $group_title,
                'post_type'    => 'lef_groepen',
                'post_status'  => 'publish',
                'post_author'  => $user_id,
            );

            $post_id = wp_insert_post($post_data);

            if ($post_id) {
                //if a post is made, redirect the user to this post
                wp_redirect(get_permalink($post_id));
                echo '<p>Group created successfully!</p>';
                exit;
            } else {
                echo '<p>Failed to create group. Please try again.</p>';
            }
        } else {
            echo '<p>Please enter a group name.</p>';
        }
    }
    return ob_get_clean();
}
// Register the shortcode
add_shortcode('lef_create_group_form', 'lef_create_group_submission_form');

/**
 * shortcode to show a user the wishlists they have
 */
function lef_display_user_groups_shortcode( $atts ) {
    if (!is_user_logged_in()) {
        return '<p class="lef-no-groups">You must be logged in to see your groups.</p>';
    }

    global $wpdb;
    $user_id = get_current_user_id();

    $group_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT group_ID FROM {$wpdb->prefix}lef_groups_users WHERE user_ID = %d AND has_joined = 1",
            $user_id
        )
    );

    if (empty($group_ids)) {
        return '<p class="lef-no-groups">You have no groups.</p>';
    }

    // Fetch the group posts from the lef_groepen post type
    $args = array(
        'post_type'      => 'lef_groepen',
        'post_status'    => 'publish',
        'post__in'       => $group_ids, // Filter by group IDs found in the database
        'posts_per_page' => -1, // Get all groups
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $groups = get_posts($args);

    if (empty($groups)) {
        return '<p class="lef-no-groups">You have no groups.</p>';
    }

    // Build the output
    $output = '<ul class="lef-user-groups">';
    foreach ($groups as $group) {
        $output .= '<li class="lef-list-item lef-user-groups-item">';
        $output .= '<a href="' . get_permalink($group->ID) . '">';
        $output .= esc_html($group->post_title);
        $output .= '</a>';
        $output .= '</li>';
    }
    $output .= '</ul>';

    return $output;
}
add_shortcode( 'lef_display_user_groups', 'lef_display_user_groups_shortcode' );

/**
 * shortcode for a group post to show the users within this group
 */
function lef_show_group_users_shortcode( $atts ) {
    global $wpdb;

    $current_user_id = get_current_user_id();

    // Get group ID from attributes or fallback to current post ID
    $atts = shortcode_atts(array(
        'group_id' => get_the_ID(), // Use current post ID if not provided
    ), $atts);

    $group_id = intval($atts['group_id']);

    // Ensure we're on a group post type
    if (get_post_type($group_id) !== 'lef_groepen') {
        return '<p class="lef-no-users">Invalid group.</p>';
    }

    // Check if the current user is an owner of the group
    $is_owner = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM wp_lef_groups_users WHERE group_id = %d AND user_id = %d AND is_owner = 1",
            $group_id,
            $current_user_id
        )
    );

    // Fetch users who have joined (separating owners and non-owners)
    $joined_users = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_id, is_owner FROM wp_lef_groups_users WHERE group_id = %d AND has_joined = 1 ORDER BY is_owner DESC",
            $group_id
        )
    );

    // Fetch users who have been invited but haven't joined yet
    $invited_users = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_id, NULL as email FROM wp_lef_groups_users 
             WHERE group_id = %d AND has_joined = 0
             UNION 
             SELECT NULL as user_id, email FROM wp_lef_group_invites 
             WHERE group_ID = %d",
            $group_id, $group_id
        )
    );

    // Generate output
    $output = '<div class="lef-group-users">';

    // Show joined users
    if (!empty($joined_users)) {
        $output .= '<h3>Current Users:</h3><ul>';
        foreach ($joined_users as $user) {
            $user_info = get_userdata($user->user_id);
            if ($user_info) {
                
                $user_display = '';

                if ($user->is_owner) {
                    $user_display .= '👑 ';
                }

                $user_display .= esc_html($user_info->display_name);

                $output .= '<li class="lef-list-item display-block">' . $user_display;

                //creates a delete button for any owner of the group
                if ($is_owner && !$user->is_owner) {
                    $output .= 
                        '<span style="margin-left: 30px; " 
                            class="lef-delete-button" 
                            data-type="remove_user_from_group" 
                            data-user-id=" ' . esc_attr($user_info->ID) . '" 
                            data-group-id="' . esc_attr($group_id) . '">❌ 
                        </span>';
                }

                $output .='</li>';
            }
        }
        $output .= '</ul>';
    }

    if ($is_owner) {
        global $post; // Get the current post
        $group_id = $post->ID; // Assuming post ID represents the group ID
    
        $output .= 
        '<form id="lef_invite_user" class="lef-form-item" method="post" data-group-id="' . esc_attr($group_id) . '">
            <label for="lef_invite-user-input">Invite a friend</label><br>
            <input type="text" class="lef_invite-user-input" placeholder="friend'."'".'s email">
            <button type="submit">Send invite!</button>
        </form>';
    }
    
    // Show invited but not joined users
    if (!empty($invited_users)) {
        $output .= '<h3>Pending invites:</h3><ul>';
        foreach ($invited_users as $user) {
            if (!empty($user->user_id)) {
                // User exists in the system but hasn't joined yet
                $user_info = get_userdata($user->user_id);
                if ($user_info) {
                    $output .= '<li class="lef-list-item display-block">' . esc_html($user_info->display_name) . ' (Pending)';
                }
                if ($is_owner) {
                    $output .= 
                        '<span style="margin-left: 30px; " 
                            class="lef-delete-button" 
                            data-type="remove_invite_existing_user" 
                            data-user-id=" ' . esc_attr($user->user_id) . '" 
                            data-group-id="' . esc_attr($group_id) . '"
                            >❌</span>';
                }
            } elseif (!empty($user->email)) {
                // User is invited but not registered
                $output .= '<li class="lef-list-item display-block">' . esc_html($user->email) . ' (No Account Yet)';
                if ($is_owner) {
                    $output .= 
                        '<span style="margin-left: 30px; " 
                            class="lef-delete-button" 
                            data-type="remove_invite_unknown_user" 
                            data-user-email="' . esc_attr($user->email) . '" 
                            data-group-id="' . esc_attr($group_id) . '"
                            >❌</span>';
                }
            }
            $output .= '</li>';
        }
        $output .= '</ul>';
    }
    $output .= '</div>';
    
    return $output;
}
add_shortcode('lef_show_group_users', 'lef_show_group_users_shortcode');


/**
 * shortcode to allow a user to add one of their wishlists to this group
 */
// Shortcode: Add Wishlist to Group
function lef_add_wishlist_to_group_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p class="lef-error">You must be logged in to add a wishlist.</p>';
    }

    $atts = shortcode_atts(array(
        'group_id' => 0,
    ), $atts);

    $group_id = intval($atts['group_id']);
    
    if ($group_id <= 0) return '<p class="lef-error">Invalid group.</p>';

    ob_start();
    ?>
    <div class="lef-add-group-wishlist lef-form-item">
        <label for="group-wishlist-input">Add your wishlist to group:</label><br>
        <input type="text" id="group-wishlist-input" placeholder="Search your wishlists...">
        <ul id="group-wishlist-dropdown" class="lef-dropdown" style="display: none;"></ul>
        <p id="group-wishlist-message" style="display: none;"></p>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            lefGroupWishlist.init(<?php echo $group_id; ?>);
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('lef_add_wishlist_to_group', 'lef_add_wishlist_to_group_shortcode');


/**
 *  shortcode to show all the wishlists that a user has added to this group
 */

 function lef_display_group_wishlists_shortcode($atts) {
     global $wpdb;
    if (!is_user_logged_in()) {
        return '<p class="lef-error">You must be logged in to see your wishlists.</p>';
    }

    $atts = shortcode_atts(array(
        'groepen_id' => 0,
    ), $atts);

    $group_id = intval($atts['groepen_id']);
    $user_id = get_current_user_id();

    if ($group_id <= 0) return '<p class="lef-error">Invalid group.</p>';

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT w.ID, w.post_title 
         FROM {$wpdb->prefix}lef_group_wishlists gw 
         JOIN {$wpdb->prefix}posts w ON gw.wishlist_ID = w.ID 
         WHERE gw.group_ID = %d AND w.post_author = %d",
        $group_id, $user_id
    ));

    if (empty($results)) {
        return '<p class="lef-no-wishlists">You have not added any wishlists to this group.</p>';
    }
    $output = '<h3>Your wishlists</h3>';
    $output .= '<ul>';
    
    //fix styling here
    foreach ($results as $wishlist) {
        $output .= '<li class="lef-list-item">';
        $output .= '<a href="' . get_permalink($wishlist->ID) . '">'; 
        $output .= esc_html($wishlist->post_title);
        $output .= '</a>';
        $output .= '<span style="margin-left: 30px;"
            class="lef-delete-button"
            data-type="remove_wishlist_from_group"
            data-wishlist-id="'. esc_attr($wishlist->ID) . '" 
            data-group-id="' . esc_attr($group_id) . '"
            >❌</span>';
        $output .= '</li">';
    }
    $output .= '</ul>';

    return $output;
}
add_shortcode('lef_display_group_wishlists', 'lef_display_group_wishlists_shortcode');

function lef_delete_group_button_shortcode() {
    global $wpdb;

    $group_id = get_the_ID();
    $user_id = get_current_user_id();

    // Check if the current user is an owner of the group
    $is_owner = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM wp_lef_groups_users WHERE group_id = %d AND user_id = %d AND is_owner = 1",
        $group_id,
        $user_id
    ));

    if (!$is_owner) {
        return ''; // Don't display anything if the user is not an owner
    }

    return '<div id="lef-delete-group-container">
                <ul>
                    <li class="lef-list-item lef-delete-group-button" 
                        data-type="delete_group" 
                        data-id="' . esc_attr($group_id) . '">
                        ❌ Delete Group ❌
                    </li>
                </ul>
            </div>';
}
add_shortcode('lef_delete_group_button', 'lef_delete_group_button_shortcode');

function lef_wishlist_nav_button_shortcode() {
    $wishlist_url = esc_url(site_url('/lef-groups/'));

    return '<div class="menu-item lef-wishlist-nav">
                <a href="' . $wishlist_url . '"><span class="dashicons dashicons-list-view"></span></a>
            </div>';
}
add_shortcode('lef_wishlist_button', 'lef_wishlist_nav_button_shortcode');





//testing selecting colors, delete later
function lef_color_test_shortcode(){
    $output = "<p>selected colors: </p>";
    $output .= '<p class="color1">bing</p>'.
              '<p class="color2">bang</p>'.
              '<p class="color3">boom</p>'.
              '<p class="color4">bap</p>';
              
    return $output;
}
add_shortcode('lef_color_test','lef_color_test_shortcode');








//testing email styling, delete once done
function lef_send_test_email() {
    // Fetch configurable colors from theme
    $primary_color = get_theme_mod('lef_primary_color', '#1f8a4d');
    $text_color = get_theme_mod('lef_text_color', '#ffffff');
    $hover_color = get_theme_mod('lef_tertiary_color', "#1a713e");
    
    // Get the logo URL from the options table
    $logo_url = get_option('lef_logo_image');

    // If no logo was set in the settings, use a default fallback
    if (!$logo_url) {
        $logo_url = get_site_url() . "/wp-content/uploads/2025/02/RDT_20250112_1217216191111379225706802.gif";
    }

    // Convert URL to server path for attachment
    $uploads_dir = wp_upload_dir();
    $site_url = $uploads_dir['baseurl'];
    $site_dir = $uploads_dir['basedir'];

    // Replace the site URL with the absolute server path
    $logo_path = str_replace($site_url, $site_dir, $logo_url);
    
    $site_title = get_bloginfo('name');
    // Generate a unique content ID for the image
    $image_cid = md5(time()) . '@lefcreative.nl';
    
    $to = "marcel@lefcreative.nl";
    $subject = "$site_title - You've been invited to a group!";
    
    $message = "
    <html>
    <head>
        <title>LEF Creative - Email Test</title>
        <style>
            body { 
                font-family: Arial,
                sans-serif;
                background-color: #f4f4f4;
                padding: 20px;
                text-align: center;
            }

            .email-container {
                background: #ffffff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
                max-width: 600px;
                margin: auto;
            }

            .logo-container { 
                background: $primary_color; 
                padding: 15px; 
                text-align: center; 
            }

            .logo-container img { height: 50px; }

            .header { 
                font-size: 22px; 
                font-weight: bold; 
                color: #333; 
                margin-top: 15px; 
            }

            .content { 
                margin-top: 10px; 
                font-size: 16px; 
                color: #555; 
                padding: 10px; 
            }

            .footer { 
                margin-top: 20px; 
                font-size: 14px; 
                color: #888; 
            }

            .button {
                display: inline-block;
                padding: 10px 20px;
                margin-top: 15px;
                background: $primary_color;
                color: $text_color;
                text-decoration: none;
                border-radius: 5px;
                font-weight: bold;
            }
            .button:hover {
                background: $hover_color;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='logo-container'>
                <img src='cid:$image_cid' alt='$site_title'>
            </div>
            <div class='header'>Welcome to $site_title!</div>
            <div class='content'>
                Someone has invited to join a group
                <br>
                <a href='#' class='button'>Click Here to join!</a>
                <br>
                <p>This invite will expire in 7 days.</p>
            </div>
            <div class='footer'>Thank you for using LEF Creative.</div>
        </div>
    </body>
    </html>";

    // Email headers
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: LEF Creative <websites@lefcreative.nl>'
    ];
    
    // Attachments - add the logo as an attachment
    $attachments = array();
    
    // Only proceed if the logo file exists
    if (file_exists($logo_path)) {
        $attachments[] = $logo_path;
        
        // Use PHPMailer to send the email with embedded image
        add_action('phpmailer_init', function($phpmailer) use ($logo_path, $image_cid) {
            // Add the embedded image
            $phpmailer->AddEmbeddedImage(
                $logo_path,     // Path to the image
                $image_cid,     // Content ID (used in the HTML above)
                basename($logo_path), // Filename
                'base64',       // Encoding
                mime_content_type($logo_path) // MIME type
            );
        });
    }
    
    // For testing in browser, display the message
    if (isset($_GET['test_view']) && $_GET['test_view'] == 'email') {
        echo $message;
        exit;
    }
    
    // Send the email
    $sent = wp_mail($to, $subject, $message, $headers, $attachments);
    
    // Remove our temporary phpmailer_init hook to avoid affecting other emails
    remove_all_actions('phpmailer_init');
    
    // Return response in WordPress
    return $sent ? "Test email sent successfully!" : "Failed to send test email.";
}

// Register shortcode
add_shortcode('send_test_email', 'lef_send_test_email');