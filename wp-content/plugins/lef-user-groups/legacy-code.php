<?php


//   // Insert into 'wp_lef_wishlists' table
//   $wpdb->insert(
//       "{$wpdb->prefix}lef_wishlists",
//       [
//           'user_id' => $user_ID,
//           'group_id' => 0, // Assuming it's not tied to a group for now
//           'wishlist_name' => $wishlist_name,
//           'created_at' => current_time('mysql')
//       ],
//       ['%d', '%d', '%s', '%s']
//   );



//old custom post type
// function register_groepen_post_type() {
//     $args = array(
//         'label'               => __('Groepen', 'textdomain'),
//         'public'              => true,
//         'show_ui'             => true,
//         'show_in_menu'        => true,
//         'supports'            => array('title', 'editor'),
//         'has_archive'         => true,
//         'capability_type'     => 'post',
//     );
//     register_post_type('groepen', $args);
// }
// add_action('init', 'register_groepen_post_type');


// OLD tables in the database needed for the groups
// table names: lef_group, lef_groups_users, lef_wishlists
// function create_user_groups_table() {
//     global $wpdb;
//     $table_name = $wpdb->prefix . 'lef_groups';
//     $charset_collate = $wpdb->get_charset_collate();

//     $sql = "CREATE TABLE $table_name (
//         id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
//         group_name VARCHAR(255) NOT NULL,
//         created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
//         PRIMARY KEY (id)
//     ) $charset_collate;";

//     require_once ABSPATH . 'wp-admin/includes/upgrade.php';
//     dbDelta($sql);

//     // Create user-group relations table
//     $user_groups_users_table = $wpdb->prefix . 'lef_groups_users';
//     $sql_users = "CREATE TABLE $user_groups_users_table (
//         id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
//         group_id BIGINT(20) UNSIGNED NOT NULL,
//         user_id BIGINT(20) UNSIGNED NOT NULL,
//         PRIMARY KEY (id),
//         FOREIGN KEY (group_id) REFERENCES $table_name(id) ON DELETE CASCADE,
//         FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
//     ) $charset_collate;";
//     dbDelta($sql_users);

//     // Create wishlists table
//     $wishlists_table = $wpdb->prefix . 'lef_wishlists';
//     $sql_wishlists = "CREATE TABLE $wishlists_table (
//         id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
//         user_id BIGINT(20) UNSIGNED NOT NULL,
//         group_id BIGINT(20) UNSIGNED NOT NULL,
//         wishlist_name VARCHAR(255) NOT NULL,
//         created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
//         PRIMARY KEY (id),
//         FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE,
//         FOREIGN KEY (group_id) REFERENCES $table_name(id) ON DELETE CASCADE
//     ) $charset_collate;";
//     dbDelta($sql_wishlists);
// }

// register_activation_hook(__FILE__, 'create_user_groups_table');
// end of talbe creation

//shortcode that can probably all get trashed but might have some usefull snippets until then
//shortcode for a user to edit a wishlist
// function lef_edit_wishlist_form() {
//     if (!is_user_logged_in()) {
//         return '<p>Je moet ingelogd zijn om een wishlist te bewerken.</p>';
//     }

//     global $wpdb;
//     $user_id = get_current_user_id();

//     $wishlists = $wpdb->get_results($wpdb->prepare(
//         "SELECT ID, wishlist_name FROM {$wpdb->prefix}wishlists WHERE user_id = %d",
//         $user_id
//     ));

//     if (!$wishlists) {
//         return '<p>Je hebt nog geen wishlists.</p>';
//     }

