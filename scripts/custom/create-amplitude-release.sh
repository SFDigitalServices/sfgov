#!/bin/bash -e

if [ "$AMPLITUDE_AUTH" = "" ]; then
  echo "Skipping Amplitude release because AMPLITUDE_AUTH is not set"
  exit 0
elif [[ "$AMPLITUDE_AUTH" =~ (^:|:$) ]]; then
  echo "Skipping Amplitude release because AMPLITUDE_AUTH is malformed (missing either API or secret)"
  exit 0
fi

# our base timestamp is up to the minute so that we can tweak the start and end
timestamp=$(date +"%Y-%m-%d %H:%M")
# releases without an end "stack" on top of one another in the Amplitude UI, so
# we "end" our releases a second after they start
release_start="$timestamp:00"
release_end="$timestamp:01"
# our version is just the timestamp rounded to the hour (YYYYMMDDHH), since it's
# (currently) impossible to release more frequently
version=$(date +"%Y%m%d%H")
title=$(date)
# commit message
description="Automated release from CI: $(git log -1)"
# commit author
created_by=$(git log -1 --format='%ae')

# API docs: https://developers.amplitude.com/docs/releases-api
curl -u "$AMPLITUDE_AUTH" -X POST \
  -F release_start="$release_start" \
  -F release_end="$release_end" \
  -F version="$version" \
  -F title="$title" \
  -F description="$description" \
  -F platforms=Web \
  -F created_by="$created_by" \
  -F chart_visibility=true \
  https://amplitude.com/api/2/release
