#!/bin/bash

set -eo pipefail

composer require \
  drupal/upgrade_status:^3 \
  drupal/devel:^4 \
  drush/drush:^10 \
  -W --no-update

composer require \
  phpunit/phpunit:"^4 | ^7 | ^8 | ^9 | ^10" \
  phpstan/phpstan \
  --dev -W --no-update

composer config extra.enable-patching true

composer require cweagans/composer-patches drupal/upgrade_status --no-update
php -d memory_limit=-1 `which composer` update -W --optimize-autoloader --prefer-dist

chmod 777 web/sites/default
find web/sites/default -name "*settings.php" -exec chmod 777 {} \;
find web/sites/default -name "*services.yml" -exec chmod 777 {} \;
composer require drupal/core-recommended:^9.2 drupal/core-composer-scaffold:^9.2 drupal/core-project-message:^9.2 drush/drush:^10 -W --no-update
composer require drupal/core-dev:^9.2 --dev -W --no-update
php -d memory_limit=-1 `which composer` update -W --optimize-autoloader --prefer-dist