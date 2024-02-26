<?php
/*
Plugin Name: Lightning Checkout Bitcoin Donate
Description: A custom payment form plugin with Lightning-fast checkout for Bitcoin donations.
Version: 1.0
Author: Your Name
*/

// Register Settings
function lightningcheckout_bitcoin_donate_register_settings()
{
    register_setting(
        "lightningcheckout_bitcoin_donate_settings_group",
        "lightningcheckout_bitcoin_donate_api_endpoint"
    );

    register_setting(
        "lightningcheckout_bitcoin_donate_settings_group",
        "lightningcheckout_bitcoin_donate_api_key"
    );

    register_setting(
        "lightningcheckout_bitcoin_donate_settings_group",
        "lightningcheckout_bitcoin_donate_api_secret"
    );

    register_setting(
        "lightningcheckout_bitcoin_donate_settings_group",
        "lightningcheckout_bitcoin_donate_currency_options",
        "lightningcheckout_bitcoin_donate_sanitize_currency_options"
    );

    add_settings_section(
        "lightningcheckout_bitcoin_donate_settings_section",
        "API Settings",
        "lightningcheckout_bitcoin_donate_settings_section_callback",
        "lightningcheckout_bitcoin_donate_settings"
    );

    add_settings_field(
        "lightningcheckout_bitcoin_donate_api_endpoint",
        "API Endpoint",
        "lightningcheckout_bitcoin_donate_api_endpoint_callback",
        "lightningcheckout_bitcoin_donate_settings",
        "lightningcheckout_bitcoin_donate_settings_section"
    );

    add_settings_field(
        "lightningcheckout_bitcoin_donate_api_key",
        "API Key",
        "lightningcheckout_bitcoin_donate_api_key_callback",
        "lightningcheckout_bitcoin_donate_settings",
        "lightningcheckout_bitcoin_donate_settings_section"
    );

    add_settings_field(
        "lightningcheckout_bitcoin_donate_api_secret",
        "API Secret",
        "lightningcheckout_bitcoin_donate_api_secret_callback",
        "lightningcheckout_bitcoin_donate_settings",
        "lightningcheckout_bitcoin_donate_settings_section"
    );

    add_settings_field(
        "lightningcheckout_bitcoin_donate_currency_options",
        "Currency Options",
        "lightningcheckout_bitcoin_donate_currency_options_callback",
        "lightningcheckout_bitcoin_donate_settings",
        "lightningcheckout_bitcoin_donate_settings_section"
    );
}

function lightningcheckout_bitcoin_donate_settings_section_callback()
{
    echo "Enter your API settings and configure currency options below:";
}

function lightningcheckout_bitcoin_donate_api_endpoint_callback()
{
    $endpoint = get_option("lightningcheckout_bitcoin_donate_api_endpoint");
    echo "<input type='text' name='lightningcheckout_bitcoin_donate_api_endpoint' value='$endpoint' />";
}

function lightningcheckout_bitcoin_donate_api_key_callback()
{
    $key = get_option("lightningcheckout_bitcoin_donate_api_key");
    echo "<input type='text' name='lightningcheckout_bitcoin_donate_api_key' value='$key' />";
}

function lightningcheckout_bitcoin_donate_api_secret_callback()
{
    $secret = get_option("lightningcheckout_bitcoin_donate_api_secret");
    echo "<input type='text' name='lightningcheckout_bitcoin_donate_api_secret' value='$secret' />";
}

function lightningcheckout_bitcoin_donate_currency_options_callback()
{
    $currency_options = get_option(
        "lightningcheckout_bitcoin_donate_currency_options"
    ); ?>
    <input type="text" name="lightningcheckout_bitcoin_donate_currency_options" value="<?php echo esc_attr(
        $currency_options
    ); ?>" placeholder="Enter currency codes (comma-separated)" />
    <?php
}

function lightningcheckout_bitcoin_donate_sanitize_currency_options($input)
{
    // Sanitize and ensure that only valid currency codes are saved
    $valid_currencies = lightningcheckout_bitcoin_donate_get_currency_list();

    // Convert the input to an array
    $input_array = explode(",", $input);

    // Filter out invalid currencies
    $sanitized_input = array_intersect(
        $input_array,
        array_keys($valid_currencies)
    );

    // Convert back to a comma-separated string
    $sanitized_input_string = implode(",", $sanitized_input);

    return $sanitized_input_string;
}

