<?php
/**
 * Template Name: My Custom Post
 * Template Post Type: post
 */

get_header();

if ( have_posts() ) : 
    while ( have_posts() ) : the_post();
        the_title();
        the_content();
        comments_template();
        the_post_navigation();
    endwhile;
endif;

get_sidebar();
get_footer();
?>