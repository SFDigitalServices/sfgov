const purgeConfig = require('./purgecss.config')
const purgeReporter = require('./lib/postcss/purgecss-reporter')

module.exports = {
  syntax: 'postcss-scss',
  plugins: [
    require('postcss-import')({
      filter: path => path.endsWith('.css')
    }),
    require('postcss-node-sass')({
      sass: require('node-sass'),
      outputStyle: 'nested'
    }),
    require('@fullhuman/postcss-purgecss')(purgeConfig),
    require('autoprefixer'),
    purgeReporter()
  ]
}
