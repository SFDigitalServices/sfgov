# SF.gov Pattern Lab Theme

`sfgovpl` is the Drupal theme running on the front-end of SF.gov. It consumes assets from two separate design systems, via Composer and Drupal libraries defined in `sfgovpl.libraries.yml`:

| Project | Location | Status | Details |
| :---    | :---     |  :---  | :---    |
| <nobr> [SF.gov Pattern Lab](https://github.com/SFDigitalServices/sfgov-pattern-lab)</nobr> | `web/libraries/sfgov-pattern-lab` | Inactive | Currently being phased out. However, this theme still consumes its Twig templates, and utilizes its CSS, Sass, and NPM dependencies. |
| <nobr> [SF Design System](https://github.com/SFDigitalServices/sf-design-system)</nobr> | `web/libraries/sf-design-system` | Active | Under active development. This theme consumes its compiled CSS. |

_Note: Check `composer.json` in the repository root to get specific branch/release details._

## Prerequisites

These instructions assume you've installed the site using the instructions in the main [README](../../../../README.md), as its necessary to install Composer dependencies first. Additionally, you will need:

- [Node.js](https://nodejs.org) >= v10.3.0
- [NPM](https://docs.npmjs.com/downloading-and-installing-node-js-and-npm) >=6.1.0

## Installation

1. Install NPM dependencies for Pattern Lab:

    ```bash
    cd <repo-root>/web/libraries/sfgov-pattern-lab/
    npm install
    ```

    _Note: If you need to make changes to the Pattern Lab (which should be rare), you will need to make them in its [repository](https://github.com/SFDigitalServices/sfgov-pattern-lab), and get them merged into the `retheme` branch._

2. Install NPM dependencies for this theme:

    ```bash
    cd <repo-root>/web/themes/custom/sfgovpl
    npm install
    ```

## Development Build

The build script uses Gulp 4 to compile Sass, and BrowserSync to watch for changes and reload. _Note: JavaScript is NOT processed by the build script_.

The start the build script, navigate to the root of this theme in your terminal and run:

```bash
gulp
```
