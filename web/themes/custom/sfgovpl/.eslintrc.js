module.exports = {
  root: true,
  plugins: ['sfgov'],
  extends: [
    'plugin:sfgov/recommended'
  ],
  rules: {
    'arrow-parens': ['warn', 'as-needed', { requireForBlockBody: true }],
    camelcase: 'warn',
    'comma-dangle': 'warn',
    eqeqeq: ['error', 'smart'],
    'newline-per-chained-call': ['warn', { ignoreChainWithDepth: 3 }],
    'no-magic-numbers': ['warn', { ignore: [0, 1] }],
    'no-console': 'warn',
    'object-shorthand': ['warn', 'always', { avoidExplicitReturnArrows: true }],
    'prefer-arrow-callback': 'warn',
    'promise/always-return': 'warn',
    'promise/catch-or-return': 'warn',
    semi: 'error',
    // we don't need "use strict" directives: @babel/preset-env adds them automatically
    strict: ['error', 'never']
  }
}
