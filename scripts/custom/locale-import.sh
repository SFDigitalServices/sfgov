#!/usr/bin/env bash
set -eo pipefail

files=$(ls -1 config/translations/**.*.po || echo '')
target_env="${TERMINUS_SITE?'TERMINUS_SITE is not set'}.${TERMINUS_ENV?'TERMINUS_ENV is not set'}"

for file in $files; do
  # remove everything up to and including the first ".", so
  # "config/translations/custom.es.po" yields "es.po"
  ext="${file#*.}"
  # remove the .po suffix; what's left should be the language code
  lang="${ext%%.po}"
  echo "importing translations from $file (lang: '$lang', ext: '$ext')"
  terminus -n drush "$target_env" -- locale:import --type=customized --override=none "$lang" "/code/$file"
done