//     ob_start();
//     ?>
     <!-- <form method="post">
         <label for="wishlist_id">Selecteer Wishlist:</label><br>
         <select name="wishlist_id" required>
             <?php foreach ($wishlists as $wishlist) : ?>
                 <option value="<?php echo esc_attr($wishlist->ID); ?>">
                     <?php echo esc_html($wishlist->wishlist_name); ?>
                 </option>
             <?php endforeach; ?>
         </select><br><br>

         <label for="item_name">Item Naam:</label><br>
         <input type="text" name="item_name" required><br><br>

         <label for="item_link">Item Link:</label><br>
         <input type="url" name="item_link" required><br><br>

         <label for="item_image_url">Afbeelding URL (optioneel):</label><br>
         <input type="url" name="item_image_url"><br><br>

         <label for="item_price">Prijs (optioneel):</label><br>
         <input type="number" step="0.01" name="item_price"><br><br>

         <label for="item_description">Beschrijving (optioneel):</label><br>
         <textarea name="item_description"></textarea><br><br>

         <input type="hidden" name="lef_wishlist_edit_nonce" value="<?php echo wp_create_nonce('lef_wishlist_edit_action'); ?>">
         <button type="submit" name="lef_submit_wishlist_item">Voeg item toe</button>
     </form> -->
     <?php
//     return ob_get_clean();
// }
// add_shortcode('lef_edit_wishlist', 'lef_edit_wishlist_form');

// function lef_handle_wishlist_edit() {
//     if (isset($_POST['lef_submit_wishlist_item'])) {
//         if (!isset($_POST['lef_wishlist_edit_nonce']) || !wp_verify_nonce($_POST['lef_wishlist_edit_nonce'], 'lef_wishlist_edit_action')) {
//             die('Beveiligingsfout.');
//         }
//         if (!is_user_logged_in()) {
//             die('Je moet ingelogd zijn om items toe te voegen.');
//         }

//         global $wpdb;
//         $wishlist_id = intval($_POST['wishlist_id']);
//         $item_name = sanitize_text_field($_POST['item_name']);
//         $item_link = esc_url($_POST['item_link']);
//         $item_image_url = !empty($_POST['item_image_url']) ? esc_url($_POST['item_image_url']) : NULL;
//         $item_price = !empty($_POST['item_price']) ? floatval($_POST['item_price']) : NULL;
//         $item_description = !empty($_POST['item_description']) ? sanitize_textarea_field($_POST['item_description']) : NULL;

//         $wpdb->insert(
//             "{$wpdb->prefix}wishlist_items",
//             [
//                 'wishlist_id' => $wishlist_id,
//                 'item_name' => $item_name,
//                 'item_link' => $item_link,
//                 'item_image_url' => $item_image_url,
//                 'item_price' => $item_price,
//                 'item_description' => $item_description
//             ],
//             ['%d', '%s', '%s', '%s', '%f', '%s']
//         );

//         wp_redirect(home_url()); // Change to wishlist page if needed
//         exit;
//     }
// }
// add_action('init', 'lef_handle_wishlist_edit');

// //metaboxes have to updated for the new database
// // makes the meta boxes in the admin panels for the custom group posts
// function groepen_register_meta_boxes() {
//     add_meta_box('groep_users_list', 'Users in Group', 'groepen_users_list_callback', 'groepen', 'normal', 'high');
//     add_meta_box('groep_add_user', 'Add User to Group', 'groepen_add_user_callback', 'groepen', 'normal', 'high');
// }
// add_action('add_meta_boxes', 'groepen_register_meta_boxes');

// /** .............
//  * metabox to show the users in a group
//  * Display the list of users in the group with a hoverable "Remove" button.
//  * Clicking the "X" button opens a confirmation modal to remove the user from the group.
//  */
// function groepen_users_list_callback($post) {
//     global $wpdb;

//     $group_id = $post->ID;

//     // Fetch users from the custom lef_groups_users table
//     $users = $wpdb->get_results($wpdb->prepare(
//         "SELECT u.ID, u.display_name, u.user_email 
//          FROM {$wpdb->users} u
//          INNER JOIN {$wpdb->prefix}lef_groups_users gu ON u.ID = gu.user_id
//          WHERE gu.group_id = %d", 
//          $group_id
//     ));

