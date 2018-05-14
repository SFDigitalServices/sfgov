# sfgov

[![CircleCI](https://circleci.com/gh/SFDigitalServices/sfgov.svg?style=shield)](https://circleci.com/gh/SFDigitalServices/sfgov)
[![Dashboard sfgov](https://img.shields.io/badge/dashboard-sfgov-yellow.svg)](https://dashboard.pantheon.io/sites/91d50373-c4cf-40e4-a646-cb73e16a140c#dev/code)
[![Dev Site sfgov](https://img.shields.io/badge/site-sfgov-blue.svg)](http://dev-sfgov.pantheonsite.io/)


## Development with Lando
1. Prerequisites
  * [Lando](https://docs.devwithlando.io/installation/installing.html)
  * [composer](https://getcomposer.org/download/)
  * github personal access token (https://github.com/settings/tokens)
  * pantheon machine token (https://pantheon.io/docs/machine-tokens/)  

2. [Init Lando with Github](https://docs.devwithlando.io/cli/init.html#github)  
`lando init github --recipe=pantheon`
3. `composer install`
4. `lando start`
5. (optional) Turn off caching.  Turn on debug. (https://www.drupal.org/node/2598914)  
  Note:  Run `lando drush cr` instead of `drush cr` in step 7.

## Adding a contrib module
1. Create a new branch `git clone -b new_branch`
2. Install module with composer `composer require drupal/paragraphs`
3. Enable the module `lando drush -y en paragraphs`
4. Export config `lando drush -y cex`
5. Check in modified composer and config files `git add composer.* config/*`
6. Commit and push changes `git commit -m 'installed paragraphs' && git push`
7. Wait for CircleCI to build and deploy to a multidev.  CircleCI will add comment to the checkin on GitHub with link to the created MultiDev.
8. Create Pull Request and merge to master
9. Switch away from branch and delete branch `git checkout master && git push origin --delete new_branch && git branch -d new_branch`
