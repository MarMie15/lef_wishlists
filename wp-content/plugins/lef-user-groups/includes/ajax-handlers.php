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
        $products[] = [
            'id'   => get_the_ID(),
            'name' => get_the_title(),
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

            // Corrected ownership check using lef_groups_users
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
