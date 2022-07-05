/** @type {import('eslint').ESLint.ConfigData} */
module.exports = {
  extends: '../../../../.eslintrc.js',
  env: {
    jquery: true
  },
  ignorePatterns: [
    'js/amplitude-init.js'
  ],
  globals: {
    google: 'readonly',
    amplitude: 'readonly'
  },
  rules: {
    'no-magic-numbers': 0,
    'no-unused-vars': 'warn'
  }
}
