# PHP: HTML Heading Normalizer
PHP class for normalizing (promote/demote) HTML headings.

[![Build Status](https://travis-ci.org/vikpe/php-html-heading-normalizer.svg?branch=master)](https://travis-ci.org/vikpe/php-html-heading-normalizer)
[![Test Coverage](https://codeclimate.com/github/vikpe/php-html-heading-normalizer/badges/coverage.svg)](https://codeclimate.com/github/vikpe/php-html-heading-normalizer/coverage)
[![Code Climate](https://codeclimate.com/github/vikpe/php-html-heading-normalizer/badges/gpa.svg)](https://codeclimate.com/github/vikpe/php-html-heading-normalizer)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vikpe/php-html-heading-normalizer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/vikpe/php-html-heading-normalizer/?branch=master)
[![StyleCI](https://styleci.io/repos/77139895/shield?branch=master)](https://styleci.io/repos/77139895)

## Installation
```
composer require vikpe/php-html-heading-normalizer
```

## Methods
### promote(string $html, int $numberOfLevels)
Promotes all headings in `$html` by `$numberOfLevels` levels.

```php
\Vikpe\HtmlHeadingNormalizer::promote('<h6>Foo</h6>', 3); // '<h3>Foo</h3>'
```

### demote(string $html, int $numberOfLevels)
Demotes all headings in `$html` by `$numberOfLevels` levels.
```php
\Vikpe\HtmlHeadingNormalizer::demote('<h1>Foo</h1>', 1); // '<h2>Foo</h2>'
```

### min(string $html, int $minLevel)
Adjusts all headings in `$html` so that the lowest heading level equals `$minLevel`.
```php
\Vikpe\HtmlHeadingNormalizer::min('<h4>Foo</h4>', 1); // '<h1>Foo</h1>'
```
