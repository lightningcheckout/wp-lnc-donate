<?php
// Register the webhook endpoint
function register_webhook_endpoint() {
    register_rest_route('lightningcheckout-donate/v1', '/webhook/', array(
        'methods'  => 'POST',
        'callback' => 'handle_webhook',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'register_webhook_endpoint');

function save_donation_message($donation_details) {
    $post_data = array(
        'post_title' => $donation_details['title'] . ' ('.$donation_details['amount'].' sats)',
        'post_content' => $donation_details['message'],
        'post_type' => 'donation',
        'post_status' => 'publish',
    );
    wp_insert_post($post_data);
}


// Handle the incoming webhook data
function handle_webhook($request) {
    $data = $request->get_json_params();
    error_log(data);


    $donation_details = array(
        'title' => $data['description'],
        'amount' => $data['amount'],
        'message' => $data['payment_hash'],
    );

    save_donation_message($donation_details);

    // Verify that the incoming request has the required data
    if (isset($data['title']) && isset($data['content'])) {
        // Prepare post data
        $post_data = array(
            'post_title'   => sanitize_text_field($data['title']),
            'post_content' => wp_kses_post($data['content']),
            'post_status'  => 'publish',
            'post_type'    => 'donation',  // Change this to your custom post type
        );

        // Insert the post
        $post_id = wp_insert_post($post_data);

        if (!is_wp_error($post_id)) {
            // Successfully created the post
            return new WP_REST_Response(array('message' => 'Post created successfully.'), 200);
        } else {
            // Failed to create the post
            return new WP_REST_Response(array('message' => 'Failed to create post.'), 500);
        }
    } else {
        // Required data is missing
        return new WP_REST_Response(array('message' => 'Missing required data.'), 400);
    }
}
