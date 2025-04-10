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

function lef_add_wishlist_to_group_handler() {
    // Verify nonce for security
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'lef-wishlist-nonce')) {
        error_log('Security check failed in lef_add_wishlist_to_group_handler');
        wp_send_json_error(['message' => 'Security check failed']);
    }
    
    global $wpdb;
    $current_user_id = get_current_user_id();
    
    // Check if required parameters are provided
    if (!isset($_POST['group_id']) || !isset($_POST['wishlist_id'])) {
        error_log('Missing parameters in lef_add_wishlist_to_group_handler. POST data: ' . print_r($_POST, true));
        wp_send_json_error(['message' => 'Missing group ID or wishlist ID']);
    }
    
    $group_id = intval($_POST['group_id']);
    $wishlist_id = intval($_POST['wishlist_id']);
    
    // Check if user is a member of the group
    $is_member = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}lef_groups_users WHERE group_ID = %d AND user_ID = %d AND has_joined = 1",
            $group_id,
            $current_user_id
        )
    );
    
    if (!$is_member) {
        error_log("User $current_user_id is not a member of group $group_id");
        wp_send_json_error(['message' => 'You are not a member of this group']);
    }
    
    // Check if user already has a wishlist in this group
    $existing_wishlist = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}lef_group_wishlists WHERE group_ID = %d AND wishlist_ID = %d",
            $group_id,
            $wishlist_id
        )
    );
    
    if ($existing_wishlist) {
        error_log("Wishlist $wishlist_id is already in group $group_id");
        wp_send_json_error(['message' => 'This wishlist is already in the group']);
    }
    
    // Add the wishlist to the group
    $result = $wpdb->insert(
        $wpdb->prefix . 'lef_group_wishlists',
        [
            'group_ID' => $group_id,
            'wishlist_ID' => $wishlist_id,
            'added_at' => current_time('mysql')
        ],
        ['%d', '%d', '%s']
    );
    
    if ($result === false) {
        error_log('Failed to add wishlist to group. Group ID: ' . $group_id . ', Wishlist ID: ' . $wishlist_id . '. DB Error: ' . $wpdb->last_error);
        wp_send_json_error(['message' => 'Failed to add wishlist. Please try again.']);
    }
    
    wp_send_json_success(['message' => 'Wishlist added to group successfully']);
}
add_action('wp_ajax_lef_add_wishlist_to_group', 'lef_add_wishlist_to_group_handler');
add_action('wp_ajax_nopriv_lef_add_wishlist_to_group', 'lef_add_wishlist_to_group_handler');

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
                ['wishlist_ID' => $wishlist_id, 'product_ID' => $product_id]
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
                WHERE group_ID = %d AND wishlist_ID = %d
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
                WHERE group_ID = %d AND user_ID = %d AND is_owner = 1
            ", $group_id, $user_id));

            if ($is_owner) {
                $wpdb->delete("{$wpdb->prefix}lef_groups_users", [
                    'group_ID' => $group_id,
                    'user_ID'  => $target_user_id
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
                WHERE group_ID = %d AND user_ID = %d AND is_owner = 1
                ", $group_id, $current_user_id));
            
            if (!$is_owner) {
                wp_send_json_error(['message' => 'You do not have permission to remove this invite.']);
            }
            
            // Check if the user has an invite (but hasn't joined yet)
            $invite_exists = $wpdb->get_var($wpdb->prepare("
                SELECT id FROM {$wpdb->prefix}lef_groups_users 
                WHERE group_ID = %d AND user_ID = %d
            ", $group_id, $user_id));
            
            if ($invite_exists) {
                // Delete the invite from `lef_group_invites`
                $wpdb->delete("{$wpdb->prefix}lef_groups_users", [
                    'group_ID' => $group_id,
                    'user_ID'  => $user_id
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
                WHERE group_ID = %d AND user_ID = %d AND is_owner = 1
            ", $group_id, $current_user_id));
        
            if (!$is_owner) {
                wp_send_json_error(['message' => 'You do not have permission to remove this invite.']);
            }
        
            // Check if the invite exists for this email
            $invite_exists = $wpdb->get_var($wpdb->prepare("
                SELECT id FROM {$wpdb->prefix}lef_group_invites 
                WHERE group_ID = %d AND email = %s
            ", $group_id, $user_email));
        
            if ($invite_exists) {
                // Delete the invite from `lef_group_invites`
                $wpdb->delete("{$wpdb->prefix}lef_group_invites", [
                    'group_ID' => $group_id,
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
        $wpdb->update("{$wpdb->prefix}lef_groups_users", ['has_joined' => 1], ['user_ID' => $user_id, 'group_ID' => $group_id]);
        wp_send_json_success('Invite accepted!');
    } elseif ($action_type === 'decline') {
        $wpdb->delete("{$wpdb->prefix}lef_groups_users", ['user_ID' => $user_id, 'group_ID' => $group_id]);
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
    
    $headers = ['Content-Type: text/html; charset=UTF-8'];
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
            "SELECT id FROM $table_groups_users WHERE group_ID = %d AND user_ID = %d",
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
            'group_ID'   => $group_id,
            'user_ID'    => $user_id,
            'has_joined' => 0,
            'added_at'   => current_time('mysql')
        ]);
        
        $subject = "$site_title - You've been invited to a group!";
        
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
        lef_send_styled_invite_email($email, $subject, $invite_link, $logo_path, $image_cid);
        
        // Remove temporary phpmailer_init hook to avoid affecting other emails
        remove_all_actions('phpmailer_init');

        wp_send_json_success(['message' => "User invited."]);

    } else {
        // New user logic (check invites first)
        $table_invites = $wpdb->prefix . "lef_group_invites";

        $existing_invite = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_invites WHERE group_ID = %d AND email = %s",
            $group_id, $email
        ));

        if ($existing_invite) {
            wp_send_json_error("This email has already been invited to this group.");
        }

        // Generate and store invite
        $token = wp_generate_password(20, false);
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));

        $wpdb->insert($table_invites, [
            'group_ID'       => $group_id,
            'email'          => $email,
            'invite_token'   => $token,
            'invite_expires' => $expires,
            'invited_at'     => current_time('mysql')
        ]);

        $invite_link = site_url("/register/?token=$token&email=$email");

        $subject = "$site_title - You've been invited to a group!";
    
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
        lef_send_styled_invite_email($email, $subject, $invite_link, $logo_path, $image_cid);
        
        // Remove our temporary phpmailer_init hook to avoid affecting other emails
        remove_all_actions('phpmailer_init');

        wp_send_json_success(['message' => "New user invited"]);
    }
}

add_action('wp_ajax_lef_send_invite', 'lef_send_invite');
add_action('wp_ajax_nopriv_lef_send_invite', 'lef_send_invite');

function lef_promote_to_owner_handler() {
    // Verify nonce for security
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'lef-owner-nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }
    
    global $wpdb;
    $current_user_id = get_current_user_id();
    
    // Check if required parameters are provided
    if (!isset($_POST['user_id']) || !isset($_POST['group_id'])) {
        wp_send_json_error(['message' => 'Missing required parameters']);
    }
    
    $user_id = intval($_POST['user_id']);
    $group_id = intval($_POST['group_id']);
    
    // Check if current user is an owner of the group
    $is_owner = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}lef_groups_users WHERE group_ID = %d AND user_ID = %d AND is_owner = 1",
            $group_id,
            $current_user_id
        )
    );
    
    if (!$is_owner) {
        wp_send_json_error(['message' => 'You do not have permission to modify owners']);
    }
    
    // Check if target user exists in the group
    $user_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}lef_groups_users WHERE group_ID = %d AND user_ID = %d AND has_joined = 1",
            $group_id,
            $user_id
        )
    );
    
    if (!$user_exists) {
        wp_send_json_error(['message' => 'User is not a member of this group']);
    }
    
    // Update the user to be an owner
    $result = $wpdb->update(
        $wpdb->prefix . 'lef_groups_users',
        ['is_owner' => 1],
        [
            'group_ID' => $group_id,
            'user_ID' => $user_id
        ],
        ['%d'],
        ['%d', '%d']
    );
    
    if ($result === false) {
        error_log('Failed to promote user to owner. Group ID: ' . $group_id . ', User ID: ' . $user_id);
        wp_send_json_error(['message' => 'Database error occurred']);
    } else {
        wp_send_json_success(['message' => 'User promoted to owner successfully']);
    }
}
add_action('wp_ajax_lef_promote_to_owner', 'lef_promote_to_owner_handler');

