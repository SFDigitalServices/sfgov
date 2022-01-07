module.exports = {
  customSyntax: require('postcss-scss'),
  extends: [
    'stylelint-config-standard',
    'stylelint-config-standard-scss'
  ],
  rules: {
    // FIXME: 'percentage'?
    'alpha-value-notation': null,
    // FIXME: 'modern'?
    'color-function-notation': null,
    // FIXME: promote this to an error once fixed
    'declaration-block-no-redundant-longhand-properties': [true, { severity: 'warning' }],
    // FIXME: promote this to an error once fixed
    'function-url-quotes': ['always', { severity: 'warning' }],
    // FIXME: promote this to an error once fixed
    'no-descending-specificity': [true, { severity: 'warning' }],
    // FIXME: promote this to an error once fixed
    'no-duplicate-selectors': [true, { severity: 'warning' }],
    // FIXME: set this to a number that's significant and promote to error
    'number-max-precision': [4, { severity: 'warning' }],
    // because we have periods in some of our partial filenames (before the final .scss)...
    'scss/at-import-partial-extension': null,
    // we don't care about the naming of our mixins
    'scss/at-mixin-pattern': null,
    // some comment blocks include empty "//" lines
    'scss/comment-no-empty': null,
    // we don't care about variable names for now
    'scss/dollar-variable-pattern': null,
    // we don't care about newlines before inline comments
    'scss/double-slash-comment-empty-line-before': null,
    'scss/double-slash-comment-whitespace-inside': null,
    // we're not using modern Sass (yet), so this is irrelevant
    'scss/no-global-function-names': null,
    // we don't (yet) enforce class naming conventions
    'selector-class-pattern': null,
    // some "unknown" pseudo-classes are for visible scrollbars
    'selector-pseudo-class-no-unknown': [true, {
      ignorePseudoClasses: ['horizontal', 'vertical']
    }],
    'string-quotes': 'single'
  }
}
