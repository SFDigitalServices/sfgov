DESCRIPTION
-----------
Google Analytics Reports module provides graphical reporting of your site's
tracking data. Graphical reports include small path-based report in blocks,
and a full path-based report.

Google Analytics Reports API module provide API for developers to access data
from Google Analytics using Google Analytics Core Reporting API
https://developers.google.com/analytics/devguides/reporting/core/v3/.

Google Analytics Reports module provide Views query plugin to create Google
Analytics reports using Views interface.


REQUIREMENTS
------------
* Google Analytics user account https://www.google.com/analytics


DEPENDENCIES
------------
* Google Analytics Reports API has no dependencies.
* Google Analytics Reports depends on Google Analytics Reports API and Views
  modules.


RECOMMENDED MODULES
-------------------
* Charts module https://www.drupal.org/project/charts. Enable Google Charts or
  Highcharts sub-module to see graphical reports.
* Ajax Blocks module https://www.drupal.org/project/ajaxblocks for better page
  loading with Google Analytics Reports blocks.


INSTALLATION
------------
1. Copy the 'google_analytics_reports' module directory in to your Drupal
   sites/all/modules directory as usual. See https://www.drupal.org/documentati
   on/install/modules-themes/modules-7 for details.


CONFIGURATION
-------------
Configuration of Google Analytics Reports API module.

Before you can get the credentials you may need to create a new
project and enable the analytics API for it:

 1. Open Google Developers Console:
    https://console.developers.google.com.
    Log in to you Google account if required.
 2. This will take you to a screen to manage your Google analytics
    APIs. In the top bar there will be a menu to select an API project
    (if you have one), and to create a new project. Click on the
    down-triangle of the menu.
 3. This will produce a modal pop-up. To create a new project, click
    on the plus (+) sign.
 4. Give the project a name and press "Create".
 5. Make this the active project.
 6. Use the hamburger menu to select API & Services » Library Filter
    on "Analytics". Select "Analytics API"
 7. Press "Enable",

Then get the credentials:

 8. Use the hamburger menu to select API & Services » Credentials.
 9. Click the pull-down menu "Create credentials". Select "Help me
    choose".
10. Under "What API are you using", select "Analytics API". (If this
    option does not appear, you have not yet enabled this API for this
    project, see steps 6 and 7 above).
11. Under "Where will you be calling the API from?" select "Web
    Browser (Javascript)". Under "What data will you be accessing?",
    select "User Data".
12. Press "What credentials do I need?" and edit the name if
    necessary.
13. Leave empty "Authorized JavaScript origins".
14. Fill in "Authorized redirect URIs" with
    "http://YOURSITEDOMAIN/admin/config/services/google-analytics-reports-api".
    Replace  "YOURSITEDOMAIN" with the base URL of your site.
15. Press "Create Client ID"-button.
16. Type a product name to show to users and hit "Continue" and then
    "Done".
17. Use the hamburger menu to select API & Services » Credentials.
18. Click on the name of your new client ID to be shown both the
    "Client ID" and "Client Secret".

On the Drupal site navigate to "Configuration » System » Google
Analytics Reports API" and copy "Client ID" and "Client secret" from
the Google Developers console into the fields. Press "Start setup and
authorize account" to allow the project access to Google Analytics
data.

Configuration of Google Analytics Reports module:
1. Configure Google Analytics Reports API module first.
2. Enable Charts module and Google Charts or Highcharts sub-module to see
   graphical reports.
3. Go to "admin/reports/google-analytics-reports/summary" page to see
   Google Analytics Summary report.
4. Go to "admin/structure/block" page and enable "Google Analytics Reports
   Summary Block" and/or "Google Analytics Reports Page Block" blocks.


CACHING
-------
Note that Google has a moderately strict Quota Policy https://developers.google
.com/analytics/devguides/reporting/core/v3/limits-quotas#core_reporting. To aid
with this limitation, this module caches query results for a time that you
specify in the admin settings. Our recommendation is at least three days.


CREDITS
-------
* Joel Kitching (jkitching)
* Tony Rasmussen (raspberryman)
* Dylan Tack (grendzy)
* Nickolay Leshchev (Plazik)
