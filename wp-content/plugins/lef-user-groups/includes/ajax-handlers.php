<?php
// Prevent direct access
if (!defined('ABSPATH')) exit;  

/**
 * Fetches product suggestions based on user input.
 *for adding items to wishlist
 */
function lef_search_products() {
    if (!isset($_GET['query']) || empty($_GET['query'])) {
        wp_send_json_error('Missing or empty search query.', 400);
        wp_die();
    }

    global $wpdb;
    $search_query = sanitize_text_field($_GET['query']);
    // Query WooCommerce products
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => 10,
        'post_status'    => 'publish',
        's'              => $search_query
    );

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        wp_send_json_success([""]); // Return empty array if no results
        wp_die();
    }

    $products = [];
    while ($query->have_posts()) {
        $query->the_post();
        $product = wc_get_product(get_the_ID()); // Get WooCommerce product object

        if (!$product) continue;

        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
        
        // Format price: Strike-through regular price if there is a sale price
        if ($sale_price && $sale_price < $regular_price) {
            $formatted_price = '<del>' . wc_price($regular_price) . '</del> <ins>' . wc_price($sale_price) . '</ins>';
        } else {
            $formatted_price = wc_price($regular_price);
        }        
        $products[] = [
            'id'    => $product->get_id(),
            'name'  => $product->get_name(),
            'image' => get_the_post_thumbnail_url($product->get_id(), 'thumbnail') ?: wc_placeholder_img_src(),
            'price' => $formatted_price
        ];
    }
    wp_reset_postdata();
    wp_send_json_success($products);
    wp_die();
}
add_action('wp_ajax_lef_search_products', 'lef_search_products');
add_action('wp_ajax_nopriv_lef_search_products', 'lef_search_products');

function lef_add_product_to_wishlist() {
    global $wpdb;

    if (!isset($_POST['wishlist_id']) || !isset($_POST['product_id'])) {
        wp_send_json_error('Invalid request');
    }

    $wishlist_id = intval($_POST['wishlist_id']);
    $product_id = intval($_POST['product_id']);

    if ($wishlist_id <= 0 || $product_id <= 0) {
        wp_send_json_error(array('message' => 'Invalid request: IDs must be greater than 0'));
    }
    
    // Define table name
    $table_name = $wpdb->prefix . 'lef_wishlist_items';

    // Check if the product is already in the wishlist (avoid duplicates)
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE wishlist_id = %d AND product_id = %d",
        $wishlist_id,
        $product_id
    ));

    if ($existing) {
        wp_send_json_error(array('message' => 'Product is already in the wishlist'));
    }

    // Insert the new item into the wishlist
    $inserted = $wpdb->insert(
        $table_name,
        array(
            'wishlist_id' => $wishlist_id,
            'product_id'  => $product_id
        ),
        array('%d', '%d', '%s')
    );

    if ($inserted) {
        wp_send_json_success(array('message' => 'Product successfully added to wishlist'));
    } else {
        wp_send_json_error(array('message' => 'Database error: Unable to insert item'));
    }

}
add_action('wp_ajax_lef_add_product_to_wishlist', 'lef_add_product_to_wishlist');
add_action('wp_ajax_nopriv_lef_add_product_to_wishlist', 'lef_add_product_to_wishlist');

function lef_get_wishlist_items() {
    if (!isset($_GET['wishlist_id'])) {
        wp_send_json_error('Invalid request');
    }

    $wishlist_id = intval($_GET['wishlist_id']);
    if ($wishlist_id <= 0) {
        wp_send_json_error('Invalid wishlist ID');
    }

    echo do_shortcode('[lef_display_wishlist_items wishlist_id="' . $wishlist_id . '"]');
    wp_die();
}
add_action('wp_ajax_lef_get_wishlist_items', 'lef_get_wishlist_items');
add_action('wp_ajax_nopriv_lef_get_wishlist_items', 'lef_get_wishlist_items');

