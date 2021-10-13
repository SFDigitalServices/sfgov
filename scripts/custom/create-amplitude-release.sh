#!/bin/bash -e

if [ "$AMPLITUDE_API_KEY" = "" ]; then
  echo "Skipping Amplitude release because AMPLITUDE_API_KEY is not set"
  exit 0
elif [ "$AMPLITUDE_SECRET_KEY" = "" ]; then
  echo "Skipping Amplitude release because AMPLITUDE_SECRET_KEY is not set"
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

curl -u "$AMPLITUDE_API_KEY:$AMPLITUDE_SECRET_KEY" -X POST \
  -F release_start="$release_start" \
  -F version="$version" \
  -F title="$title" \
  -F description="$description" \
  -F platforms=Web \
  -F created_by="$created_by" \
  -F chart_visibility=true \
  https://amplitude.com/api/2/release
