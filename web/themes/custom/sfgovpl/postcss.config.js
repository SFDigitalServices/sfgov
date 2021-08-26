const purgeConfig = require('./purgecss.config')
const { mkdirSync, writeFileSync } = require('fs')
const { basename } = require('path')
const { bold, green, yellow, red, white } = require('chalk')

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
    reporter(),
  ]
}

function reporter (options) {
  const cwd = `${process.cwd()}/`
  mkdirSync('dist/css', { recursive: true })
  return {
    postcssPlugin: 'sfgovpl-reporter',
    OnceExit (root, { result }) {
      const [message] = result.messages.filter(({ type }) => type === 'purgecss')
      const input = result.root.source.input.file.replace(cwd, '')
      const output = result.opts.to.replace(cwd, '')
      if (message) {
        const purgedPath = `${output}.purged.json`
        const selectors = message.text
          .split(/[\r\n]+/)
          .slice(1)
          .map(line => line.trim())
        const empty = root.nodes.length === 0
        if (selectors.length) {
          if (empty) {
            console.warn(red`⚠️  ${bold('all CSS has been purged')} (${selectors.length} un-referenced selectors) from`, output)
            console.warn(red`   you either don't need this file anymore, or you should`)
            console.warn(red`   exclude it from PurgeCSS with \`${white`scripts/add-purgecss-comments ${input}`}\``)
          } else {
            console.log(yellow`🔥 purged ${white(bold(selectors.length))} un-referenced selectors from`, output)
            console.log(yellow`   see ${white(purgedPath)} for the full list`)
          }
        } else {
          console.log(`👍 ${green('no unused selectors purged from')}`, output)
        }
        writeFileSync(purgedPath, JSON.stringify({ selectors }, null, 2), 'utf8')
      } else {
        console.log(green`🙈 purgecss did not process ${white(output)}`, `(source: ${input})`)
      }
    }
  }
}
