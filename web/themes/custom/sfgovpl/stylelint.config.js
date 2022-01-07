module.exports = {
  customSyntax: require('postcss-scss'),
  extends: [
    'stylelint-config-standard',
    'stylelint-config-standard-scss'
  ],
  rules: {
    'color-function-notation': null, // FIXME: 'modern'
    'alpha-value-notation': null, // FIXME: 'percentage'
    'no-descending-specificity': [true, { severity: 'warning' }],
    'scss/double-slash-comment-whitespace-inside': ['always', { severity: 'warning' }],
    'string-quotes': 'single'
  }
}