//     echo '<h3>Users in this Group</h3>';
//     if (!empty($users)) {
//         echo '<ul id="group-user-list" style="list-style-type: none; padding: 0;">';
//         foreach ($users as $user) {
//             echo '<li style="position: relative; padding: 5px; border-bottom: 1px solid #ddd;">
//                     <span>' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</span>
//                     <button class="remove-user-btn" data-user-id="' . esc_attr($user->ID) . 
//                     '" data-user-name="' . esc_attr($user->display_name) . 
//                     '" data-user-email="' . esc_attr($user->user_email) . 
//                     '" data-group-id="' . esc_attr($group_id) . 
//                     '" style="position: absolute; right: 10px; top: 5px; background: red; color: white; border: none; cursor: pointer; padding: 2px 6px; font-size: 14px; display: none;">X</button>
//                   </li>';
//         }
//         echo '</ul>';
//     } else {
//         echo '<p>No users in this group yet.</p>';
//     }
//     ?>
//     <script type="text/javascript">
//         jQuery(document).ready(function($) {
//             $('#group-user-list li').hover(
//                 function() { $(this).find('.remove-user-btn').show(); },
//                 function() { $(this).find('.remove-user-btn').hide(); }
//             );

//             $('.remove-user-btn').click(function() {
//                 let userId = $(this).data('user-id');
//                 let groupId = $(this).data('group-id');
//                 let userName = $(this).data('user-name');

//                 if (confirm(`Remove ${userName} from this group?`)) {
//                     $.ajax({
//                         url: ajaxurl,
//                         type: 'POST',
//                         data: {
//                             action: 'remove_user_from_group',
//                             user_id: userId,
//                             group_id: groupId,
//                             security: '<?php echo wp_create_nonce("remove_user_nonce"); ?>'
//                         },
//                         success: function(response) {
//                             if (response.success) {
//                                 $('button[data-user-id="'+userId+'"]').closest('li').fadeOut(300, function() { $(this).remove(); });
//                             } else {
//                                 alert('Error: ' + response.data);
//                             }
//                         }
//                     });
//                 }
//             });
//         });
//     </script>
//     <?php
// }

// /**
//  * Handle the AJAX request to remove a user from a group for function groepen_users_list_callback.
//  * Validates the request, removes the user from the group, and returns a response.
//  */
// function remove_user_from_group() {
//     global $wpdb;

//     if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'remove_user_nonce')) {
//         wp_send_json_error('Invalid security token.');
//     }

//     if (!isset($_POST['user_id']) || !isset($_POST['group_id'])) {
//         wp_send_json_error('Missing user or group ID.');
//     }

//     $user_id = intval($_POST['user_id']);
//     $group_id = intval($_POST['group_id']);

//     if (!current_user_can('edit_post', $group_id)) {
//         wp_send_json_error('You do not have permission to remove users.');
//     }

//     // Check if the user is an owner
//     $is_owner = $wpdb->get_var($wpdb->prepare(
//         "SELECT is_owner FROM {$wpdb->prefix}lef_groups_users WHERE group_id = %d AND user_id = %d",
//         $group_id, $user_id
//     ));

//     // Remove the user from the group
//     $deleted = $wpdb->delete(
//         "{$wpdb->prefix}lef_groups_users",
//         ['group_id' => $group_id, 'user_id' => $user_id],
//         ['%d', '%d']
//     );

//     if ($deleted) {
//         wp_send_json_success(['is_owner' => $is_owner]);
//     } else {
//         wp_send_json_error('Failed to remove user.');
//     }
// }
// add_action('wp_ajax_remove_user_from_group', 'remove_user_from_group');

