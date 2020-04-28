# Color Field - Drupal 8

## ABOUT & FEATURES

### Formatters

  Plain text HEX code (#FFFFFF)
  Css Declaration (color/background-color)

### Widgets

- *Color Boxes*: Provides a row of colored boxes to select from a configured
list of colors. If enabled, opacity is a number field.
- *Color Default*: Textbox to put in a hex value. If enabled, opacity is a
number field.
- *Color Grid*: Uses
[jQuery Simple Color](https://github.com/recurser/jquery-simple-color)
to provide a pop up grid of color options. If enabled, opacity is a number
field.
- *Color HTML5*: Uses the color HTML5 input type to render in a browser/system
native manner. If enabled, opacity is a number field.
- *Color Spectrum*: Uses [Spectrum](https://github.com/bgrins/spectrum) to
provide a user friendly color palette to choose the correct color. This has an
integrated slider for opacity (if opacity is enabled).

## ROAD MAP

1) Make this module a base that could be used by any color picker.
2) include http://www.eyecon.ro/colorpicker/
3) include http://www.dematte.at/colorPicker/
4) include http://acko.net/blog/farbtastic-jquery-color-picker-plug-in/

## INSTALLATION

Install as you would normally install a contributed Drupal module. See also
[Core Docs](https://www.drupal.org/docs/8/extending-drupal-8/installing-modules)

### DEPENDENCIES
There are JavaScript libraries required for a couple of the field widgets. If
you are not actively using those field widgets, you can skip their installation
if desired.

#### COMPOSER
If you installed color field via [Composer](https://getcomposer.org), the
packages will have been suggested but not automatically installed. If you have
Asset Packagist already configured - as most Commerce users will - skip to just
requiring the desired package(s).
```bash
composer require bower-asset/jquery-simple-color bower-asset/spectrum
``` 

Otherwise, to install them you will need to add
[Asset Packagist](https://asset-packagist.org) to your composer.json and
do some and make a couple other changes to your `composer.json`. Specifically,
in the `extra` key add/adjust current values:
```json
"installer-types": [
  "npm-asset",
  "bower-asset"
],
"installer-paths": {
  "web/core": [
    "type:drupal-core"
  ],
  "web/libraries/{$name}": [
    "type:bower-asset",
    "type:npm-asset",
    "type:drupal-library"
  ],
  "web/modules/contrib/{$name}": [
    "type:drupal-module"
  ],
  "web/profiles/contrib/{$name}": [
    "type:drupal-profile"
  ],
  "web/themes/contrib/{$name}": [
    "type:drupal-theme"
  ],
  "drush/contrib/{$name}": [
    "type:drupal-drush"
  ]
},
```

then run
```bash
composer require oomphinc/composer-installers-extender
composer require bower-asset/jquery-simple-color bower-asset/spectrum
```

#### MANUAL
If you are not using Composer, you will need to manually install them.

- [jQuery Simple Color](https://github.com/recurser/jquery-simple-color)
copy to `/libraries/jquery-simple-color` so that `jquery.simple-color.min.js`
is in that folder. Required for the Color Grid widget.
- [Spectrum](https://github.com/bgrins/spectrum) copy to `/libraries/spectrum`
so that `spectrum.js` exists in that folder. Required for the Spectrum widget.

## USAGE

Field
1. Add the field to an node/entity
2. Select the 'Color Field' field type
3. Select the 'Color' widget you want

## DEVELOPMENT

To ease matching Drupal code standards, development dependencies are configured
to use a pre-commit hook. Install composer dev dependencies and PHPCS will be
automatically run if php is in your path.

## CREDIT

Original Creator: [targoo](https://www.drupal.org/u/targoo).

Maintainers:
  - [targoo](https://www.drupal.org/u/targoo)
  - [Nick Wilde](https://www.drupal.org/u/nickwilde)

Original development sponsored by Marique Calcus and written by Calcus David.
For professional support and development services contact targoo@gmail.com.

## More info

http://www.w3.org/TR/css3-color/#color
https://github.com/mikeemoo/ColorJizz-PHP
http://www.colorhexa.com/ff0000
https://github.com/PrimalPHP/Color/blob/master/lib/Primal/Color/Parser.php
https://github.com/matthewbaggett/php-color/blob/master/Color.php
