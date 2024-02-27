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
    // Check if the payment hash is provided
    if (isset($donation_details['payment_hash'])) {
        // Query to check if a post with the payment hash already exists
        $payment_hash = sanitize_text_field($donation_details['payment_hash']);
        $existing_post = get_posts(array(
            'post_type' => 'donation',
            'meta_query' => array(
                array(
                    'key' => '_payment_hash',
                    'value' => $payment_hash,
                ),
            ),
        ));

        // If no post exists with the given payment hash, insert a new post
        $charge_data = json_encode($donation_details['details']['extra']['charge_data']);
        if (empty($existing_post)) {
            $post_data = array(
                'post_title' => $charge_data['misc']['donate_title'],
                'post_content' => $charge_data['misc']['donate_message'],
                'post_type' => 'donation',
                'post_status' => 'publish',
            );

            // Insert the post and Save payment hash as post meta
            $post_id = wp_insert_post($post_data);
            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_payment_hash', $payment_hash);
            }
        } else {
            error_log("Post with payment hash already exists: " . $payment_hash);
        }
    }
}

// Handle the incoming webhook data
function handle_webhook($request) {

    // Receive payment hash
    // get payment data via payemnt hash
    $data = $request->get_json_params();

    // Retrieve API settings from the admin
    $api_endpoint = get_option("lnc_btcdonate_api_endpoint");
    $api_key = get_option("lnc_btcdonate_api_key");

    // Make API call
    $payment_response = wp_remote_post($api_endpoint . "/api/v1/payments/".$data['payment_hash'], [
        "headers" => [
            "Content-Type" => "application/json",
            "X-API-KEY" => $api_key,

        ],
    ]);
    if (is_wp_error($api_response)) {
        error_log("API Error: " . $api_response->get_error_message());
    } else {
        // API call was successful
        $api_body = wp_remote_retrieve_body($api_response);
        $decoded_response = json_decode($api_body);
        if ($decoded_response !== null) {
            save_donation_message($decoded_response);
            return new WP_REST_Response(array('message' => 'Post created successfully.'), 200);
        }
    return new WP_REST_Response(array('message' => 'Failed to create post.'), 500);
}
