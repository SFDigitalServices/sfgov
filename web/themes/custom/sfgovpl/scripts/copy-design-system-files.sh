#!/bin/bash

js_path=$(node -e "console.log(require.resolve('sfgov-design-system/dist/js/sfds.js'))")

mkdir -p dist/js && cp $js_path dist/js