// Fetch user wishlists
function lef_get_user_wishlists() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    $query = $wpdb->prepare(
        "SELECT ID, post_title 
         FROM {$wpdb->posts} 
         WHERE post_type = 'lef_wishlist' 
         AND post_author = %d 
         AND post_status = 'publish' 
         AND post_title LIKE %s 
         ORDER BY post_title ASC",
        $user_id,
        '%' . $wpdb->esc_like($search) . '%'
    );

    $wishlists = $wpdb->get_results($query);
    
    if (!$wishlists) {
        wp_send_json([]);
    }

    $response = [];
    foreach ($wishlists as $wishlist) {
        $response[] = [
            'id' => $wishlist->ID,
            'title' => $wishlist->post_title
        ];
    }

    wp_send_json($response);
}
add_action('wp_ajax_lef_get_user_wishlists', 'lef_get_user_wishlists');
add_action('wp_ajax_nopriv_lef_get_user_wishlists', 'lef_get_user_wishlists');

function lef_add_wishlist_to_group() {
    global $wpdb;

    // Get the data from AJAX request
    $wishlist_id = isset($_POST['wishlist_id']) ? intval($_POST['wishlist_id']) : 0;
    $groepen_id = isset($_POST['groepen_id']) ? intval($_POST['groepen_id']) : 0;
    
    if (!$wishlist_id || !$groepen_id) {
        wp_send_json_error(["message" => "Invalid input data"]);
    }
    
    $table_name = $wpdb->prefix . 'lef_group_wishlists';

    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE wishlist_id = %d AND group_id = %d",
        $wishlist_id,
        $groepen_id
    ));

    if ($existing) {
        wp_send_json_error(array('message' => 'Wishlist is already in the Group'));
    }

    // Insert into the database
    $result = $wpdb->insert(
        $table_name,
        [
            "group_ID" => $groepen_id,
            "wishlist_ID" => $wishlist_id,
            "added_at" => current_time("mysql"),
            "accessible_by" => 0
        ],
        ["%d", "%d", "%s", "%d"]
    );

    if ($result) {
        wp_send_json_success(["message" => "Wishlist added successfully"]);

    } else {
        wp_send_json_error(["message" => "Database insert failed  wishlist: ". $wishlist_id ."  group:  " . $groepen_id]);
    }
}
add_action("wp_ajax_lef_add_wishlist_to_group", "lef_add_wishlist_to_group");
add_action("wp_ajax_nopriv_lef_add_wishlist_to_group", "lef_add_wishlist_to_group");

