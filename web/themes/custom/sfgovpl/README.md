# SF.gov theme

This is the Drupal theme running on the front-end of SF.gov. It contains its
own CSS and JavaScript source files, and includes assets from the [sf.gov
design system][design system] via [npm].

_Note: See [`composer.json`](../../../../composer.json) for specific branch/release details._

## Prerequisites

These instructions assume you've installed the site using the instructions in
the main [README](../../../../README.md), as its necessary to install Composer
dependencies first. Additionally, you will need:

- [Node.js] version 10 or greater
- [npm] version 6 or greater

**Note**: the version of [Pantheon build tools] that we use on Circle CI
[runs][pantheon node version] Node version
[14.16.1](https://nodejs.org/ja/blog/release/v14.16.1/). While older version of
Node _should_ work, it's good practice to run the same version of Node locally.
[nvm] is a great tool for managing multiple installations of Node on macOS,
Linux, and [Windows][nvm for windows].

## Installation

Install NPM dependencies for this theme by navigating to this directory
(`web/themes/custom/sfgovpl`), then running:

```sh
npm install
```

## Development Build

There are several [npm scripts] defined in [`package.json`](./package.json) for
running different tasks. The ones you're most likely to run directly are:

- `npm start` launches [BrowserSync] and `npm run watch-all` in parallel.
  Press <kbd>Control + C</kbd> to exit.
- `npm run build` builds the CSS and JavaScript assets to the `dist` directory
  and exits
- `npm run build-css` builds only the CSS assets, removes unused selectors via PurgeCSS ([see notes below](#purgecss))
- `npm run build-js` builds only the JavaScript assets

These tasks are long-running, so you'll need to press <kbd>Control + C</kbd> to
quit them:

- `npm run browser-sync` runs only [BrowserSync], assuming that you already have a server running with built assets
- `npm run watch` watches both the (S)CSS and JavaScript source files and rebuilds when they change
- `npm run watch-css` watches only the (S)CSS source files and rebuilds when they change
- `npm run watch-js` watches only the JavaScript source files and rebuilds when they change

When in doubt, run `npm run` to see a list of the available scripts.

## Linting

To lint the CSS and JavaScript source files with [Prettier], run:

```sh
npm run lint
```

There are additional npm scripts for different linting tasks:

- `npm run lint-css` lints only (S)CSS source files
- `npm run lint-js` lints only JavaScript source files using a custom [eslint-plugin-sfgov] configuration
- `npm run lint-fix` runs Prettier with the [`--write` option](https://prettier.io/docs/en/cli.html#--write) to fix any automatically fixable errors

## PurgeCSS

PurgeCSS is used in our CSS build process to "purge" unused selectors from our stylesheets. Here's how it works:

- Our PurgeCSS config lives in [purgecss.config.js] so that we can use the same options for both the CLI and the postcss plugin. There are two options that do all the work for us:
    1. `rejected: true` tells PurgeCSS to collect and report information about the list of rejected ("purged") CSS when it runs
    2. `content: [...]` lists paths (mostly globs) where it should look for HTML elements, attributes, and any "token" that looks like a class name. Currently I have this set to pull in:
        * `templates/**/*.{twig,html}` to match all templates in this Drupal theme
        * `src/**/*.js` to match class names that we may use to either query or add/remove in our JavaScript
        * `../../../modules/custom/**/*.{html,inc,php,theme,twig}` to match files that might contain classes in any of our custom modules
- [postcss-purgecss]() takes the options and from our config, reads all of the files in the `content` globs listed above, and "rejects" selectors that don't match anything found in the content
- A [custom postcss reporter][purge reporter] takes the rejected messages for each file, generates a `{filename}.css.purged.json` alongside each `{filename}.css`, and outputs some useful info to the console

### Testing
This is a combination of gut checking the rejected selector lists and QA, but in a nutshell: We need to make sure that this isn't rejecting the wrong selectors.

#### Disabling PurgeCSS
If it looks like PurgeCSS is over-aggressively purging selectors that _are_ used, we'll need to do one or both of the following:

1. Add [`purgecess ignore` comments](https://purgecss.com/safelisting.html#in-the-css-directly) to CSS that we want to prevent from being purged. You can run [scripts/add-purgecss-comments](https://github.com/SFDigitalServices/sfgov/blob/100d10a635792416804dc627a1215013c00d95e3/web/themes/custom/sfgovpl/scripts/add-purgecss-comments) on one or more files to safe-list them entirely:

    ```sh
    scripts/add-purgecss-comments path/to/foo.scss path/to/bar.scss
    ```

2. Add [safe lists](https://purgecss.com/safelisting.html) to our PurgeCSS config to prevent specific selectors from being purged.


[browsersync]: https://browsersync.io/
[design system]: https://github.com/SFDigitalServices/design-system
[eslint-plugin-sfgov]: https://github.com/SFDigitalServices/eslint-plugin-sfgov
[node.js]: https://nodejs.org
[npm]: https://docs.npmjs.com/downloading-and-installing-node-js-and-npm
[npm scripts]: https://docs.npmjs.com/cli/v6/using-npm/scripts
[nvm]: https://github.com/nvm-sh/nvm
[nvm for windows]: https://github.com/coreybutler/nvm-windows
[pantheon build tools]: https://pantheon.io/docs/guides/build-tools/
[pantheon node version]: https://quay.io/repository/pantheon-public/build-tools-ci/manifest/sha256:7288b1a1c30babb4e02446fd843e679c6c5807a3095df4746030cdc316ca5ad3#:~:text=NODE_VERSION%3D
[prettier]: https://prettier.io
[purgecss]: https://purgecss.com/
[postcss-purgecss]: https://github.com/FullHuman/purgecss/tree/master/packages/postcss-purgecss#readme
[postcss-import]: https://github.com/postcss/postcss-import
[purge reporter]: https://github.com/SFDigitalServices/sfgov/blob/develop/web/themes/custom/sfgovpl/lib/postcss/purgecss-reporter.js
[purgecss.config.js]: https://github.com/SFDigitalServices/sfgov/blob/develop/web/themes/custom/sfgovpl/purgecss.config.js