function lef_remove_from_group_handler() {
    // Verify nonce for security
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'lef-owner-nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }
    
    global $wpdb;
    $current_user_id = get_current_user_id();
    
    // Check if required parameters are provided
    if (!isset($_POST['user_id']) || !isset($_POST['group_id'])) {
        wp_send_json_error(['message' => 'Missing required parameters']);
    }
    
    $user_id = intval($_POST['user_id']);
    $group_id = intval($_POST['group_id']);
    
    // Check if current user is an owner of the group
    $is_owner = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}lef_groups_users WHERE group_ID = %d AND user_ID = %d AND is_owner = 1",
            $group_id,
            $current_user_id
        )
    );
    
    if (!$is_owner) {
        wp_send_json_error(['message' => 'You do not have permission to remove users']);
    }
    
    // Check if target user exists in the group
    $user_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}lef_groups_users WHERE group_ID = %d AND user_ID = %d AND has_joined = 1",
            $group_id,
            $user_id
        )
    );
    
    if (!$user_exists) {
        wp_send_json_error(['message' => 'User is not a member of this group']);
    }
    
    // Remove the user from the group
    $result = $wpdb->delete(
        $wpdb->prefix . 'lef_groups_users',
        [
            'group_ID' => $group_id,
            'user_ID' => $user_id
        ],
        ['%d', '%d']
    );
    
    if ($result === false) {
        error_log('Failed to remove user from group. Group ID: ' . $group_id . ', User ID: ' . $user_id);
        wp_send_json_error(['message' => 'Database error occurred']);
    } else {
        wp_send_json_success(['message' => 'User removed from group successfully']);
    }
}

