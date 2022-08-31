/* eslint-disable no-console */
const { mkdirSync, writeFileSync } = require('fs')
const { bold, green, yellow, red, white } = require('chalk')

/**
 * This postcss plugin looks for purgecss messages generated by
 * @fullhuman/postcss-purgecss and, if found:
 *
 * 1. Outputs useful info to the console explaining what happened, such as the
 *    number of selectors purged from each file, or a note if the file was not
 *    processed by purgecss.
 *
 * 2. Writes a "{filename}.purged.json" alongside each CSS file that generated
 *    purge messages detailing the specific CSS selectors removed (if any).
 */
module.exports = function purgeCSSReporter (options) {
  const cwd = `${process.cwd()}/`
  mkdirSync('dist/css', { recursive: true })
  return {
    postcssPlugin: 'sfgovpl-purgecss-reporter',
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
          }
          else {
            console.log(yellow`🔥 purged ${white(bold(selectors.length))} un-referenced selectors from`, output)
            console.log(yellow`   see ${white(purgedPath)} for the full list`)
          }
        }
        else {
          console.log(`👍 ${green('no unused selectors purged from')}`, output)
        }
        // eslint-disable-next-line no-magic-numbers
        writeFileSync(purgedPath, JSON.stringify({ selectors }, null, 2), 'utf8')
      }
      else {
        console.log(green`🙈 purgecss did not process ${white(output)}`, `(source: ${input})`)
      }
    }
  }
}
