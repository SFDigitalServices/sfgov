#!/bin/bash
set -e
export TERMINUS_ENV=$CIRCLE_BUILD_NUM

# Bring the code down to Circle so that modules can be added via composer.
git clone $(terminus connection:info ${TERMINUS_SITE}.dev --field=git_url) --branch $TERMINUS_ENV drupal-site
cd drupal-site


composer -- config repositories.papc vcs git@github.com:pantheon-systems/pantheon_advanced_page_cache.git
# Composer require the given commit of this module
composer -- require drupal/views_custom_cache_tag "drupal/pantheon_advanced_page_cache:dev-${CIRCLE_BRANCH}#${CIRCLE_SHA1}"

# Don't commit a submodule
rm -rf web/modules/contrib/pantheon_advanced_page_cache/.git/

# Make a git commit
git add .
git commit -m 'Result of build step'
git push --set-upstream origin $TERMINUS_ENV