// Function to define a basic set of currencies (replace with your desired list)
function lightningcheckout_bitcoin_donate_get_currency_list()
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
function lightningcheckout_bitcoin_donate_menu_page()
{
    add_menu_page("LN Checkout", "LN Checkout", "manage_options", "lightningcheckout_bitcoin_donate_main", "lightningcheckout_bitcoin_donate_render_main_page", "dashicons-lightning", 30);

    // Add Submenu Page
    add_submenu_page("lightningcheckout_bitcoin_donate_main", "BTC Donate", "BTC Donate", "manage_options", "lightningcheckout_bitcoin_donate_settings", "lightningcheckout_bitcoin_donate_render_settings_page");
}

function lightningcheckout_bitcoin_donate_render_main_page()
{
    ?>
    <div class="wrap">
        <h1>Lightning Checkout</h1>
    </div>
    <?php
}

function lightningcheckout_bitcoin_donate_render_settings_page()
{
    ?>
    <div class="wrap">
        <h1>Bitcoin Donate Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields("lightningcheckout_bitcoin_donate_settings_group");
            do_settings_sections("lightningcheckout_bitcoin_donate_settings");
            submit_button("Save Settings");?>
        </form>
    </div>
    <?php
}

// Rest of the code remains unchanged

add_action("admin_menu", "lightningcheckout_bitcoin_donate_menu_page");
add_action("admin_init", "lightningcheckout_bitcoin_donate_register_settings");
// ...

// Add Form Shortcode
function lightningcheckout_bitcoin_donate_shortcode()
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
        $api_endpoint = get_option("lightningcheckout_bitcoin_donate_api_endpoint");
        $api_key = get_option("lightningcheckout_bitcoin_donate_api_key");
        error_log('selected_currency: ' . $selected_currency);
        if ($selected_currency == 'SAT') {
            $amount_sats = $donation_amount;
        } else {
        // TODO ONLY CONVERT IF NOT SAT
        // Prepare API data
        $api_data = [
            "from_" => $selected_currency,
            "amount" => $donation_amount,
            "to" => "sat",
        ];

        // Make API call
        $api_response = wp_remote_post($api_endpoint . "/api/v1/conversion", [
            "body" => json_encode($api_data),
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
    }

    // Retrieve selected currency options
    $selected_currencies = get_option( "lightningcheckout_bitcoin_donate_currency_options");
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
                    <?php $name = lightningcheckout_bitcoin_donate_get_currency_list()[$code] ?? ""; ?>
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
    $form_content = apply_filters("lightningcheckout_bitcoin_donate_output", $form_content);
    error_log("Lightning Checkout Bitcoin Donate shortcode called.");
    return $form_content;
}

// Register the shortcode
add_shortcode("lightningcheckout_bitcoin_donate", "lightningcheckout_bitcoin_donate_shortcode");

// Enqueue Active Theme Styles
function lightningcheckout_bitcoin_donate_theme_styles()
{
    // Enqueue the active theme's stylesheet
    wp_enqueue_style("lightningcheckout-bitcoin-donate-theme-styles", get_stylesheet_uri());
}

add_action( "wp_enqueue_scripts", "lightningcheckout_bitcoin_donate_theme_styles");

// User-Provided Styles
function lightningcheckout_bitcoin_donate_user_styles()
{
    /**
     * Action hook for users to enqueue their own stylesheet for the custom payment form.
     */
    do_action("lightningcheckout_bitcoin_donate_enqueue_styles");
}

add_action("wp_head", "lightningcheckout_bitcoin_donate_user_styles");

function lightningcheckout_bitcoin_donate_enqueue_styles()
{
    // Enqueue Bootstrap stylesheet
    wp_enqueue_style("bootstrap", "https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css");
    // Enqueue your plugin stylesheet
    wp_enqueue_style( "lightningcheckout-style", plugins_url("lightningcheckout-donate-style.css", __FILE__));
}

add_action("wp_enqueue_scripts", "lightningcheckout_bitcoin_donate_enqueue_styles");
