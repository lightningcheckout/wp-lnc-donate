# Lightning Checkout Donate BETA

This Wordpress plugin gives you the possiblity to accept Bitcoin lightning donations on your Wordpress site.
For payments the plugin relies on a [LNbits](https://lnbits.com/) instance with plugin Satspay installed. Currently only Lightning payments are implemented since those are best suitable for value4value projects.


## Admin settings
After installing the plugin you can set the following settings:
- API Endpoint
- API Key
- Lightning Wallet
- After payment create donation post
- Currency Options

#### API Endpoint, API Key, Lightning Wallet
These settings you can take from your LNbits instance. API Endpoint is the url of your LNbits instance, without a / at the end.
The API key and Lightning Wallet you find in the GUI of LNbits. Take it from the appropriate wallet.

#### After payment create donation post
When enabled, for every donation a post with donation title and message is created.

#### Currency Options
Give one (or more, seperated by a comma) currencies to be supported. Current options: SAT, EUR, USD, GBP.
When SAT no conversion is done, For other options a conversion is done, before creating the payment.


## Shortcode donation form
To show the donation form on a page or post you must include the following shortcode: [lightningcheckout_bitcoin_donate] into the page/post.

## Shortcode to show donations
To show donations on a page or post you must include the following shortcode: [publish_donations] into the page/post.
Using this makes only sense when the option 'After payment create donation post' is enabled.