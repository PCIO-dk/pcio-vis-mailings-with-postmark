=== PCIO VIS Mailings with Postmark ===
Contributors:      pcio
Author:            PCIO
Author URI:        https://www.pcio.dk
Plugin URI:        https://www.pcio.dk/vis-plugins
Tags:              email, postmark, newsletter, mail, bulk mail
Requires at least: 6.2
Tested up to:      7.0
Requires PHP:      8.1
Requires Plugins:  pcio-vis-member-event
Stable tag:        1.0.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Sends PCIO VIS Member Event mailings via the Postmark API; falls back to the standard WordPress mailer when not configured.

== Description ==

PCIO VIS Mailings with Postmark is an extension for the PCIO VIS Member Event plugin. It routes member mailings and newsletters through the [Postmark](https://postmarkapp.com) transactional/broadcast e-mail API instead of the default `wp_mail()` mailer.

When a Postmark server token and a verified sender address are configured, the **Mails** page sends via Postmark's batch endpoint — with attachment support and automatic chunking to stay within Postmark's payload limits. When the settings are left blank, the plugin does nothing and sending falls back to the standard WordPress mailer, so the Mails feature always works.

Settings are managed under **Vis → Postmark** in the WordPress admin.

This plugin requires the PCIO VIS Member Event core plugin to be installed and active.

Deleting the plugin from the **Plugins** screen removes the Postmark settings (API token, from address and from name) it stored; deactivating alone leaves them in place.

== Installation ==

1. Install and activate the **PCIO VIS Member Event** core plugin first.
2. Upload the `pcio-vis-mailings-with-postmark` directory to `/wp-content/plugins/`.
3. Activate the plugin in **Plugins** in the WordPress admin.
4. Go to **Vis → Postmark** and enter your Postmark **Server API Token**, **From Email** (a verified sender in your Postmark account) and optional **From Name**.

== Frequently Asked Questions ==

= What happens if I don't configure Postmark? =
Nothing changes — member mailings fall back to the standard WordPress mailer (`wp_mail()`).

= Where do I get a server API token? =
From your account at [postmarkapp.com](https://postmarkapp.com). Use a **Server** token, not an account token.

= Why did a mailing fail to send? =
The sender address must be a verified sender signature or domain in your Postmark account. Check **Vis → Postmark** for the current status.

== External services ==

This plugin connects to the Postmark email-delivery API (operated by AC PM, LLC / ActiveCampaign) to send member mailings — but only when you have entered a Postmark **Server API Token** and a verified **From Email** under **Vis → Postmark**. If those settings are left blank, no data is ever sent to Postmark and mailing falls back to the standard WordPress mailer (`wp_mail()`).

What is sent, and when:

* **When:** each time an administrator sends a mailing from the **Mails** page while Postmark is configured. Nothing is transmitted during normal browsing or admin use.
* **Where:** `https://api.postmarkapp.com/email/batch` (over HTTPS).
* **What:** the recipient email addresses of the selected members, the message subject and HTML body, any attachments you added to the mailing, your configured sender name and address, and your Postmark Server API Token (sent as an authentication header).

Because member email addresses and message content are transmitted to a third party, please make sure your own privacy policy covers this processing. By using this integration you accept Postmark's / ActiveCampaign's terms and policies:

* Terms of Service: https://postmarkapp.com/terms-of-service
* Privacy Policy: https://www.activecampaign.com/legal/privacy-policy
* Data Processing Addendum (DPA): https://postmarkapp.com/dpa


== Screenshots ==

1. In the WordPress Admin page you can specify the Postmark account information

== Changelog ==

= 1.0.0 =
* Initial release.
