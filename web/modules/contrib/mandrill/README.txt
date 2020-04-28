## SUMMARY

This module integrates Drupals mail system with Mandrill, a transactional
email delivery API from the folks behind MailChimp.

Learn more at http://mandrillapp.com

## IMPORTANT CHANGES BETWEEN D7 and D8 RELEASES

### The "mandrill_test_mode" variable has been removed

In the D7 version of this module, a "mandrill_test_mode" variable was being used
to alter mail sending functionality when tests were run.

In the D8 version, this functionality is overridden in the MandrillTestService class.

### The "mandrill_api_classname" variable is no longer used in tests

This variable defines the name of the main class in Mandrill's PHP library.

While the variable still exists, it is no longer used as part of the included tests.

During testing, the D7 version of this module would set the value of
"mandrill_api_classname" to the name of a class in a spoof version of Mandrill's
PHP library, which was included in the module.

In D8, we have removed the spoof library entirely and moved its functionality
into the MandrillTestAPI class. This class overrides functions in the MandrillAPI
class to add functionality for testing.

### hook_mandrill_mail_alter() has been removed

In the D7 module, this hook allowed the message and mail sending function to be
changed.

In D8, changes should be made by overriding the send() function of MandrillService.
See MandrillTemplateService in the mandrill_template module for an example.

## REQUIREMENTS

* Mail System (https://drupal.org/project/mailsystem)
* Mandrill PHP library (https://bitbucket.org/mailchimp/mandrill-api-php/get/1.0.52.zip)

## INSTALLATION

* Acquire a Mandrill API key from http://mandrillapp.com

## INSTALLATION NOTES

* If you are upgrading from one of many previous versions, You may find an extra
  Mail System class in the Mailsystem configuration called "Mandrill module
  class". It's harmless, but feel free to delete it.

## CONFIGURATION

### Set Mandrill API Key
Start by loading up the Mandrill admin page at Configuration -> Web
Services (or admin/config/services/mandrill) and adding your API key from
http://mandrillapp.com. Then you'll see more configuration options.

### Email Options
* **From address:** The email address that emails should be sent from
* **From name:** The name to use for sending (optional)
* **Subaccount:** This selection box appears if you have configured subaccounts
on your Mandrill account, and can be used to select the outgoing subaccount to
use for Mandrill sending.
* **_Input format_:** An optional input format to apply to the message body
before sending emails

### Send Options
* **Track opens:** Toggles open tracking for messages
* **Track clicks:** Toggles click tracking for messages
* **Strip query string:** Strips the query string from URLs when aggregating
tracked URL data
* **Log sends that are not registered in mailsystem:** Useful for configuring
Mail System and getting more granular control over emails coming from various
modules. Enable this and set the system default in Mail System to Mandrill,
then trigger emails from various modules and functions on your site. You'll
see Mandrill writing log messages identifying the modules and keys that are
triggering each email. Now you can add these keys in Mail System and control
each email-generating module/key pair specifically. WARNING: If you leave this
enabled, you may slow your site significantly and clog your log files. Enable
only during configuration.

### Google Analytics
* **Domains:** One or more domains for which any matching URLs will
automatically have Google Analytics parameters appended to their query string.
Separate each domain with a comma.
* **Campaign:** The value to set for the utm_campaign tracking parameter. If
empty, the from address of the message will be used instead.

### Asynchronous Options
* **Queue Outgoing Messages** Drops all messages sent through Mandrill into a
queue without sending them. When Cron is triggered, a number of queued messages
are sent equal to the specified Batch Size.
* **Batch Size** The number of messages to send when Cron triggers. Must be
greater than 0.

### SEND TEST EMAIL

The Send Test Email function is pretty self-explanatory. The To: field will
accept multiple addresses formatted in any Drupal mail system approved way.
By configuring the Mandrill Test module/key pair in Mail System, you can
use this tool to test outgoing mail for any installed mailer.

### Update Mail System settings
Mandrill Mail interface is enabled by using the
[Mail System module](http://drupal.org/project/mailsystem). Go to the
[Mail System configuration page](admin/config/system/mailsystem) to start
sending emails through Mandrill. Once you do this, you'll see a list of the
module keys that are using Mandrill listed near the top of the Mandrill
settings page.

Once you set the site-wide default (and any other module classes that may be
listed) to MandrillMailSystem, your site will immediately start using Mandrill
to deliver all outgoing email.

### Module/key pairs
The key is optional: not every module or email uses a key. That is why on the
mail system settings page, you may see some modules listed without keys. For
more details about this, see the help text on the mail system configuration
page.

# Sub-modules

## Templates

In order to use the mandrill_template module, start by creating some templates
in your Mandrill account. Once you do, you can add one or more Mandrill
Template Maps for that template, specifying where in the template to place
the email content and which module/key pair should be sent using the template.
If you want to send multiple module/key pairs through the same Template, you
can make Mandrill the default mail system and make that Template Map the
default template, or you can clone the Template Map for each module/key pair
and assign them individually.

You should also consider enabling the css-inline feature in your Mandrill
account under Settings -> Sending Options. For more info, see
"http://help.mandrill.com/entries/24460141-Does-Mandrill-inline-CSS-automatically-".

## Reports
The mandrill_reports sub-module provides reports on various metrics. For
performance, metrics are cached after the initial load. Clear your cache for
the most current metrics.

### Dashboard
Displays charts that show volume and engagement, along with a tabular list of
URL interactions for the past 30 days.

### Account Summary
Shows account information, quotas, and all-time usage stats.

## Activity
The Mandrill Activity sub-modules allows users to view email activity for any
Drupal entity with a valid email address. Configuration and usage details are in
sub-module's README file.

## Advanced Options
If you would like to use additional template (or other) Mandrill API
variables not implemented in this module, set them in hook_mail_alter under:
$params['mandrill'].
