=== Free WP-Membership Plugin ===
Contributors: foran
Tags: membership
Requires at least: 2.6.0
Tested up to: 3.2.1
Stable tag: 1.1.6.1

Allows the ability to have a membership based page and post restrictions.

== Description ==
Protect your content with Levels. Levels can protect Posts, Pages and Categories.
Levels are not sequential, they are more like groups.
Currently the Payment gateways are non-functional due to stripping out the copy-protection during the open sourcing process.
These will be restored over time, including: PayPal, Authorize.net and Google Checkout.

The primary repository for Free WP-Membership is at [GitHub](https://github.com/Foran/free-wp-membership "Free WP-Membership main repository").
The project home page is at [Free WP-Membership](http://free-wp-membership.foransrealm.com "Free WP-Membership project home page").

== Installation ==
Simply unpack in the wp-content/plugins folder

== Frequently Asked Questions ==
= Where did this come from? =
Synergy Software Group LLC (now out of business) open-sourced their commercial Wordpress plugin WP-Membership 1.1.3. This plugin is based on that original source tree. (Thus why it starts on version 1.1.4)
= When is feature "X" going to be done? =
I work on Free WP-Membership as time permits, so the best answer I can give is "When it's done". With that said it is open source and you can contribute to it. To do so simply fork the project on [GitHub](https://github.com/Foran/free-wp-membership "Fork Me") and submit a pull request with your changes.
Now while I don't have any specific dates things will be done, I am keeping a current list of the sequence things are planned to be done at [Issues](http://free-wp-membership.foransrealm.com/Issues.html "Current Issues") and [GitHub](https://github.com/Foran/free-wp-membership/issues "Current Issues").
= What is currently being worked on? =
Check the [Issues](http://free-wp-membership.foransrealm.com/Issues.html "Current Issues") or [GitHub](https://github.com/Foran/free-wp-membership/issues "Current Issues") pages for the most current list of items.

== Changelog ==
= 1.1.7 =
* More general code cleanup (added comments, removed unused filters, refactored and reorginized code paths, etc)
* Issues on News & Info are now sorted by release milestone
= 1.1.6.1 =
* fixed an error in the installation of 1.1.6 that caused a server 500 error
= 1.1.6 =
* Updated the readme.txt to better conform with WordPress.org's parser
* Refactored Option Tabs (Code Cleanup)
* Added Important Links to News & Info tab
= 1.1.5 =
* Added Issues to News & Info tab
* Fixed some more navigation issues (broken by removing the DRM) when accessing Payment Gateways, Feedback or Troubleshooting from the Settings menu
= 1.1.4 =
* Integrated with WordPress.org's plugin database
* Added an error message to the Feedback tab indicating that it's currently non-functional
* Fixed some navigation that was broken after stripping out DRM
* Payment Gateways were broken due removal of DRM
* Stripped DRM from the original plug-in
* Initial release as Open Source

== Upgrade Notice ==
= 1.1.6.1 =
Contains [HotFix](https://github.com/Foran/free-wp-membership/issues/8 "Details for hotfix") for broken build 1.1.6

== Minimum Requirements ==
* PHP 5.3.0
* Curl (Used to update News & Info)
* Simple Xml
* MySql
* To Be Determined