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
