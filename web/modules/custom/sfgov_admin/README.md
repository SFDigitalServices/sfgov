# SF.gov Admin

This module contains customizations to the Seven theme/administrative UI, mostly centered around improving the UX for and Node edit and Paragraphs forms. It contains a Drupal library that references CSS and JavaScript that load on all administration pages. It also utilizes Field Group module to wrap form elements and inject custom CSS classes where needed. See the Form Display of the "Transaction" content type for a good example, as it was the most heavily customized.

While none of this is _easily_ maintainable due to the nature of Drupal core and contributed modules being a moving target, one of the goals was to try to keep this code as maintainable as possible. A sub-theme of Seven could have been created, but this was decided against for a number of reasons. A sub-theme involves a lot more code/overhead, and since Seven is not designed to be a base theme, breaking changes could occur at any time. Additionally, the types of changes we made here technically required a module. See [SG-61](https://sfgovdt.jira.com/browse/SG-61) for more details, original tasks, and supporting designs, which detail this work.

## Prerequisites

These instructions assume you've installed the site using the instructions in the main [README](../../../../README.md), as its necessary to install Composer dependencies, and have your Lando instance up and running first. Additionally, you will need to ensure [Node.js and NPM](https://docs.npmjs.com/downloading-and-installing-node-js-and-npm) is installed.

## Installation

Install the Gulp CLI globally, and local NPM dependencies:

```bash
cd <repo-root>/web/modules/custom/sfgovpl_admin/build
npm install -g gulp-cli
npm install
```

## Development Build

The build script uses Gulp 4 to compile Sass and JavaScript (with Babel), and uses BrowserSync to watch for changes and reload.

The start the build script, navigate to the `./build` of this module in your terminal and run:

```bash
gulp
```
