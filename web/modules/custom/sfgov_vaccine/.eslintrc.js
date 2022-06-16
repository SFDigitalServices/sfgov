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
    eqeqeq: 'warn',
    'no-magic-numbers': 0,
    'no-unused-vars': 'warn'
  }
}
