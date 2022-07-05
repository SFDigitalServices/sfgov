/** @type {import('eslint').ESLint.ConfigData} */
module.exports = {
  extends: '../../../../.eslintrc.js',
  env: {
    jquery: true
  },
  globals: {
    google: 'readonly'
  },
  rules: {
    'no-magic-numbers': 0,
    'no-unused-vars': 'warn'
  }
}
