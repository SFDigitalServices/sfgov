module.exports = {
  parser: '@babel/eslint-parser',
  extends: [
    'plugin:sfgov/babel'
  ],
  env: {
    browser: true,
    jquery: true
  },
  globals: {
    drupalSettings: true,
    Drupal: true
  }
}
