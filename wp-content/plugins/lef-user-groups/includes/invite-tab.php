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
            <li><a href="?tab=wishlists" class="<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'wishlists') ? 'active' : ''; ?>">Wishlists</a></li>
            <li><a href="?tab=groups" class="<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'groups') ? 'active' : ''; ?>">Groups</a></li>
            <li>
                <a href="?tab=invites" class="<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'invites') ? 'active' : ''; ?>">
                    Invites 
                    <?php
                    if (!isset($_GET['tab']) || $_GET['tab'] !== 'invites') { // Only show badge if NOT on the invites tab
                        global $wpdb;
                        $user_id = get_current_user_id();
                        $invite_count = $wpdb->get_var($wpdb->prepare("
                            SELECT COUNT(*) FROM {$wpdb->prefix}lef_groups_users 
                            WHERE user_id = %d AND has_joined = 0
                        ", $user_id));

                        if ($invite_count > 0) {
                            echo '<span class="lef-invite-tab-badge">' . esc_html($invite_count) . '</span>';
                        }
                    }
                    ?>
                </a>
            </li>
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
    echo do_shortcode('[lef_create_wishlist_form]');
    echo do_shortcode('[lef_display_user_wishlists]');
}

// Function to display groups
function lef_display_groups() {
    echo '<h2>Your Groups</h2>';
    echo do_shortcode('[lef_display_user_groups]');
}

// Function to display invites in the LEF dashboard
function lef_display_invites() {
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

    echo "<h2>You've been invited to:</h2>";
    echo '<ul>';
    foreach ($invites as $invite) {
        echo '<li>' . esc_html($invite->post_title) . '<br>' .
             '<a style="color: green; cursor: pointer;" class="lef-accept-invite" data-group-id="' . esc_attr($invite->ID) . '">[Accept]</a> 
              <a style="color: red; cursor: pointer;" class="lef-decline-invite" data-group-id="' . esc_attr($invite->ID) . '">[Decline]</a></li>';
    }
    echo '</ul>';
}

// Shortcode to render the dashboard
function lef_wishlist_dashboard_shortcode() {
    ob_start();
    lef_wishlist_dashboard_page();
    return ob_get_clean();
}
add_shortcode('lef_wishlist_dashboard', 'lef_wishlist_dashboard_shortcode');