function lef_get_group_wishlists_and_users() {
    if (!isset($_POST['group_id'])) {
        wp_send_json_error(['message' => 'Missing group ID']);
    }

    global $wpdb;
    $group_id = intval($_POST['group_id']);
    $current_user_id = get_current_user_id();

    // Check if user is an owner of the group
    $is_owner = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}lef_groups_users 
         WHERE group_ID = %d AND user_ID = %d AND is_owner = 1",
        $group_id,
        $current_user_id
    ));

    if (!$is_owner) {
        wp_send_json_error(['message' => 'You are not authorized to perform this action']);
    }

    // Get all wishlists in the group with their owners
    $wishlists = $wpdb->get_results($wpdb->prepare("
        SELECT gw.wishlist_ID as id, p.post_title as title, p.post_author as owner_id, u.display_name as owner_name
        FROM {$wpdb->prefix}lef_group_wishlists gw
        JOIN {$wpdb->posts} p ON gw.wishlist_ID = p.ID
        JOIN {$wpdb->users} u ON p.post_author = u.ID
        WHERE gw.group_ID = %d
    ", $group_id));

    // Get all users who have joined the group
    $users = $wpdb->get_results($wpdb->prepare("
        SELECT u.ID as id, u.display_name
        FROM {$wpdb->prefix}lef_groups_users gu
        JOIN {$wpdb->users} u ON gu.user_ID = u.ID
        WHERE gu.group_ID = %d AND gu.has_joined = 1
    ", $group_id));

    wp_send_json_success([
        'wishlists' => $wishlists,
        'users' => $users
    ]);
}
add_action('wp_ajax_lef_get_group_wishlists_and_users', 'lef_get_group_wishlists_and_users');
add_action('wp_ajax_nopriv_lef_get_group_wishlists_and_users', 'lef_get_group_wishlists_and_users');

function lef_save_wishlist_assignments() {
    if (!isset($_POST['group_id']) || !isset($_POST['assignments'])) {
        wp_send_json_error(['message' => 'Missing required data']);
    }

    global $wpdb;
    $group_id = intval($_POST['group_id']);
    $current_user_id = get_current_user_id();
    $assignments = $_POST['assignments'];

    // Check if user is an owner of the group
    $is_owner = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}lef_groups_users 
         WHERE group_ID = %d AND user_ID = %d AND is_owner = 1",
        $group_id,
        $current_user_id
    ));

    if (!$is_owner) {
        wp_send_json_error(['message' => 'You are not authorized to perform this action']);
    }

    // Start transaction
    $wpdb->query('START TRANSACTION');

    try {
        // First, clear any existing assignments for this group
        $wpdb->update(
            $wpdb->prefix . 'lef_group_wishlists',
            ['accessible_by' => null],
            ['group_ID' => $group_id],
            ['%s'],
            ['%d']
        );

        // Insert new assignments
        foreach ($assignments as $wishlist_id => $assignment) {
            $wpdb->update(
                $wpdb->prefix . 'lef_group_wishlists',
                ['accessible_by' => $assignment['assigned_to_id']],
                [
                    'group_ID' => $group_id,
                    'wishlist_ID' => $wishlist_id
                ],
                ['%d'],
                ['%d', '%d']
            );
        }

        $wpdb->query('COMMIT');
        wp_send_json_success(['message' => 'Assignments saved successfully']);
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(['message' => 'Failed to save assignments: ' . $e->getMessage()]);
    }
}
add_action('wp_ajax_lef_save_wishlist_assignments', 'lef_save_wishlist_assignments');
add_action('wp_ajax_nopriv_lef_save_wishlist_assignments', 'lef_save_wishlist_assignments');