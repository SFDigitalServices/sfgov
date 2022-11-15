module.exports = {
  syntax: 'postcss-scss',
  plugins: [
    require('postcss-import')({
      filter: path => path.endsWith('.css')
    }),
    require('@csstools/postcss-sass')({
      outputStyle: 'expanded'
    })
  ]
}
