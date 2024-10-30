=== Plugin Name ===
Contributors: thegrumpydeveloper
Donate link: http://erinhookkelly.com/bridge
Tags: paypal, active campaign, ipn
Requires at least: 3.0.1
Tested up to: 3.4
Stable tag: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows users to map their paypal buttons to email campaign lists in Active Campaigns (and soon mailchimp and others)

== Description ==

This plugin allows users to host their own IPN listener on their wordpress install.  It will connect to the email service via the API and allow the user to map a 
paypal button to an active campaign list.  The pro version of the plugin will allow users to map multiple buttons to multiple lists.  Currently just Active Campaign
is supported but in the next version, will be adding Mailchimp and other popular providers.


== Installation ==

1.  Upload `bridge_paypal.zip` to the `/wp-content/plugins/` directory
2.  Activate the plugin through the 'Plugins' menu in WordPress
3.  Visit 'Bridge Settings' on the left hand side menu
4.  If you have a pro activation key, provide it there, if not, skip
5.  Select an email service (currently only Active Campaign)
6.  Provide API URL and API Key and hit 'Save Changes'
7.  Once valid credentials are given, you will see the 'List Mapping' section is editable
8.  In the left hand column, provide the name of the paypal button you are using
9.  On the right hand column, select the 'Email List' you wish to add them to.
10. Once these are in, press save changes.
11. Go into your paypal settings for IPN notifications and provide it with the URL provided in the 'IPN URL' section
12. Now everyone someone buys somethign from a paypal button, it will use this url.  you can see a log of all transactions at the bottom in 'Transaction Log'

== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

= 1.0 =
* Initial Version

== Upgrade Notice ==