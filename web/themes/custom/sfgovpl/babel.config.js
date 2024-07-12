const PROD = process.env.NODE_ENV === 'production'
const plugins = PROD
  ? [
      ['transform-remove-console', { exclude: ['warn', 'error'] }]
    ]
  : []

module.exports = {
  extends: '../../../../babel.config.js',
  comments: false,
  plugins
}