// // Metabox to add a user to a group
// function groepen_add_user_callback($post) {
//     ?>
     <!-- <h3>Select a User to Add</h3>
     <input type="text" id="user_search" placeholder="Search for a user by name or email..." style="width:100%; padding:5px;">
     <input type="hidden" id="selected_user_id" name="new_user_id">
    
     <div id="user_results" style="width:100%; max-height:150px; overflow-y:auto; border:1px solid #ccc; display:none; position:absolute; background:white; z-index:1000;"></div>

     <br><br>
     <form method="POST">
         <?php wp_nonce_field('add_user_to_group_nonce', 'add_user_to_group_nonce'); ?>
         <input type="hidden" name="group_id" value="<?php echo esc_attr($post->ID); ?>">
         <input type="submit" name="add_user_to_group" value="Add User" id="add_user_button" disabled>
     </form> -->

     <script type="text/javascript">
//         jQuery(document).ready(function($) {
//             $('#user_search').on('input', function() {
//                 var search = $(this).val();
//                 if (search.length < 2) {
//                     $('#user_results').hide();
//                     return;
//                 }

//                 $.ajax({
//                     url: ajaxurl,
//                     type: 'POST',
//                     data: {
//                         action: 'search_users',
//                         search_term: search
//                     },
//                     success: function(response) {
//                         if (response.trim() !== '') {
//                             $('#user_results').html(response).show();
//                         } else {
//                             $('#user_results').hide();
//                         }
//                     }
//                 });
//             });

//             // Handle user selection
//             $(document).on('click', '.user-result-item', function() {
//                 var user_id = $(this).data('id');
//                 var user_name = $(this).text();
                
//                 $('#user_search').val(user_name);
//                 $('#selected_user_id').val(user_id);
//                 $('#user_results').hide();
//                 $('#add_user_button').prop('disabled', false); // Enable add button
//             });

//             // Hide results when clicking outside
//             $(document).click(function(e) {
//                 if (!$(e.target).closest('#user_search, #user_results').length) {
//                     $('#user_results').hide();
//                 }
//             });
//         });
//     </script>
    
//     <style>
//         .user-result-item {
//             padding: 5px;
//             cursor: pointer;
//         }
//         .user-result-item:hover {
//             background-color: #f0f0f0;
//         }
//     </style>
//     <?php
// }

// // Function to process adding a user to a group
// function process_add_user_to_group() {
//     if (isset($_POST['add_user_to_group']) && isset($_POST['new_user_id']) && isset($_POST['group_id'])) {
//         if (!isset($_POST['add_user_to_group_nonce']) || !wp_verify_nonce($_POST['add_user_to_group_nonce'], 'add_user_to_group_nonce')) {
//             return;
//         }

//         global $wpdb;
//         $table_groups_users = $wpdb->prefix . 'lef_groups_users';

//         $group_id = intval($_POST['group_id']);
//         $user_id = intval($_POST['new_user_id']);

//         // Verify the user has permission to edit this group
//         if (!current_user_can('edit_post', $group_id)) {
//             return;
//         }

//         // Check if the user is already in the group
//         $exists = $wpdb->get_var($wpdb->prepare(
//             "SELECT COUNT(*) FROM $table_groups_users WHERE group_id = %d AND user_id = %d",
//             $group_id,
//             $user_id
//         ));

//         if ($exists) {
//             wp_redirect(add_query_arg('error', 'user_already_in_group', get_edit_post_link($group_id, 'redirect')));
//             exit;
//         }

//         // Add user to the group with is_owner = 0
//         $insert = $wpdb->insert(
//             $table_groups_users,
//             [
//                 'group_id' => $group_id,
//                 'user_id'  => $user_id,
//                 'is_owner' => 0,
//             ],
//             ['%d', '%d', '%d']
//         );

//         if ($insert) {
//             wp_redirect(add_query_arg('updated', 'true', get_edit_post_link($group_id, 'redirect')));
//         } else {
//             wp_redirect(add_query_arg('error', 'insert_failed', get_edit_post_link($group_id, 'redirect')));
//         }
//         exit;
//     }
// }
// add_action('admin_init', 'process_add_user_to_group');

// //function to allow the add user field to filter users based on input using ajax handler
// function search_users_ajax() {
//     if (!isset($_POST['search_term'])) {
//         wp_die();
//     }

