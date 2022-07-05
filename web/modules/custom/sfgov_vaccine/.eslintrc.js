/** @type {import('eslint').ESLint.ConfigData} */
module.exports = {
  extends: [
    require.resolve('../../../../.eslintrc')
  ],
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
