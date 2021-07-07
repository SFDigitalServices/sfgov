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

- `npm run develop` launches [BrowserSync] and `npm run watch-all` in parallel.
  Press <kbd>Control + C</kbd> to exit.
- `npm run build` builds the CSS and JavaScript assets to the `dist` directory
  and exits
- `npm run build-css` builds only the CSS assets
- `npm run build-js` builds only the JavaScript assets

These tasks are long-running, so you'll need to press <kbd>Control + C</kbd> to
quit them:

- `npm start` runs only [BrowserSync], assuming that you already have a server running with built assets
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
- `npm run lint-js` lints only JavaScript source files
- `npm run lint-fix` runs Prettier with the [`--write` option](https://prettier.io/docs/en/cli.html#--write) to fix any automatically fixable errors


[browsersync]: https://browsersync.io/
[design system]: https://github.com/SFDigitalServices/design-system
[node.js]: https://nodejs.org
[npm]: https://docs.npmjs.com/downloading-and-installing-node-js-and-npm
[npm scripts]: https://docs.npmjs.com/cli/v6/using-npm/scripts
[nvm]: https://github.com/nvm-sh/nvm
[nvm for windows]: https://github.com/coreybutler/nvm-windows
[pantheon build tools]: https://pantheon.io/docs/guides/build-tools/
[pantheon node version]: https://quay.io/repository/pantheon-public/build-tools-ci/manifest/sha256:7288b1a1c30babb4e02446fd843e679c6c5807a3095df4746030cdc316ca5ad3#:~:text=NODE_VERSION%3D
[prettier]: https://prettier.io
