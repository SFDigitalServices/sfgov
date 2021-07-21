module.exports = {
  syntax: 'postcss-scss',
  plugins: [
    require('postcss-node-sass')({
      sass: require('node-sass'),
      outputStyle: 'nested'
    }),
    require('autoprefixer')
  ]
}
