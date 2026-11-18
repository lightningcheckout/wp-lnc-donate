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
		$args = array(
			'post_type' => 'donation',
			'post_status' => array('draft'),
			'meta_query' => array(
				array(
					'key' => '_payment_hash',
					'value' => $payment_hash,
					'compare' => '=',
				),
			),
			'fields' => 'ids', // Fetch only post IDs
		);

		$posts = get_posts($args);
		if ($posts) {
			foreach ($posts as $post_id) {
				$post_data = array(
					'ID' => $post_id,
					'post_status' => 'publish',
				);
				$updated = wp_update_post($post_data);
				return True;
			}
		} else {
			return False;

		}

	}
	else {
		return False;
	}
}

// Handle the incoming webhook data
function handle_webhook($request) {

    // Retrieve API settings from the admin
	$api_endpoint = get_option("lnc_btcdonate_api_endpoint");
	$api_key = get_option("lnc_btcdonate_api_key");

	// The payload may arrive as a JSON object or as a JSON-encoded string.
	$data = $request->get_json_params();
	if (is_string($data)) {
		$data = json_decode($data, true);
	}

	if (!is_array($data) || !isset($data['payment_hash']) || !is_string($data['payment_hash'])) {
		return new WP_REST_Response(array('message' => 'Invalid payload.'), 400);
	}

	// A payment hash is a 32-byte hex string; reject anything else so it
	// cannot be used to alter the API path called with our API key.
	$payment_hash = strtolower($data['payment_hash']);
	if (!preg_match('/^[a-f0-9]{64}$/', $payment_hash)) {
		return new WP_REST_Response(array('message' => 'Invalid payment hash.'), 400);
	}

	// Verify the payment with the API instead of trusting the webhook body.
	$payment_response = wp_remote_get($api_endpoint . "/api/v1/payments/" . $payment_hash, [
		"headers" => [
			"Content-Type" => "application/json",
			"X-API-KEY" => $api_key,
		]]);

	if (is_wp_error($payment_response)) {
		error_log("API Error: " . $payment_response->get_error_message());
		return new WP_REST_Response(array('message' => 'Failed to publish donation.'), 500);
	}

	$decoded_response = json_decode(wp_remote_retrieve_body($payment_response));
	if ($decoded_response === null) {
		return new WP_REST_Response(array('message' => 'Failed to publish donation.'), 500);
	}

	// Only publish donations that have actually been paid.
	if (empty($decoded_response->paid)) {
		return new WP_REST_Response(array('message' => 'Payment not completed.'), 402);
	}

	if (publish_donation($decoded_response)) {
		return new WP_REST_Response(array('message' => 'Donation published.'), 200);
	}
	return new WP_REST_Response(array('message' => 'Error processing webhook.'), 500);
}
