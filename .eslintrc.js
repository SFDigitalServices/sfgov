/** @type {import('eslint').ESLint.ConfigData} */
module.exports = {
  root: true,
  plugins: [
    'sfgov'
  ],
  extends: [
    'plugin:sfgov/babel',
    'plugin:sfgov/recommended'
  ],
  globals: {
    drupalSettings: 'readonly',
    Drupal: 'readonly',
    jQuery: 'readonly'
  },
  rules: {
    'arrow-parens': ['warn', 'as-needed', {
      requireForBlockBody: false
    }],
    'brace-style': ['warn', 'stroustrup'],
    camelcase: 'warn',
    'comma-dangle': 'warn',
    curly: ['warn', 'all'],
    eqeqeq: ['error', 'smart'],
    'newline-per-chained-call': ['warn', {
      ignoreChainWithDepth: 3
    }],
    'no-magic-numbers': ['warn', {
      ignore: [0, 1]
    }],
    'no-console': 'warn',
    'object-shorthand': ['warn', 'always', {
      avoidExplicitReturnArrows: true
    }],
    'prefer-arrow-callback': 'warn',
    'promise/always-return': 0,
    'promise/catch-or-return': 0,
    semi: 'error',
    strict: ['error', 'never']
  }
}
