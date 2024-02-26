<?php
// Create a custom post type for donations
function create_donation_post_type() {
    register_post_type('donation', array(
        'labels' => array(
            'name' => 'Donations',
            'singular_name' => 'Donation',
        ),
        'public' => true,
        'has_archive' => false,
        'publicly_queryable' => false,
        'supports' => array('title'),
    ));
}

// Add a noindex meta tag for the 'donation' custom post type
function prevent_indexing_custom_post_type() {
    // Check if it's a single post of the custom post type 'donation'
    if (is_singular('donation')) {
        echo '<meta name="robots" content="noindex" />';
    }
}

add_action('init', 'create_donation_post_type');
add_action('wp_head', 'prevent_indexing_custom_post_type');