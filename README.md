# sfgov

[![CircleCI](https://circleci.com/gh/SFDigitalServices/sfgov.svg?style=shield)](https://circleci.com/gh/SFDigitalServices/sfgov)
[![Dashboard sfgov](https://img.shields.io/badge/dashboard-sfgov-yellow.svg)](https://dashboard.pantheon.io/sites/91d50373-c4cf-40e4-a646-cb73e16a140c#dev/code)
[![Dev Site sfgov](https://img.shields.io/badge/site-sfgov-blue.svg)](http://dev-sfgov.pantheonsite.io/)

## Local Development Setup

### Prerequisites

- Latest version of [Lando](https://docs.devwithlando.io) installed.
- A global installation of [Composer](https://getcomposer.org)*

### Instructions

1. **Get the codebase**: `git clone git@github.com:SFDigitalServices/sfgov.git`. _Note: Always use the Github repository for development. CircleCI is used to deploy an artifact build to Pantheon via Github._
2. Go to the root directory. `cd sfgov`
2. **Run the custom script** which will also install Composer dependencies: `./scripts/custom/local_dev_setup.sh`
3. **Download the following assets** from files(_dev)/private/saml on Pantheon via SFTP or the Backups tab on the dashboard. Place them in web/sistes/default/files/private/saml:

    ```sh
    IDCSCertificate.pem
    metadata/
    saml.crt
    saml.pem
    simplesaml_config.php
    ```

4. **Start the Lando VM**: `lando start`
5. **Obtain a [machine token](https://pantheon.io/docs/machine-tokens/)** from your Pantheon dashboard, and run the provided command, making sure to prefix it with `lando`, e.g. `lando terminus auth:login --machine-token=TOKEN`.
6. **Get latest DB and files from Pantheon** dev environment: `lando pull`. Most of the time, code will not need to be pulled from Pantheon: `lando pull --code=none --database=dev --files=dev`.
7. Create a **local services** file:

    <details>
      <summary>Copy development.services.yml</summary>

      ```sh
      cp web/sites/development.services.yml web/sites/default/local.services.yml
      ```

    </details>

    <details>
      <summary>Enable Twig Debug mode (optional) in local.services.yml</summary>

      ```yml
      parameters:
        # Add Twig config below "parameters".
        twig.config:
          debug: true
          auto_reload: true
          cache: false
      ```

    </details>

8. Create a **local settings** file, and add the settings below:

    <details>
      <summary>Copy example.settings.local.php</summary>

      ```sh
      cp web/sites/example.settings.local.php web/sites/default/settings.local.php
      ```

    </details>

    <details>
      <summary>Add the following to settings.local.php</summary>

      ```php
      # Point to your local services file:
      $settings['container_yamls'][] = DRUPAL_ROOT . '/sites/default/local.services.yml';

      # Add a dummy hash salt.
      $settings['hash_salt'] = 'whatever';

      # Database settings.
      $databases['default']['default'] = array (
        'database' => 'pantheon',
        'username' => 'pantheon',
        'password' => 'pantheon',
        'prefix' => '',
        'host' => 'database',
        'port' => '3306',
        'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
        'driver' => 'mysql',
      );
      ```

    </details>

    _See [Disable Drupal 8 caching during development](https://www.drupal.org/node/2598914) for more details_.

9. **Update dependencies and active config** in the following order:

    ```sh
    # 1. After updating the codebase, install any pending composer dependencies.
    lando composer install
    # 2. If any dependencies were updated, run database updates.
    lando drush updb -y
    # 3. Update active config to include and changes pending in `develop`.
    lando drush cim -y
    # 4. Clear the cache.
    lando drush cr
    ```

10. Visit [https://sfgov.lndo.site](https://sfgov.lndo.site). 🎉

## Pull Request Workflow

https://pantheon.io/docs/guides/build-tools/new-pr/
https://gist.github.com/Chaser324/ce0505fbed06b947d962
https://www.atlassian.com/git/tutorials/making-a-pull-request

TLDR version:

1. Create a branch from `develop` and make changes. Push branch.
2. Open a pull request to merge from branch to `develop`.
3. The team reviews, discusses, and makes change requests to the change. This includes the PM reviewing the CircleCI review app BEFORE it is merged into `develop`.
4. Change is approved and merged.
5. Delete branch.

## Adding a contrib module

1. Create a new branch `git checkout -b new_branch`
2. Install module with composer `composer require drupal/paragraphs`
3. Update the lock file hash `composer update --lock`
4. Enable the module `lando drush -y en paragraphs`
5. Export config `lando drush -y cex`
6. Check in modified composer and config files `git add composer.* config/*`
7. Commit and push changes `git commit -m 'installed paragraphs' && git push`
8. Wait for CircleCI to build and deploy to a multidev. CircleCI will add comment to the checkin on GitHub with link to the created MultiDev.
9. Create Pull Request and merge to develop
10. Switch away from branch and delete branch `git checkout master && git push origin --delete new_branch && git branch -d new_branch`

## Local Behat Tests

For additional context, refer to config in `.lando.yml`.

```sh
lando behat --config=/app/tests/behat-pantheon.yml --tags sfgov
```

## Updating core with Composer

PHP out of memory issues can occur when running updates with Composer. Drupal [documentation](https://www.drupal.org/docs/8/update/update-core-via-composer) suggests the following command, which will disable the `memory_limit`:

```sh
php -d memory_limit=-1 `which composer` update drupal/core --with-dependencies
```

then

```sh
lando drush updatedb
lando drush cr
```

## Issues with Lando/Drush (7/26/2019)

Pantheon required a `drush` update to `8.2.3`. Updating site-local Drush to this version resulted in a failed attempt to `lando pull` the db from Pantheon, with the following error:

```txt
Class 'Drush\Commands\DrushCommands' not found
/etc/drush/drupal-8-drush-commandfiles/Commands/site-audit-tool/SiteAuditCommands.php:18
```

Temp workaround is to use Lando's helper script to import db. Refer to `.lando.yml` file, under the `tooling` section.

In short, do `lando getdb` from the `/web` directory to import the db from Pantheon `dev` environment.

_* I'm not 100% sure, but I don't think global composer is necessary. One can use `lando composer install` instead. -ZK_
