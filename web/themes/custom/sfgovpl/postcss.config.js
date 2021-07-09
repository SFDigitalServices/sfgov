module.exports = {
  syntax: 'postcss-scss',
  plugins: [
    require('postcss-node-sass')({
      outputStyle: 'nested'
    }),
    require('autoprefixer')
  ]
}
