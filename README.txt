

Generate ZIP on macbook:
zip -r -X lightningcheckout-donate.zip lightningcheckout-donate -x "*/.*"



Shortcode:
[lightningcheckout_bitcoin_donate]




/lightning-checkout-donate
  |-- /includes
       |-- api-settings.php
       |-- donation-post-type.php
       |-- webhook-handler.php
  |-- /admin
       |-- settings-page.php
  |-- /public
       |-- shortcode.php
       |-- styles.php
  |-- lightning-checkout-donate.php