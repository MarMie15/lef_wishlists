<?php
// Prevent direct access

if (!defined('ABSPATH')) {
    exit;
}

// Function to display the LEF Wishlists dashboard page
function lef_wishlist_dashboard_page() {
    ?>
    <div class="lef-dashboard-menu">
        <ul>
            <li><a href="?tab=wishlists">Wishlists</a></li>
            <li><a href="?tab=groups">Groups</a></li>
            <li><a href="?tab=invites">Invites</a></li>
        </ul>
    </div>

    <div class="lef-dashboard-content">
        <?php
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'wishlists';

        if ($tab == 'wishlists') {
            lef_display_wishlists();
        } elseif ($tab == 'groups') {
            lef_display_groups();
        } elseif ($tab == 'invites') {
            lef_display_invites();
        }
        ?>
    </div>
    <?php
}

// Function to display wishlists
function lef_display_wishlists() {
    echo '<h2>Your Wishlists</h2>';
    // Placeholder for wishlist content
}

// Function to display groups
function lef_display_groups() {
    echo '<h2>Your Groups</h2>';
    // Placeholder for group content
}

// Function to display invites
function lef_display_invites() {
    echo '<h2>Your Invites</h2>';
    // Placeholder for invite content
}


// Shortcode to render the dashboard
function lef_wishlist_dashboard_shortcode() {
    ob_start();
    lef_wishlist_dashboard_page();
    return ob_get_clean();
}
add_shortcode('lef_wishlist_dashboard', 'lef_wishlist_dashboard_shortcode');




//code ot add a "group invites" tab to the woocommmerce my account tab
// function lef_add_group_invites_tab($items) {
//     $items['group-invites'] = __('Group Invites', 'text-domain');
//     return $items;
// }
// add_filter('woocommerce_account_menu_items', 'lef_add_group_invites_tab');

// function lef_register_group_invites_endpoint() {
//     add_rewrite_endpoint('group-invites', EP_ROOT | EP_PAGES);
// }
// add_action('init', 'lef_register_group_invites_endpoint');

// function lef_group_invites_content() {
//     if (!is_user_logged_in()) {
//         echo '<p>You must be logged in to view your invites.</p>';
//         return;
//     }

//     global $wpdb;
//     $user_id = get_current_user_id();

//     // Fetch invites where has_joined = 0
//     $query = $wpdb->prepare(
//         "SELECT p.ID, p.post_title 
//          FROM {$wpdb->prefix}lef_groups_users gu
//          JOIN {$wpdb->posts} p ON gu.group_ID = p.ID
//          WHERE gu.user_ID = %d 
//          AND gu.has_joined = 0
//          AND p.post_type = 'lef_groepen'
//          AND p.post_status = 'publish'",
//         $user_id
//     );

//     $invites = $wpdb->get_results($query);

//     if (empty($invites)) {
//         echo '<p>No pending invites.</p>';
//         return;
//     }

//     echo "<p>you've been invited to:</p>";
//     echo '<ul>';
//     foreach ($invites as $invite) {
//         echo '<li>' . esc_html($invite->post_title) . '</li>'.
//              ' <a style="color: green;" href="#" class="lef-accept-invite" data-group-id=" '. esc_attr($invite->ID) .' ">[Accept]</a> 
//                <a style="color: red;"   href="#" class="lef-decline-invite" data-group-id=" '. esc_attr($invite->ID) .' ">[Decline]</a></li>';
//     }
//     echo '</ul>';
// }
// add_action('woocommerce_account_group-invites_endpoint', 'lef_group_invites_content');