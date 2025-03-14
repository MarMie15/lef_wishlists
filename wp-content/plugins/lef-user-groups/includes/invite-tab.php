<?php
// Prevent direct access
if (!defined('ABSPATH')) exit;

function lef_add_group_invites_tab($items) {
    $items['group-invites'] = __('Group Invites', 'text-domain');
    return $items;
}
add_filter('woocommerce_account_menu_items', 'lef_add_group_invites_tab');

function lef_register_group_invites_endpoint() {
    add_rewrite_endpoint('group-invites', EP_ROOT | EP_PAGES);
}
add_action('init', 'lef_register_group_invites_endpoint');

function lef_group_invites_content() {
    if (!is_user_logged_in()) {
        echo '<p>You must be logged in to view your invites.</p>';
        return;
    }

    global $wpdb;
    $user_id = get_current_user_id();

    // Fetch invites where has_joined = 0
    $query = $wpdb->prepare(
        "SELECT p.ID, p.post_title 
         FROM {$wpdb->prefix}lef_groups_users gu
         JOIN {$wpdb->posts} p ON gu.group_ID = p.ID
         WHERE gu.user_ID = %d 
         AND gu.has_joined = 0
         AND p.post_type = 'lef_groepen'
         AND p.post_status = 'publish'",
        $user_id
    );

    $invites = $wpdb->get_results($query);

    if (empty($invites)) {
        echo '<p>No pending invites.</p>';
        return;
    }

    echo "<p>you've been invited to:</p>";
    echo '<ul>';
    foreach ($invites as $invite) {
        echo '<li>' . esc_html($invite->post_title) . '</li>'.
             ' <a style="color: green;" href="#" class="lef-accept-invite" data-group-id=" '. esc_attr($invite->ID) .' ">[Accept]</a> 
               <a style="color: red;"   href="#" class="lef-decline-invite" data-group-id=" '. esc_attr($invite->ID) .' ">[Decline]</a></li>';
    }
    echo '</ul>';
}
add_action('woocommerce_account_group-invites_endpoint', 'lef_group_invites_content');
