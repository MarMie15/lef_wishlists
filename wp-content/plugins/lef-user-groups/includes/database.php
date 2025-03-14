<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Creates the tables in the database needed for the groups, group_members, wishlist, and wishlist_items
function create_custom_plugin_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $lef_groups_users_table = $wpdb->prefix . 'lef_groups_users';
    $lef_group_wishlists_table = $wpdb->prefix . 'lef_group_wishlists';
    $lef_wishlist_items_table = $wpdb->prefix . 'lef_wishlist_items';
    $lef_item_inventory_table = $wpdb->prefix . 'lef_item_inventory';
    
    $sql = [];

    $sql[] = "CREATE TABLE IF NOT EXISTS $lef_groups_users_table (
        ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        group_ID BIGINT(20) UNSIGNED NOT NULL,
        user_ID BIGINT(20) UNSIGNED NOT NULL,
        is_owner TINYINT(1) NOT NULL DEFAULT 0,
        has_joined TINYINT(1) NOT NULL DEFAULT 0,
        added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (ID),
        FOREIGN KEY (group_ID) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
        FOREIGN KEY (user_ID) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
    )$charset_collate;";

    $sql[] = "CREATE TABLE IF NOT EXISTS $lef_group_wishlists_table (
        ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        group_ID BIGINT(20) UNSIGNED NOT NULL,
        wishlist_ID BIGINT(20) UNSIGNED NOT NULL,
        added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        accessible_by INT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (ID),
        FOREIGN KEY (group_ID) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
        FOREIGN KEY (wishlist_ID) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE
    )$charset_collate;";

    $sql[] = "CREATE TABLE IF NOT EXISTS $lef_wishlist_items_table (
        ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        wishlist_ID BIGINT(20) UNSIGNED NOT NULL,
        product_ID BIGINT(20) UNSIGNED NOT NULL,
        PRIMARY KEY (ID),
        FOREIGN KEY (wishlist_ID) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
        FOREIGN KEY (product_ID) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE
    ) $charset_collate;";

    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lef_group_invites (
        ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        group_ID BIGINT(20) UNSIGNED NOT NULL,
        email VARCHAR(255) NOT NULL,
        invite_token VARCHAR(64) NOT NULL,
        invite_expires DATETIME DEFAULT NULL,
        invited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (ID),
        UNIQUE KEY unique_invite (group_ID, email),
        FOREIGN KEY (group_ID) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE
    )$charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    foreach ($sql as $query) {
        dbDelta($query);
    }
}
