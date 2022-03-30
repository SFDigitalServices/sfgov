# Contributing to SF.gov

Welcome, and thank you for contributing! This repo contains the source code that runs most of [SF.gov], the web site run by the City & County of San Francisco. These guidelines are intended to set expectations for development of the site and encourage high-quality contributions.

⚠️ **This repository does not contain any of the content for individual pages on SF.gov.** With the exception of common [user interface strings][ui strings], all content is written, translated, and/or managed by City staff and hosted in our content management system, [Drupal].

## Code quality
Our goal is to ensure a base level of code quality through code "linters": language-specific software that reads code files, checks them against agreed-upon formatting and structural rules, and reports any issues back to the author.

### Standards
We are in the process of setting up several mechanisms for code quality validation:

- [ ] We **will** have a [`CODEOWNERS` file][codeowners] to automate pull request reviews of specific files from the Digital Services team.
- [ ] We **will** run linters in as part of our [CI](#continuous-integration) workflow.
- [ ] We **will** use [git precommit hooks]() to run linters on our code before they're committed.
- [ ] We **will** suggest development tools that lint code in our [editors](#editor-support) whenever possible.

### PHP
PHP files are linted with [PHP CodeSniffer][] ("phpcs") and the [Drupal coding standards].

### Twig
[Twig] is Drupal's native templating language. We **will** lint Twig templates with [twig-lint](https://github.com/asm89/twig-lint).

### CSS
CSS source files (including Sass and SCSS formats) **will be** linted with [stylelint].

### JavaScript
JavaScript source files **will be** linted with [eslint] and the [sfgov preset][eslint-plugin-sfgov].

## Continuous integration
We run our continuous integration workflows on [CircleCI](). Our
[workflows](../.circleci) are based on [a template](https://github.com/pantheon-systems/example-drops-8-composer) provided by our hosting provider, [Pantheon].

## Editor support
Whenever possible, we will add and maintain editor configuration files that support local development, code quality and testing tools, and generally improve the developer experience. Our initial focus is on [VS Code], but we recognize that some PHP developers may prefer to use [PhpStorm] and hope to configure it similarly.

Pending the resolution of some billing issues, we will also investigate the use of [GitHub Codespaces][codespaces] to enable development without having to run the entire app environment locally.

## Best practices
We **encourage**, but do not yet enforce, the practices described in this section.

<!-- 
### Best practice title

A brief description of the best practice.

**Why**: Explain why this is important, useful, etc.
**How**: If necessary, explain implementation details, refactoring process, etc.
-->

### Use the design system
We have a [design system], and we should use it. Replacing the existing rat's nest of ad-hoc Sass files with the system's [Tailwind utilities] will have a ton of benefits, namely:

- Less CSS for users to download
- Simpler developer experience: one file (the template) to edit, rather than two (the template and the Sass)
- Fewer ad-hoc class naming conventions to manage

TL;DR: **Don't add custom CSS** if you can use the system's utility classes. There is no explicit documentation of the available utilities, but cross-referencing the [tailwind documentation][tailwind] with [our configuration](https://github.com/SFDigitalServices/design-system/blob/main/tailwind.config.js) should help. If you have any questions, visit the [#proj-design-system][design system slack] or message `@shawn` directly.


[codeowners]: https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/customizing-your-repository/about-code-owners
[codespaces]: https://github.com/features/codespaces
[design system]: https://design-system.sf.gov
[design system slack]: https://sfdigitalservices.slack.com/archives/CEERV5DCG
[drupal]: https://www.drupal.org/
[drupal coding standards]: https://www.drupal.org/docs/develop/standards/coding-standards
[eslint]: https://eslint.org/
[eslint-plugin-sfgov]: https://github.com/SFDigitalServices/eslint-plugin-sfgov
[pantheon]: https://pantheon.io/
[php codesniffer]: https://github.com/squizlabs/PHP_CodeSniffer
[phpstorm]: https://www.jetbrains.com/phpstorm/
[sf.gov]: https://sf.gov
[stylelint]: https://stylelint.io
[tailwind]: https://v2.tailwindcss.com/docs
[tailwind utilities]: https://v2.tailwindcss.com/docs/utility-first
[twig]: https://twig.symfony.com/
[ui strings]: https://www.drupal.org/project/string_translation_ui
[vs code]: https://code.visualstudio.com/
