INTRODUCTION
------------
Simple Instagram Feed is an integration module for the jquery.instagramFeed
library that can be found at https://github.com/jsanahuja/jquery.instagramFeed.

BENEFITS
--------
Unlike many Instagram integrations, this module does not require a complicated
token and authorization sequence to use. Simply add the jquery.instagramFeed
library, install this module and place the block, assign the Instagram account
that you would like to pull the feed from and save. If you want to change the
number of images or any other settings, use the block settings.


REQUIREMENTS
------------
This module requires the jquery.instagramFeed library that can be found at
https://github.com/jsanahuja/jquery.instagramFeed.

INSTALLATION
------------

* Without Composer *
Download the repository for the jquery.instagramFeed library that can be found
at https://github.com/jsanahuja/jquery.instagramFeed .

Place the file jquery.instagramFeed.min.js in a directory called:
jqueryinstagramfeed

Install the Simple Instagram Feed Block:
  Using DRUSH: drush en simple_instagram_feed
  -or-
  Download it from https://www.drupal.org/project/simple_instagram_feed and i
  nstall it to your website.

* With Composer *

To install this module and the jquery.instagramFeed library with composer,
You will need to perform the following four (4) steps:

1) Edit composer.json to include the jquery.instagramFeed repository.
2) Install the jquery.instagramFeed Library.
3) Install the Simple Instagram Feed module.
4) Enable the Simple Instagram Feed module.

1) Add the code below to into your project's composer.json,
under "repositories":

"repositories": [
  {
    "type": "package",
    "package": {
      "name": "jsanahuja/jqueryinstagramfeed",
      "version": "dev-master",
      "type": "drupal-library",
      "dist": {
        "url": "https://github.com/jsanahuja/jquery.instagramFeed/archive/master.zip",
        "type": "zip"
      }
    }
  }
]

If the installer path is not set, use:

"extra": {
    "installer-paths": {
        "web/libraries/{$name}": ["type:drupal-library"]
    }
}

2) Install the jquery.instagramFeed Library, run:
  $ composer require jsanahuja/jqueryinstagramfeed:dev-master

3) Install the Simple Instagram Feed module, run:
  $ composer require 'drupal/simple_instagram_feed

4) Within the Administration menu in your website, navigate to Extend and
enable the Simple Instagram Feed module.


CONFIGURATION
-------------
Once you have installed Simple Instagram Feed Block and placed the
jquery.inatagramFeed library in your libraries directory, navigate to
Structure -> Block Layout (/admin/structure/block) to create a new Simple
Instagram Feed block on your site. By default the block will use the Instagram
account and display 12 images, 6 images per row. You can change the Instagram
user account, number of images and number of images per row settings as well as
displaying the Profile and Bio for the Instagram account.

KNOWN LIMITATIONS
-----------------
If you would like to have different feeds on different pages, you can create as
many feed blocks as you like. However you can not have more than one feed per
page. This is being worked on and will hopefully be resolved for a future
release.