//     $search_term = sanitize_text_field($_POST['search_term']);
//     $users = get_users([
//         'search'         => '*' . esc_attr($search_term) . '*',
//         'search_columns' => ['user_login', 'user_email', 'display_name'],
//         'number'         => 10, // Limit results for performance
//     ]);

//     if ($users) {
//         foreach ($users as $user) {
//             echo '<div class="user-result-item" data-id="' . esc_attr($user->ID) . '">' . 
//                  esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')' . 
//                  '</div>';
//         }
//     } else {
//         echo '<div class="user-result-item">No users found</div>';
//     }

//     wp_die();
// }
// add_action('wp_ajax_search_users', 'search_users_ajax');

// /**
// *adds the user that created a post into that group
//  */
// function add_creator_to_group($post_id, $post, $update) {
//     if ($update || $post->post_type !== 'groepen') {
//         return;
//     }

//     global $wpdb;
//     $user_id = get_current_user_id();
//     if (!$user_id) {
//         error_log("No user ID found when creating group post ID: $post_id");
//         return;
//     }

//     $table = $wpdb->prefix . 'lef_groups_users';

//     // Insert user into group as owner
//     $result = $wpdb->insert($table, [
//         'group_id' => $post_id,
//         'user_id' => $user_id,
//         'is_owner' => 1
//     ], ['%d', '%d', '%d']);

//     if ($result === false) {
//         error_log("Database insert failed for group_id: $post_id, user_id: $user_id. Error: " . $wpdb->last_error);
//     } else {
//         error_log("Successfully added user_id: $user_id to group_id: $post_id");
//     }
// }

// add_action('wp_insert_post', 'add_creator_to_group', 10, 3);

// // shortcode that shows the current users groups
// function show_user_groups_shortcode() {
//     if (!is_user_logged_in()) {
//         return '<p>You must be logged in to see your groups.</p>';
//     }

//     global $wpdb;
//     $user_id = get_current_user_id();
//     $output = '';

//     // Define table names
//     $groups_users_table = $wpdb->prefix . 'lef_groups_users'; // User-Group relation table
//     $groups_table = $wpdb->prefix . 'posts'; // wp_posts table where groups are stored (custom post type 'groepen')

//     // Get all groups the user is in
//     $groups = $wpdb->get_results($wpdb->prepare("
//         SELECT p.ID, p.post_title
//         FROM $groups_table p
//         JOIN $groups_users_table ug ON p.ID = ug.group_id
//         WHERE ug.user_id = %d
//         AND p.post_type = 'groepen'
//         AND p.post_status = 'publish'
//         ", $user_id));

//     if (!empty($groups)) {
//         $output .= '<ul>';
//         foreach ($groups as $group) {
//             // Make each group name a link to a page showing the group's wishlists
//             $group_url = esc_url(home_url("/index.php/group-details/?group_id=" . $group->ID));
//             $output .= '<li><a href="' . $group_url . '">' . esc_html($group->post_title) . '</a></li>';
//         }
//         $output .= '</ul>';
//     } else {
//         $output .= '<p>You are not in any groups.</p>';
//     }

//     return $output;
// }
// add_shortcode('show_user_groups', 'show_user_groups_shortcode');

// // shortcode to allow a user to make a group
// function create_user_group_shortcode() {
//     if (!is_user_logged_in()) {
//         return '<p>You must be logged in to create a group.</p>';
//     }    

//     global $wpdb;
//     $user_id = get_current_user_id();
//     $output = '';

//     if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["group_name"])) {
//         $group_name = sanitize_text_field($_POST["group_name"]);

//         if (!empty($group_name)) {
//             $table_groups = $wpdb->prefix . 'LEF_groups';
//             $table_users = $wpdb->prefix . 'LEF_groups_users';

//             // Insert the new group into the database
//             $result = $wpdb->insert($table_groups, [
//                 'group_name' => $group_name,
//                 'created_at' => current_time('mysql'),
//             ]);    

