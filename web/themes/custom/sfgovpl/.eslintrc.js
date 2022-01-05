module.exports = {
  root: true,
  plugins: ['sfgov'],
  extends: [
    'plugin:sfgov/recommended'
  ],
  rules: {
    'comma-dangle': 'error',
    'newline-per-chained-call': ['warn', { ignoreChainWithDepth: 3 }],
    'no-magic-numbers': ['warn', { ignore: [0, 1] }],
    'no-console': 'warn',
    'semi': 'error',
    // we don't need "use strict" directives: @babel/preset-env adds them automatically
    strict: ['error', 'never']
  }
}
