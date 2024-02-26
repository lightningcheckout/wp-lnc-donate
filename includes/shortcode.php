<?php

// Add Form Shortcode
function lnc_btcdonate_shortcode()
{
    ob_start();

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit"])) {

        if (!isset($_POST["lnc_btcdonate_nonce_field"]) || !wp_verify_nonce($_POST["lnc_btcdonate_nonce_field"], "lnc_btcdonate_nonce")) {
        // Nonce verification failed, handle the error as needed
        wp_redirect($_SERVER["REQUEST_URI"]);
        exit;
    }

        // Retrieve form data
        $donation_amount = sanitize_text_field($_POST["donation_amount"]);
        $donor_name = sanitize_text_field($_POST["donor_name"]);
        $donor_comment = sanitize_text_field($_POST["donor_comment"]);
        $selected_currency = sanitize_text_field($_POST["donor_currency"]);

        // Retrieve API settings from the admin
        $api_endpoint = get_option("lnc_btcdonate_api_endpoint");
        $api_key = get_option("lnc_btcdonate_api_key");
        $api_lnwallet = get_option("lnc_btcdonate_api_wallet");
        $api_createpost = get_option("lnc_btcdonate_api_createpost");


        // get blog name
        $site_name = get_bloginfo('name');
        $base_url = home_url();
        $redirect_url = add_query_arg('', '', $base_url . $_SERVER['REQUEST_URI']).'?paid=true';


        // Convert amount to sat if not sat
        if ($selected_currency == 'SAT') {
            $amount_sats = $donation_amount;
        } else {
                $conversion_api_data = [
                    "from_" => $selected_currency,
                    "amount" => $donation_amount,
                    "to" => "sat",
                ];

                // Make API call
                $api_response = wp_remote_post($api_endpoint . "/api/v1/conversion", [
                    "body" => json_encode($conversion_api_data),
                    "headers" => [
                        "Content-Type" => "application/json",
                    ],
                ]);

                // Check if the API call was successful
                if (is_wp_error($api_response)) {
                    error_log("API Error: " . $api_response->get_error_message());
                } else {
                    // API call was successful
                    $api_body = wp_remote_retrieve_body($api_response);
                    $decoded_response = json_decode($api_body);
                    if ($decoded_response !== null) {
                        $amount_sats = $decoded_response->sats;
                        error_log($amount_sats);
                    }
                    }
            }

        if (strlen($donor_name) > 2) {
            $donar_title = $donor_name.' ('.$amount_sats.' sats)';
        } else {
            $donar_title = 'Anonymous ('.$amount_sats.' sats)';
        }

        // Create payments
        $charge_api_data = [
                "lnbitswallet" => $api_lnwallet,
                "description" => 'Donation '. $site_name,
                "completelink" => $redirect_url,
                "completelinktext" => "Thanks, go back to ". $site_name,
                "webhook" => $base_url.'wp-json/lightningcheckout-donate/v1/webhook',
                "time" => 1440,
                "amount" => $amount_sats,
                "extra" => '{"mempool_endpoint": "https://mempool.space", "network": "Mainnet", "misc": {"lnc_product": "BTCDONATE", "donate_title": "'.$donar_title.'", "donate_message": "'.$donor_comment.'"}}',
            ];

            // Make API call
            $charge_api_response = wp_remote_post($api_endpoint . "/satspay/api/v1/charge", [
                "body" => json_encode($charge_api_data),
                "headers" => [
                    "Content-Type" => "application/json",
                    "X-API-KEY" => $api_key,

                ],
            ]);
        if (is_wp_error($charge_api_response)) {
                    error_log("API Error: " . $charge_api_response->get_error_message());
                } else {
                    // API call was successful
                    $charge_api_body = wp_remote_retrieve_body($charge_api_response);
                    error_log($charge_api_body);
                    $decoded_charge_response = json_decode($charge_api_body);
                    if ($decoded_charge_response !== null) {
                        $charge_id = $decoded_charge_response->id;
                        // Redirect to charge
                        wp_redirect($api_endpoint.'/satspay/'.$charge_id);

                    }
                }
    }



    // Check if the 'paid' parameter is present in the URL
    $is_paid = isset($_GET['paid']) && $_GET['paid'] === 'true';

    // If 'paid' is true, display a thank you message
    if ($is_paid) {
        return '<p>Thanks for your donation!</p>';
    }

    // Retrieve selected currency options
    $selected_currencies = get_option( "lnc_btcdonate_currency_options");
    $selected_currencies = explode(",", $selected_currencies);
    $selected_currencies = array_map("trim", $selected_currencies);
    // Your shortcode logic here
    ?>
        <form action="" method="post">
           <div class="form-row">
              <div class="form-group col-md-12">
                 <input placeholder="Name" type="text" class="form-control" id="donor-name" name="donor_name" />
              </div>
           </div>
           <div class="form-row">
              <div class="form-group col-md-8">
                 <input type="text" placeholder="Amount" class="form-control" id="donation-amount" name="donation_amount" />
              </div>
              <div class="form-group col-md-4">
                 <select id="donor-currency" class="form-control" name="donor_currency">
                    <?php foreach ($selected_currencies as $code): ?>
                    <?php $name = lnc_btcdonate_get_currency_list()[$code] ?? ""; ?>
                    <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?> (<?php echo esc_html($code); ?>)</option>
                    <?php endforeach; ?>
                 </select>
              </div>
           </div>
           <div class="form-row">
              <div class="form-group col-md-12">
                 <textarea id="donor-comment" placeholder="Comment..." class="form-control" name="donor_comment" maxlength="250"></textarea>
              </div>
           </div>
           <div class="form-row">
              <div class="form-group col-md-12">
                 <button type="submit" name="submit" class="btn btn-primary">Donate!</button>
              </div>
           </div>
        </form>
    <?php
    $form_content = ob_get_clean();

    /**
     * Filter the custom payment form output.
     *
     * @param string $form_content The default form content.
     */
    $form_content = apply_filters("lnc_btcdonate_output", $form_content);
    error_log("Lightning Checkout Bitcoin Donate shortcode called.");
    return $form_content;
}

// Register the shortcode
add_shortcode("lightningcheckout_bitcoin_donate", "lnc_btcdonate_shortcode");