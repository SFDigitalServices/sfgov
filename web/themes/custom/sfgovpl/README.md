# SFGOV PL

## Introduction

This is the sfgov patternlab theme.

## Pattern library source files:

all the source files live in the folder:

`sfgovpl/pattern-lab/source/_patterns`

in that folder you'll be able to place your twig template files that are going to be part of the Pattern Library.

## Requirements:

TODO: check versions of the following:

- php-cli
- composer
- nodejs
- gulp

## Installation:

### Install Pattern Lab:

`$ cd pattern-lab`

`$ composer install`

### Install nodejs packages.

run `npm install` from the root of the theme.

the `gulp` command is going to run browserSync and it's going to watch for changes in the patter library.

it is necessary php-cli to auto generate a new pattern library everytime a twig file or a SASS file changes.

### Compiled Pattern Library:

TODO: update the following.

`/themes/custom/sfgovpl/pattern-lab/public/index.html`

### Optional.

setup.sh to generate a symbolic link between the pattern library _patterns folder and the src folder.

## TODO: 

  - Implement Javascript.
  - remove `src/fonts` and `dist/fonts` if they are going to be served via CDN or Google Fonts.
  - remove `fractal_components` once all the PL is migrated to Pattern Lab.
  - clean preprocess functions
  - move config.json to config folder
  - create config.local.json to override params if necessary.
  - ignore map files in .gitignore.

