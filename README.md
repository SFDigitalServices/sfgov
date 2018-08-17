# sfgov

[![CircleCI](https://circleci.com/gh/SFDigitalServices/sfgov.svg?style=shield)](https://circleci.com/gh/SFDigitalServices/sfgov)
[![Dashboard sfgov](https://img.shields.io/badge/dashboard-sfgov-yellow.svg)](https://dashboard.pantheon.io/sites/91d50373-c4cf-40e4-a646-cb73e16a140c#dev/code)
[![Dev Site sfgov](https://img.shields.io/badge/site-sfgov-blue.svg)](http://dev-sfgov.pantheonsite.io/)


## Set up Lando for local development
1. Prerequisites
  * [Lando](https://docs.devwithlando.io/installation/installing.html)
  * [composer](https://getcomposer.org/download/)
  * github personal access token (https://github.com/settings/tokens)
  * pantheon machine token (https://pantheon.io/docs/machine-tokens/)  

2. [Init Lando with Github](https://docs.devwithlando.io/cli/init.html#github)  
`mkdir sfgov && cd sfgov && lando init github --recipe=pantheon`
3. `composer install`
4. `lando start`
5. Get latest from Pantheon dev environment `lando pull`
6. (optional) Turn off caching.  Turn on debug. (https://www.drupal.org/node/2598914)  
  Note:  Run `lando drush cr` instead of `drush cr` in step 7 of linked article.

## Pull Request Workflow
(https://pantheon.io/docs/guides/build-tools/new-pr/)
(https://gist.github.com/Chaser324/ce0505fbed06b947d962)   
(https://www.atlassian.com/git/tutorials/making-a-pull-request)  
TLDR version:  
1. Create a branch and make changes.  Push branch.
2. Open a pull request to merge from branch to master.
3. The team reviews, discusses, and makes change requests to the change. This includes the PM reviewing the Circle CI review app BEFORE it is merged into master.
4. Change is approved and merged
5. Delete branch

## Adding a contrib module
1. Create a new branch `git checkout -b new_branch`
2. Install module with composer `composer require drupal/paragraphs`
3. Update the lock file hash `composer update --lock`
4. Enable the module `lando drush -y en paragraphs`
5. Export config `lando drush -y cex`
6. Check in modified composer and config files `git add composer.* config/*`
7. Commit and push changes `git commit -m 'installed paragraphs' && git push`
8. Wait for CircleCI to build and deploy to a multidev.  CircleCI will add comment to the checkin on GitHub with link to the created MultiDev.
9. Create Pull Request and merge to master
10. Switch away from branch and delete branch `git checkout master && git push origin --delete new_branch && git branch -d new_branch`

## Local Behat Tests
For additonal context, refer to config in .lando.yml.

```
$ lando behat --config=/app/tests/behat-pantheon.yml --tags sfgov
```