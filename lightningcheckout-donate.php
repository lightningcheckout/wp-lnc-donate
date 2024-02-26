<?php
/*
Plugin Name: Lightning Checkout Bitcoin Donate
Description: A custom payment form plugin with Lightning-fast checkout for Bitcoin donations.
Version: 1.0
Author: Your Name
*/

// Register Settings
function lnc_btcdonate_register_settings()
{
    register_setting("lnc_btcdonate_settings_group", "lnc_btcdonate_api_endpoint");
    register_setting("lnc_btcdonate_settings_group","lnc_btcdonate_api_key");
    register_setting("lnc_btcdonate_settings_group","lnc_btcdonate_api_secret");
    register_setting("lnc_btcdonate_settings_group","lnc_btcdonate_currency_options","lnc_btcdonate_sanitize_currency_options");

    add_settings_section("lnc_btcdonate_settings_section","API Settings","lnc_btcdonate_settings_section_callback","lnc_btcdonate_settings");

    add_settings_field("lnc_btcdonate_api_endpoint","API Endpoint","lnc_btcdonate_api_endpoint_callback","lnc_btcdonate_settings","lnc_btcdonate_settings_section");
    add_settings_field("lnc_btcdonate_api_key","API Key","lnc_btcdonate_api_key_callback","lnc_btcdonate_settings","lnc_btcdonate_settings_section");
    add_settings_field("lnc_btcdonate_api_secret","Lightning Wallet","lnc_btcdonate_api_secret_callback","lnc_btcdonate_settings","lnc_btcdonate_settings_section");
    add_settings_field("lnc_btcdonate_currency_options","Currency Options","lnc_btcdonate_currency_options_callback","lnc_btcdonate_settings","lnc_btcdonate_settings_section");
}

function lnc_btcdonate_settings_section_callback()
{
    echo "Enter your API settings and configure currency options below:";
}

function lnc_btcdonate_api_endpoint_callback()
{
    $endpoint = get_option("lnc_btcdonate_api_endpoint");
    echo "<input type='text' name='lnc_btcdonate_api_endpoint' value='$endpoint' />";
}

function lnc_btcdonate_api_key_callback()
{
    $key = get_option("lnc_btcdonate_api_key");
    echo "<input type='text' name='lnc_btcdonate_api_key' value='$key' />";
}

function lnc_btcdonate_api_secret_callback()
{
    $secret = get_option("lnc_btcdonate_api_secret");
    echo "<input type='text' name='lnc_btcdonate_api_secret' value='$secret' />";
}

function lnc_btcdonate_currency_options_callback()
{
    $currency_options = get_option("lnc_btcdonate_currency_options"); ?>
    <input type="text" name="lnc_btcdonate_currency_options" value="<?php echo esc_attr($currency_options); ?>" placeholder="Enter currency codes (comma-separated)" />
    <?php
}

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

function save_donation_message($donation_details) {
    $post_data = array(
        'post_title' => $donation_details['title'] . ' ('.$donation_details['amount'].' sats)',
        'post_content' => $donation_details['message'],
        'post_type' => 'donation',
        'post_status' => 'publish',
    );
    wp_insert_post($post_data);
}

function lnc_btcdonate_sanitize_currency_options($input)
{
    // Sanitize and ensure that only valid currency codes are saved
    $valid_currencies = lnc_btcdonate_get_currency_list();

    // Convert the input to an array, Filter out invalid currencies, Convert back to a comma-separated string
    $input_array = explode(",", $input);
    $sanitized_input = array_intersect($input_array, array_keys($valid_currencies));
    $sanitized_input_string = implode(",", $sanitized_input);
    return $sanitized_input_string;
}

// Function to define a basic set of currencies (replace with your desired list)
function lnc_btcdonate_get_currency_list()
{
    return [
        "USD" => "US Dollar",
        "EUR" => "Euro",
        "GBP" => "British Pound",
        "SAT" => "Bitcoin",
        // Add more currencies as needed
    ];
}

// Add Top-level Menu Page
function lnc_btcdonate_menu_page()
{
    add_menu_page("LN Checkout", "LN Checkout", "manage_options", "lnc_btcdonate_main", "lnc_btcdonate_render_main_page", "dashicons-lightning", 30);
    add_submenu_page("lnc_btcdonate_main", "BTC Donate", "BTC Donate", "manage_options", "lnc_btcdonate_settings", "lnc_btcdonate_render_settings_page");
}

function lnc_btcdonate_render_main_page()
{
    ?>
    <div class="wrap"><h1>Lightning Checkout</h1></div>
    <?php
}

function lnc_btcdonate_render_settings_page()
{
    ?>
    <div class="wrap"><h1>Bitcoin Donate Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields("lnc_btcdonate_settings_group");
            do_settings_sections("lnc_btcdonate_settings");
            submit_button("Save Settings");?>
        </form>
    </div>
    <?php
}

add_action("admin_menu", "lnc_btcdonate_menu_page");
add_action("admin_init", "lnc_btcdonate_register_settings");

// Register the webhook endpoint
function register_webhook_endpoint() {
    register_rest_route('lightningcheckout-donate/v1', '/webhook/', array(
        'methods'  => 'POST',
        'callback' => 'handle_webhook',
    ));
}
add_action('rest_api_init', 'register_webhook_endpoint');

// Handle the incoming webhook data
function handle_webhook($request) {
    $data = $request->get_json_params();
    error_log(data);


    //$donation_details = array(
    //    'title' => $data['misc']['donation_title'],
    //    'amount' => $data['amount'],
    //    'message' => $data['misc']['donation_message'],
    //);

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




// Add Form Shortcode
function lnc_btcdonate_shortcode()
{
    ob_start();

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit"])) {

        error_log("================================================================");

        // Retrieve form data
        $donation_amount = sanitize_text_field($_POST["donation_amount"]);
        $donor_name = sanitize_text_field($_POST["donor_name"]);
        $donor_comment = sanitize_text_field($_POST["donor_comment"]);
        $selected_currency = sanitize_text_field($_POST["donor_currency"]);

        // Retrieve API settings from the admin
        $api_endpoint = get_option("lnc_btcdonate_api_endpoint");
        $api_key = get_option("lnc_btcdonate_api_key");
        $api_lnwallet = get_option("lnc_btcdonate_api_secret");

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

// Enqueue Active Theme Styles
function lnc_btcdonate_theme_styles()
{
    // Enqueue the active theme's stylesheet
    wp_enqueue_style("lightningcheckout-bitcoin-donate-theme-styles", get_stylesheet_uri());
}

add_action( "wp_enqueue_scripts", "lnc_btcdonate_theme_styles");

// User-Provided Styles
function lnc_btcdonate_user_styles()
{
    /**
     * Action hook for users to enqueue their own stylesheet for the custom payment form.
     */
    do_action("lnc_btcdonate_enqueue_styles");
}

add_action("wp_head", "lnc_btcdonate_user_styles");

function lnc_btcdonate_enqueue_styles()
{
    // Enqueue Bootstrap stylesheet
    wp_enqueue_style("bootstrap", "https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css");
    // Enqueue your plugin stylesheet
    wp_enqueue_style( "lightningcheckout-style", plugins_url("lightningcheckout-donate-style.css", __FILE__));
}

add_action("wp_enqueue_scripts", "lnc_btcdonate_enqueue_styles");
