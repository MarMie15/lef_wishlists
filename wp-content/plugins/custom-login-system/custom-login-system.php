<?php
/**
* Plugin Name: Custom Login System
* Description: Custom user registration and login forms.
* Version: 1.0.1
* Author: Marcel Miedema
*/
 
if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

function custom_registration_form() {
    // Check if form is submitted
    if (isset($_POST['submit_registration'])) {
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];

        // Check if email already exists
        if (email_exists($email)) {
            echo 'Email already in use!';
        } else {
            // Create new user
            $userdata = array(
                'user_login' => $email,
                'user_email' => $email,
                'user_pass'  => $password,
                'display_name' => $name,
            );

            $user_id = wp_insert_user($userdata);

            if (!is_wp_error($user_id)) {
                echo 'Registration successful! You can now log in.';
            } else {
                echo 'Error during registration!';
            }
        }
    }

    // Display Registration Form
    ?>
    <form method="POST" action="">
        <input type="text" name="name" placeholder="Your Name" required><br>
        <input type="email" name="email" placeholder="Your Email" required><br>
        <input type="password" name="password" placeholder="Your Password" required><br>
        <button type="submit" name="submit_registration">Register</button>
    </form>
    <?php
}

add_shortcode('custom_registration_form', 'custom_registration_form');


// login form
function custom_login_form() {
    if (isset($_POST['submit_login'])) {
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];

        $user = get_user_by('email', $email);
        
        if ($user && wp_check_password($password, $user->user_pass, $user->ID)) {
            wp_set_auth_cookie($user->ID);
            wp_redirect(home_url());
            exit;
        } else {
            echo 'Invalid email or password!';
        }
    }

    // Display Login Form
    ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Your Email" required><br>
        <input type="password" name="password" placeholder="Your Password" required><br>
        <button type="submit" name="submit_login">Login</button>
    </form>
    <?php
}

add_shortcode('custom_login_form', 'custom_login_form');

// display username afterwards
function display_user_name() {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        echo 'Hello, ' . esc_html($current_user->display_name);
    } else {
        echo 'You are not logged in.';
    }
}

add_shortcode('display_user_name', 'display_user_name');
