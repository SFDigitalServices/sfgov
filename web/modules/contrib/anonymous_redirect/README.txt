SUMMARY
=======

Anonymous Redirect is a drupal 8 implementation of the D7 anonymous_redirect module with a few improvements.
The module grants users with admin privileges the ability to redirect all anonymous users to any internal or external urls.
Authenticated are still able to access the site as per usual.


INSTALLATION
============

No special install steps are necessary to use this module, see https://www.drupal.org/documentation/install/modules-themes/modules-8 for further information.


CONFIGURATION
=============

Visit admin/config/development/anonymous-redirect. From here you will be able to: 

* Turn on and off anonymous redirects
* Set the path that anonymous users are redirected to
* Use '<front>' or '/path_name' internal urls, and "http://website_url.com" for external links.
* Wildcards (*) are supported for URL Overrides


Maintainer
==========

* Adrian Gordon (adrian1231) - https://www.drupal.org/u/adrian1231
