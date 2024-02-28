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

function publish_donation($donation_details) {
    // Check if the payment hash is provided
    if (isset($donation_details->details->payment_hash)) {
        // Query to check if a post with the payment hash already exists
        $payment_hash = $donation_details->details->payment_hash;
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
        if (empty($existing_post)) {
			error_log("No post found with payment hash: " . $payment_hash);
			return False;
        } else {
			// Publish donation
			$post = $existing_post[0];
    		$post_data = array(
        		'ID' => $post->ID,
        		'post_status' => 'publish',
    			);
    		$updated = wp_update_post($post_data);
			return True;
        }
    } else {
		return False;
	}
}

// Handle the incoming webhook data
function handle_webhook($request) {

    // Retrieve API settings from the admin
    $api_endpoint = get_option("lnc_btcdonate_api_endpoint");
    $api_key = get_option("lnc_btcdonate_api_key");

    // Get payment data via payemnt hash
    $data = $request->get_json_params();
    $payment_response = wp_remote_get($api_endpoint . "/api/v1/payments/".$data['payment_hash'], [
        "headers" => [
            "Content-Type" => "application/json",
            "X-API-KEY" => $api_key,

        ],
    ]);
    if (is_wp_error($payment_response)) {
        error_log("API Error: " . $payment_response->get_error_message());
    } else {
        // API call was successful
        $api_body = wp_remote_retrieve_body($payment_response);
        $decoded_response = json_decode($api_body);
		error_log(json_encode($decoded_response));
        if ($decoded_response !== null) {
            $save_donation_result = publish_donation($decoded_response);
			if ($save_donation_result) {
				return new WP_REST_Response(array('message' => 'Donation published.'), 200);
			} else {
				return new WP_REST_Response(array('message' => 'Error processing webhook.'), 500);
			}
        }
    }
    return new WP_REST_Response(array('message' => 'Failed to publish donation.'), 500);
}
