#!/bin/bash

composer install

# what dir does this script exist
SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do # resolve $SOURCE until the file is no longer a symlink
  DIR="$( cd -P "$( dirname "$SOURCE" )" >/dev/null 2>&1 && pwd )"
  SOURCE="$(readlink "$SOURCE")"
  [[ $SOURCE != /* ]] && SOURCE="$DIR/$SOURCE" # if $SOURCE was a relative symlink, we need to resolve it relative to the path where the symlink file was located
done
DIR="$( cd -P "$( dirname "$SOURCE" )" >/dev/null 2>&1 && pwd )"

# delete the simplesamlphp install by composer
# and create a symlink to the one installed in web/private
cd $DIR/../../vendor/simplesamlphp
pwd
rm -r simplesamlphp
ln -Fs ../../web/private/simplesamlphp-1.17.0-rc3 simplesamlphp

# circleci: run composer install again to get dev dependencies
cd ../..
composer install