//             if ($result === false) {
//                 $output .= '<p style="color: red;">Error creating group: ' . esc_html($wpdb->last_error) . '</p>';
//             } else {
//                 // Get the ID of the newly created group
//                 $group_id = $wpdb->insert_id;

//                 // Link the user to the new group
//                 $user_insert = $wpdb->insert($table_users, [
//                     'group_id' => $group_id,
//                     'user_id' => $user_id,
//                 ]);    

//                 if ($user_insert === false) {
//                     $output .= '<p style="color: red;">Error linking user: ' . esc_html($wpdb->last_error) . '</p>';
//                 } else {
//                     $output .= '<p style="color: green;">Group "' . esc_html($group_name) . '" created successfully!</p>';
//                 }    
//             }    
//         } else {
//             $output .= '<p style="color: red;">Please enter a group name.</p>';
//         }    
//     }    

//     // Display the form
//     $output .= '
//         <form method="post">
//             <label for="group_name">Create a new group here:</label><br>
//             <input type="text" id="group_name" name="group_name" required placeholder="group name">
//             <button type="submit">Create Group</button>
//         </form>    
//     ';    

//     return $output;
// }
// add_shortcode('create_user_group', 'create_user_group_shortcode');

// // start of the group details shortcode which should show all the information for this group that the database has
// function group_details_page() {
//     if (!isset($_GET['group_id']) || !is_numeric($_GET['group_id'])) {
//         return '<p>Invalid group ID.</p>';
//     }

//     $group_id = intval($_GET['group_id']);
//     global $wpdb;
//     $output = '';

//     $table_groups = $wpdb->prefix . 'posts';
//     $table_wishlists = $wpdb->prefix . 'LEF_wishlists';
//     $table_groups_users = $wpdb->prefix . 'LEF_groups_users';  // Correct table reference
//     $users_table = $wpdb->prefix . 'users';  // WordPress default users table

//     // Get group details
//     $group = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_groups WHERE id = %d", $group_id));

//     if ($group) {
//         $output .= '<h2>Group: ' . esc_html($group->post_content) . '</h2>';

//         // Get all users in this group
//         $users = $wpdb->get_results($wpdb->prepare("
//             SELECT u.ID, u.user_login, u.user_email
//             FROM $users_table u
//             JOIN $table_groups_users gu ON u.ID = gu.user_id
//             WHERE gu.group_id = %d
//         ", $group_id));

//         if ($users) {
//             $output .= '<h3>Users in this group:</h3>';
//             $output .= '<ul>';
//             foreach ($users as $user) {
//                 // Display user details
//                 $output .= '<li>';
//                 $output .= '<strong>' . esc_html($user->user_login) . '</strong><br>';
//                 $output .= '</li>';
//             }
//             $output .= '</ul>';
//         } else {
//             $output .= '<p>No users found in this group.</p>';
//         }
//         // Get all wishlists associated with this group
//         $wishlists = $wpdb->get_results($wpdb->prepare("
//             SELECT w.id, w.wishlist_name
//             FROM $table_wishlists w
//             JOIN $table_groups_users gu ON w.user_id = gu.user_id
//             WHERE gu.group_id = %d
//         ", $group_id));

//         if ($wishlists) {
//             $output .= '<ul>';
//             foreach ($wishlists as $wishlist) {
//                 $output .= '<li>' . esc_html($wishlist->wishlist_name) . '</li>';
//             }
//             $output .= '</ul>';
//         } else {
//             $output .= '<p>No wishlists found for this group.</p>';
//         }
//     } else {
//         $output .= '<p>Group not found.</p>';
//         $output .= '<p>' . $group . '</p>';
//     }

//     return $output;
// }
// add_shortcode('group_details', 'group_details_page');

// //start of the shortcode that should allow a user to add another user to the group
// function add_user_by_email_shortcode() {
//     // Only show the form to logged-in users
//     if (!is_user_logged_in()) {
//         return '<p>You must be logged in to add a user.</p>';
//     }

