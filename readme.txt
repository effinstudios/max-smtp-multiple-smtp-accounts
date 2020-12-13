=== Max SMTP - Multiple SMTP Accounts ===
Contributors: effinstudios
Donate link: https://ko-fi.com/effinstudios
Tags: multiple, smtp, email, mail, logs, wp smtp, wp email, max smtp, woocommerce, ninja-forms, contact-form-7, gravityforms
Requires at least: 5.0
Tested up to: 5.6
Requires PHP: 5.6
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Use multiple SMTP email accounts, cycle through your SMTP's maximum send limits, and queue failed emails from your WordPress blog/website.
Tested and works with popular plugins such as Ninja Forms, WooCommerce, Contact Form 7, Gravity Forms, ...

== Features ==
* Send emails using your preferred SMTP mail servers.
* Add multiple SMTP accounts to use with your WordPress blog/website.
* Add multiple SMTP account fallbacks and avoid missing failed sent emails from your WordPress blog/website.
* Automatically queues and retries to send failed emails from your WordPress blog/website.
* Easily track your SMTP account usage and monitor failed emails through an easy to use wp-admin interface.

== Installation ==
Extract the zip file and just drop the contents in your wp-content/plugins/ directory then activate the Max SMTP from your wp-admin/plugins page.

== Frequently Asked Questions ==
= Can I use my Gmail/Yahoo/Web Hosting email account? =
* Yes, as long as you have the SMTP server settings yoiu can configure and save your SMTP server account details for use on your WordPress blog/website.

== Screenshots ==
* Please visit our GitHub Repository - [Max SMTP - Multiple SMTP Accounts](https://github.com/effinstudios/max-smtp-multiple-smtp-accounts) for screenshots.

== Changelog ==

= Version 1.1 =
* Added feature to view queued email content.
* Added hooks "maxsmtp_filter_smtp_settings" to filter the SMTP settings array before it is set and "maxsmtp_filter_email_queue_before_save" to filter the failed email array before it is saved to the email queue.

= Version 1.0.9 =
* Improved user input security.

= Version 1.0.8 =
* Initial public release.

== Upgrade Notice ==

= Version 1.1 =
Upgrade now to Version 1.1 to easily view your queud email contents with this added new feature.

= Version 1.0.9 =
Please upgrade to Version 1.0.9 which improves user input security.

= Version 1.0.8 =
If you are using the pre-release version please upgrade to Version 1.0.8 which fixes several bugs and functionality.