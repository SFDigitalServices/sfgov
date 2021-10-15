#!/bin/bash -e

if [ "$AMPLITUDE_AUTH" = "" ] then
  echo "Skipping Amplitude release because AMPLITUDE_AUTH is not set"
  exit 0
elif [[ "$AMPLITUDE_AUTH" =~ (^:|:$) ]]; then
  echo "Skipping Amplitude release because AMPLITUDE_AUTH is malformed (missing either API or secret)"
  exit 0
fi
  
# see: https://developers.amplitude.com/docs/releases-api
release_start=$(date +"%Y-%m-%d %H:%M:00")
# version tag?
version="$release_start"
title="Automated release from deployment at $release_start"
# commit message?
description=""
# merge commit author?
created_by=""

curl -u "$AMPLITUDE_AUTH" -X POST \
  -F release_start="$release_start" \
  -F version="$version" \
  -F title="$title" \
  -F description="$description" \
  -F platforms=Web \
  -F created_by="$created_by" \
  -F chart_visibility=true \
  https://amplitude.com/api/2/release