//     // Handle form submission
//     if (isset($_POST['add_user_email']) && isset($_POST['group_id'])) {
//         $email = sanitize_email($_POST['add_user_email']);
//         $user = get_user_by('email', $email);
//         $group_id = intval($_POST['group_id']);

//         if ($user) {
//             $current_user = wp_get_current_user();
//             // Add the user to the selected group
//             global $wpdb;
//             $table_groups_users = $wpdb->prefix . 'LEF_groups_users';

//             // Check if the group_id exists (to prevent invalid group association)
//             $group_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}LEF_groups WHERE id = %d", $group_id));

//             if ($group_exists) {
//                 // Check if the user is already in the group
//                 $user_in_group = $wpdb->get_var($wpdb->prepare("
//                 SELECT COUNT(*) 
//                 FROM {$table_groups_users}
//                 WHERE user_id = %d AND group_id = %d
//                 ", $user->ID, $group_id));

//                 if ($user_in_group > 0) {
//                     return '<p>This user is already in the selected group.</p>';
//                 }

//                 // Insert the new user into the group in the relationship table
//                 $wpdb->insert(
//                     $table_groups_users,
//                     array(
//                         'group_id' => $group_id,
//                         'user_id'  => $user->ID,
//                     ),
//                     array('%d', '%d')
//                 );
//                 return '<p>User added to the group successfully!</p>';
//             } else {
//                 return '<p>Group not found.</p>';
//             }
//         } else {
//             return '<p>No user found with that email address.</p>';
//         }
//     }

//     // Display the form
//     $output = '
//     <form method="post">
//         <label for="add_user_email">add a user to a group:</label>
//         <input type="email" id="add_user_email" placeholder="Email:" name="add_user_email" required><br>
//         <label for="group_id">Select Group:</label>
//         <select name="group_id" id="group_id" required><br>';

//     // Fetch all groups to populate the dropdown
//     global $wpdb;
//     $groups = $wpdb->get_results("SELECT id, group_name FROM {$wpdb->prefix}LEF_groups");

//     foreach ($groups as $group) {
//         $output .= '<option value="' . esc_attr($group->id) . '">' . esc_html($group->group_name) . '</option>';
//     }

//     $output .= '</select>
//         <input type="submit" value="Add User">
//     </form>';

//     return $output;
// }
// add_shortcode('add_user_by_email', 'add_user_by_email_shortcode');

// // start of the add wishlist shortcode which is supposed to allow a user to add a wishlist to the current group they are in
// function add_wishlist_shortcode() {
//     if (!is_user_logged_in()) {
//         return '<p>You must be logged in to add links to your wishlist.</p>';
//     }

//     $output = '';

//     // Handle form submission
//     if (isset($_POST['wishlist_link'])) {
//         $wishlist_link = esc_url($_POST['wishlist_link']);
//         $user_id = get_current_user_id();

//         if (!empty($wishlist_link)) {
//             // Save the link to the wishlist for the user
//             global $wpdb;
//             $table_wishlists = $wpdb->prefix . 'LEF_wishlists';

//             // Insert the link into the wishlist table
//             $wpdb->insert(
//                 $table_wishlists,
//                 array(
//                     'user_id' => $user_id,
//                     'wishlist_link' => $wishlist_link,
//                     'created_at' => current_time('mysql')
//                 ),
//                 array('%d', '%s', '%s')
//             );

//             $output .= '<p>Link has been added to your wishlist.</p>';
//         } else {
//             $output .= '<p>Please enter a valid link.</p>';
//         }
//     }

//     // Display the form
//     $output .= '
//     <form method="post">
//         <label for="wishlist_link">Enter Link for Wishlist:</label>
//         <input type="url" id="wishlist_link" name="wishlist_link" required>
//         <input type="submit" value="Add to Wishlist">
//     </form>';

//     return $output;
// }
// add_shortcode('add_wishlist', 'add_wishlist_shortcode');