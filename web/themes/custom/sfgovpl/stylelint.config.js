module.exports = {
  customSyntax: require('postcss-scss'),
  extends: [
    'stylelint-config-standard',
    'stylelint-config-standard-scss'
  ],
  rules: {
    'alpha-value-notation': null, // FIXME: 'percentage'
    'color-function-notation': null, // FIXME: 'modern'
    'declaration-block-no-redundant-longhand-properties': [true, { severity: 'warning' }],
    'function-url-quotes': ['always', { severity: 'warning' }],
    'no-descending-specificity': [true, { severity: 'warning' }],
    'number-max-precision': [4, { severity: 'warning' }],
    'scss/comment-no-empty': null,
    'scss/double-slash-comment-whitespace-inside': ['always', { severity: 'warning' }],
    'scss/no-global-function-names': null,
    'selector-class-pattern': null,
    'selector-pseudo-class-no-unknown': [true, { ignorePseudoClasses: ['horizontal', 'vertical']}],
    'string-quotes': 'single'
  }
}
