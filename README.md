# sfgov

[![CircleCI](https://circleci.com/gh/SFDigitalServices/sfgov.svg?style=shield)](https://circleci.com/gh/SFDigitalServices/sfgov)
[![Dashboard sfgov](https://img.shields.io/badge/dashboard-sfgov-yellow.svg)](https://dashboard.pantheon.io/sites/91d50373-c4cf-40e4-a646-cb73e16a140c#dev/code)
[![Dev Site sfgov](https://img.shields.io/badge/site-sfgov-blue.svg)](http://dev-sfgov.pantheonsite.io/)


## Set up Lando for local development
1. Prerequisites
  * [Lando](https://docs.devwithlando.io/installation/installing.html) current setup requires 3.0.0-RC.2+
  * [composer](https://getcomposer.org/download/)
  * github personal access token (https://github.com/settings/tokens)
  * pantheon machine token (https://pantheon.io/docs/machine-tokens/)  

2. [Init Lando with Github](https://docs.devwithlando.io/cli/init.html#github)  
`mkdir sfgov && cd sfgov && lando init github --recipe=pantheon`
3. Get the secrets and put them in web/private.
4. `./scripts/custom/local_dev_setup.sh`
5. `lando start`
6. Get latest from Pantheon dev environment `lando pull`
	- most of the time, code will not need to be pulled from pantheon, so run ```lando pull --code=none --database=dev --files=dev``` to skip the prompts
7. (optional) Turn off caching.  Turn on debug. (https://www.drupal.org/node/2598914)  
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

## Updating core with composer
From (https://www.drupal.org/docs/8/update/update-core-via-composer)

Can sometimes cause php out of memory issues.  Do this:

```
$ php -d memory_limit=-1 `which composer` update drupal/core --with-dependencies
```

then 

```
$ (lando) drush updatedb
$ (lando) drush cr
```

## Issues with lando/drush (7/26/2019)
Pantheon required a `drush` update to `8.2.3`.  Updating site-local drush to this version resulted in a failed attempt to `lando pull` the db from pantheon, with the following error

```
Class 'Drush\Commands\DrushCommands' not found
/etc/drush/drupal-8-drush-commandfiles/Commands/site-audit-tool/SiteAuditCommands.php:18
```

Temp workaround is to use lando's helper script to import db.  Refer to `.lando.yml` file, under the `tooling` section.

In short, do `lando getdb` from the `/web` directory to import the db from pantheon `dev` environment.