//delete item
function lef_delete_item() {
    if (!isset($_POST['delete_type'])) {
        wp_send_json_error(['message' => 'Invalid request data.']);
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $delete_type = sanitize_text_field($_POST['delete_type']);
    $item_id = intval($_POST['item_id']);

    switch ($delete_type) {
        case 'delete_group':
        case 'delete_wishlist':
            // Delete the WordPress post directly
            if (get_post_type($item_id) === ($delete_type === 'delete_group' ? 'lef_groepen' : 'lef_wishlist')) {
                wp_delete_post($item_id, true);
                wp_send_json_success([
                    'message' => ' deleted successfully.',
                    'redirect_url' => home_url('/') // Redirect to homepage
                ]);
            } else {
                wp_send_json_error(['message' => 'Invalid post type.']);
            }
            break;

        case 'delete_wishlist_item':
            if (!isset($_POST['wishlist_id']) || !isset($_POST['product_id'])) {
                wp_send_json_error(['message' => 'Missing wishlist or product ID.']);
            }

            $wishlist_id = intval($_POST['wishlist_id']);
            $product_id = intval($_POST['product_id']);

            $deleted = $wpdb->delete(
                "{$wpdb->prefix}lef_wishlist_items",
                ['wishlist_id' => $wishlist_id, 'product_id' => $product_id]
            );

            if ($deleted) {
                wp_send_json_success(['message' => 'Item removed from wishlist.']);
            } else {
                wp_send_json_error(['message' => 'Item not found or already deleted.']);
            }
            break;

        case 'remove_wishlist_from_group':
            if (!isset($_POST['group_id']) || !isset($_POST['wishlist_id'])) {
                wp_send_json_error(['message' => 'Missing group or wishlist ID.']);
            }

            $group_id = intval($_POST['group_id']);
            $wishlist_id = intval($_POST['wishlist_id']);

            $entry = $wpdb->get_row($wpdb->prepare("
                SELECT id FROM {$wpdb->prefix}lef_group_wishlists
                WHERE group_id = %d AND wishlist_id = %d
            ", $group_id, $wishlist_id));

            if ($entry) {
                $wpdb->delete("{$wpdb->prefix}lef_group_wishlists", ['id' => $entry->id]);
                wp_send_json_success(['message' => 'Wishlist removed from group.']);
            } else {
                wp_send_json_error(['message' => 'An error has accorred trying to remove this wishlist from the group.']);
            }
            break;

        case 'remove_user_from_group':
            if (!isset($_POST['group_id']) || !isset($_POST['user_id'])) {
                wp_send_json_error(['message' => 'Missing group or user ID.']);
            }

            $group_id = intval($_POST['group_id']);
            $target_user_id = intval($_POST['user_id']);

            // ownership check using lef_groups_users
            $is_owner = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}lef_groups_users
                WHERE group_id = %d AND user_id = %d AND is_owner = 1
            ", $group_id, $user_id));

            if ($is_owner) {
                $wpdb->delete("{$wpdb->prefix}lef_groups_users", [
                    'group_id' => $group_id,
                    'user_id'  => $target_user_id
                ]);
                wp_send_json_success(['message' => 'User removed from group.']);
            } else {
                wp_send_json_error(['message' => 'You are not allowed to remove users from this group.']);
            }
            break;

            case 'remove_invite_existing_user':
                if (!isset($_POST['group_id']) || !isset($_POST['user_id'])) {
                    wp_send_json_error(['message' => 'Missing group or user ID.']);
                }
            
                $group_id = intval($_POST['group_id']);
                $user_id = intval($_POST['user_id']);
                $current_user_id = get_current_user_id();
            
                // Verify that the current user is the owner
                $is_owner = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->prefix}lef_groups_users
                    WHERE group_id = %d AND user_id = %d AND is_owner = 1
                ", $group_id, $current_user_id));
            
                if (!$is_owner) {
                    wp_send_json_error(['message' => 'You do not have permission to remove this invite.']);
                }
            
                // Check if the user has an invite (but hasn’t joined yet)
                $invite_exists = $wpdb->get_var($wpdb->prepare("
                    SELECT id FROM {$wpdb->prefix}lef_groups_users 
                    WHERE group_id = %d AND user_id = %d
                ", $group_id, $user_id));
            
                if ($invite_exists) {
                    // Delete the invite from `lef_group_invites`
                    $wpdb->delete("{$wpdb->prefix}lef_groups_users", [
                        'group_id' => $group_id,
                        'user_id'  => $user_id
                    ]);
                    wp_send_json_success(['message' => 'Invite removed for existing user.']);
                } else {
                    wp_send_json_error(['message' => 'No pending invite found for this user.']);
                }
                break;
            
            case 'remove_invite_unknown_user':
                if (!isset($_POST['group_id']) || !isset($_POST['user_email'])) {
                    wp_send_json_error(['message' => 'Missing group or user email.']);
                }
            
                $group_id = intval($_POST['group_id']);
                $user_email = sanitize_email($_POST['user_email']);
                $current_user_id = get_current_user_id();
            
                // Verify that the current user is the owner
                $is_owner = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->prefix}lef_groups_users
                    WHERE group_id = %d AND user_id = %d AND is_owner = 1
                ", $group_id, $current_user_id));
            
                if (!$is_owner) {
                    wp_send_json_error(['message' => 'You do not have permission to remove this invite.']);
                }
            
                // Check if the invite exists for this email
                $invite_exists = $wpdb->get_var($wpdb->prepare("
                    SELECT id FROM {$wpdb->prefix}lef_group_invites 
                    WHERE group_id = %d AND email = %s
                ", $group_id, $user_email));
            
                if ($invite_exists) {
                    // Delete the invite from `lef_group_invites`
                    $wpdb->delete("{$wpdb->prefix}lef_group_invites", [
                        'group_id' => $group_id,
                        'email'    => $user_email
                    ]);
                    wp_send_json_success(['message' => 'Invite removed for unknown user.']);
                } else {
                    wp_send_json_error(['message' => 'No pending invite found for this email.']);
                }
                break;
            
        default:
            wp_send_json_error(['message' => 'Invalid delete type.']);
    }
}
add_action('wp_ajax_lef_delete_item', 'lef_delete_item');

function lef_handle_invite_action() {
    if (!is_user_logged_in() || !isset($_POST['group_id']) || !isset($_POST['action_type'])) {
        wp_send_json_error('Invalid request');
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $group_id = intval($_POST['group_id']);
    $action_type = sanitize_text_field($_POST['action_type']);

    if ($action_type === 'accept') {
        $wpdb->update("{$wpdb->prefix}lef_groups_users", ['has_joined' => 1], ['user_id' => $user_id, 'group_id' => $group_id]);
        wp_send_json_success('Invite accepted!');
    } elseif ($action_type === 'decline') {
        $wpdb->delete("{$wpdb->prefix}lef_groups_users", ['user_id' => $user_id, 'group_id' => $group_id]);
        wp_send_json_success('Invite declined.');
    }

    wp_send_json_error('Invalid action');
}
add_action('wp_ajax_lef_handle_invite_action', 'lef_handle_invite_action');

// Add this function before lef_send_invite():
function lef_send_styled_invite_email($to, $subject, $invite_link, $logo_path, $image_cid) {
    // Get theme colors
    $primary_color = get_theme_mod('lef_primary_color', '#1f8a4d');
    $text_color = get_theme_mod('lef_text_color', '#ffffff');
    $hover_color = get_theme_mod('lef_tertiary_color', "#1a713e");
    $site_title = get_bloginfo('name');
        
    $message = "
    <html>
    <head>
            <title>LEF Creative - Email Invite</title>
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
                <a href='$invite_link' class='button'>Click Here to join!</a>
                <br>
                <p>This invite will expire in 7 days.</p>
            </div>
            <div class='footer'>Thank you for using LEF Creative.</div>
        </div>
    </body>
    </html>";
    
    $headers = ['Content-Type: text/html; charset=UTF-8', 'From: LEF Creative <websites@lefcreative.nl>'];
    $attachments = array();
        
    if (file_exists($logo_path)) {
        $attachments[] = $logo_path;
        
        add_action('phpmailer_init', function($phpmailer) use ($logo_path, $image_cid) {
            $phpmailer->AddEmbeddedImage(
                $logo_path,
                $image_cid,
                basename($logo_path),
                'base64',
                mime_content_type($logo_path)
            );
        });
    }
        
    $sent = wp_mail($to, $subject, $message, $headers, $attachments);
    remove_all_actions('phpmailer_init');
        
    return $sent;
}

function lef_send_invite() {
    if (!isset($_POST['email']) || !isset($_POST['group_id'])) {
        wp_send_json_error("Invalid request. Missing email or group ID.");
    }

    global $wpdb;
    $email = sanitize_email($_POST['email']);
    $group_id = intval($_POST['group_id']); 

    if (!$group_id) {
        wp_send_json_error("Invalid group ID.");
    }

    // Check if the email belongs to an existing user
    $user = get_user_by('email', $email);
    $table_groups_users = $wpdb->prefix . "lef_groups_users";
    $table_invites = $wpdb->prefix . "lef_group_invites";

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
    
    if ($user) {
        $user_id = $user->ID;

        // Check if user is already in the group
        $existing_user = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_groups_users WHERE group_id = %d AND user_id = %d",
            $group_id, $user_id
        ));
        
        // checks if a user already exists
        if ($existing_user) {
            wp_send_json_error("This user is already a member of this group.");
        }

        // Generate an invite link for existing users
        $invite_link = site_url("/?join_group=$group_id&user_id=$user_id");
        
        // If not in the group, add user
        $wpdb->insert($table_groups_users, [
            'group_id'   => $group_id,
            'user_id'    => $user_id,
            'has_joined' => 0,
            'added_at'   => current_time('mysql')
        ]);
        
        $subject = "$site_title - You've been invited to a group!";
        $headers = ['Content-Type: text/html; charset=UTF-8'];
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
                    <a href='$invite_link' class='button'>Click Here to join!</a>
                    <br>
                    <p>This invite will expire in 7 days.</p>
                </div>
                <div class='footer'>Thank you for using LEF Creative.</div>
            </div>
        </body>
        </html>";
    
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
        
        // Send the email
        wp_mail($email, $subject, $message, $headers, $attachments);
        
        // Remove our temporary phpmailer_init hook to avoid affecting other emails
        remove_all_actions('phpmailer_init');

        // old email invite
        // $subject = "You've been invited to join a group on LEF Creative";
        // $message = "Hello,\n\nYou have been added to a group on LEF Creative.\n\nClick the link below to join:\n\n$invite_link\n\nBest regards,\nLEF Creative Team";
        // $headers = ['Content-Type: text/plain; charset=UTF-8'];
        // wp_mail($email, $subject, $message, $headers);

        wp_send_json_success(['message' => "User invited."]);

    } else {
        // New user logic (check invites first)
        $table_invites = $wpdb->prefix . "lef_group_invites";

        $existing_invite = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_invites WHERE group_id = %d AND email = %s",
            $group_id, $email
        ));

        if ($existing_invite) {
            wp_send_json_error("This email has already been invited to this group.");
        }

        // Generate and store invite
        $token = wp_generate_password(20, false);
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));

        $wpdb->insert($table_invites, [
            'group_id'       => $group_id,
            'email'          => $email,
            'invite_token'   => $token,
            'invite_expires' => $expires,
            'invited_at'     => current_time('mysql')
        ]);

        $invite_link = site_url("/register/?token=$token&email=$email");

        $subject = "$site_title - You've been invited to a group!";
        $headers = ['Content-Type: text/html; charset=UTF-8'];
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
                    <a href='$invite_link' class='button'>Click Here to join!</a>
                    <br>
                    <p>This invite will expire in 7 days.</p>
                </div>
                <div class='footer'>Thank you for using LEF Creative.</div>
            </div>
        </body>
        </html>";
    
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
        
        // Send the email
        wp_mail($email, $subject, $message, $headers, $attachments);
        
        // Remove our temporary phpmailer_init hook to avoid affecting other emails
        remove_all_actions('phpmailer_init');


        // old email invite
        // $subject = "Join LEF Creative – You've Been Invited!";
        // $message = "Hello,\n\nYou've been invited to join a group on LEF Creative.\n\nClick the link below to register and accept the invite:\n\n$invite_link\n\nThis invite will expire in 7 days.\n\nBest regards,\nLEF Creative Team";
        // $headers = ['Content-Type: text/plain; charset=UTF-8'];
        // wp_mail($email, $subject, $message, $headers);

        wp_send_json_success(['message' => "New user invited"]);
    }
}

add_action('wp_ajax_lef_send_invite', 'lef_send_invite');
add_action('wp_ajax_nopriv_lef_send_invite', 'lef_send_invite');
