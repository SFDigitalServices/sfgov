# Contributing to SF.gov

Welcome, and thank you for contributing! This repo contains the source code that runs most of [SF.gov], the web site run by the City & County of San Francisco. These guidelines are intended to set expectations for development of the site and encourage high-quality contributions.

⚠️ **This repository does not contain any of the content for individual pages on SF.gov.** With the exception of common [user interface strings][ui strings], all content is written, translated, and/or managed by City staff and hosted in our content management system, [Drupal].

## Code quality
Our goal is to ensure a base level of code quality through code "linters": language-specific software that reads code files, checks them against agreed-upon formatting and structural rules, and reports any issues back to the author.

### Enforcement
We are in the process of setting up several mechanisms for code quality enforcement:

- We **will** have a [`CODEOWNERS` file][codeowners] to automate pull request reviews of specific files from the Digital Services team.
- We **will** run linters in as part of our [CI](#continuous-integration) workflow.
- We **will** suggest development tools that lint code in editors whenever possible.
- We **will** use [git precommit hooks]() to run linters on our code before they're committed.

### PHP
PHP files are linted with [PHP CodeSniffer] and the [Drupal coding standards].

### CSS
CSS source files (including Sass and SCSS formats) **will be** linted with [stylelint].

### JavaScript
JavaScript source files linted with [eslint] and the [sfgov preset][eslint-plugin-sfgov].

## Best practices
We encourage, but do not yet enforce, the practices described in this section.

<!-- 
### Best practice title

A brief description of the best practice.

**Why**: Explain why this is important, useful, etc.
**How**: If necessary, explain implementation details, refactoring process, etc.
-->


[codeowners]: https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/customizing-your-repository/about-code-owners
[drupal]: https://www.drupal.org/
[drupal coding standards]: https://www.drupal.org/docs/develop/standards/coding-standards
[eslint]: https://eslint.org/
[eslint-plugin-sfgov]: https://github.com/SFDigitalServices/eslint-plugin-sfgov
[php codesniffer]: https://github.com/squizlabs/PHP_CodeSniffer
[sf.gov]: https://sf.gov
[stylelint]: https://stylelint.io
[ui strings]: https://www.drupal.org/project/string_translation_ui
