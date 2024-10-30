=== LicenceToBill for Wordpress ===

Contributors: LicenceToBill
Tags: subscription, recurring billing, subscription billing, licencetobill
Requires at least: 3.2.1
Tested up to: 3.7.1
Stable tag: 2.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The LicenceToBill plugin allows you to sell by subscription the access to your pages, articles, content, video, text published on your website by subscription
LicenceToBill manages offers, subscriptions, payment and billing.


== Description ==

LicenceToBill's official plugin for WordPress will:

* Create an "Options" page to manage your LicenceToBill settings; 
* Enable LicenceToBill's Plugin like you would do for any other Wordpress plugin. Enter your LicenceToBill credentials and refer to the FAQs for any other information. 

== Installation ==

1. Log in as administrator in Wordpress.
2. Go to Extensions > Add and send `licencetobill-for-wordpress.zip`.
3. Activate the LicenceToBill extension through the 'Plugins' menu in WordPress.
5. Go to Settings > LicenceToBill and set BusinessKey and AgentKey (Find these keys in LicenceToBill Account http://licencetobill.com/ )

== Frequently Asked Questions ==

= What are the shortcodes ? =
* Use [LTBdeals link_text="My Subscriptions"] to retrieve the url of the page hosted by LicenceToBill which displays all the subscriptions of a user logged on your wordpress site.
* Use [LTBinvoices link_text="My Invoices"] to retrieve the url of the page hosted by LicenceToBill which displays all the invoices of a user logged on your wordpress site.
* Use [LTBoffers url_if_anonymous="https://XYZ.licencetobill.com" link_text="Change Offer" keyoffer="xxxx"] to retrieve the url of the page hosted by LicenceToBill which displays all your offers. The  keyoffer attribut is optionnal. It can help you to redirect your users to a specific offer. 
* Use [LTBaccess keyfeature="XXX-XXX-XXX-XXX" display_text_if_noaccess="yes" text_if_noaccess='This video/text is available only to paying subscribers. Please <a href="{link_upgrade}">choose a paying offer</a>.']CONTENT TO PROTECT[/LTBaccess]
The keyfeature attribut is mandatory. Get it from the backoffice of LicenceToBill http://secure.licencetobill.com/

= Where can I find my BusinessKey and AgentKey ? =
* Please log in from http://licencetobill.com.
* Click on "Global Parameters" on the left menu

== Screenshots ==

== Changelog ==

= 2.0.1 =
* Fix Trial Mode

= 2.0.0 =
* Added shortcodes

= 1.0.1 =
* Bug fix on install.

= 1.0 =
* First stable release.