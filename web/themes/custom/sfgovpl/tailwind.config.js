/** @type {import('tailwindcss/types').Config} */

const { content } = require('./purgecss.config')

module.exports = {
  presets: [
    require('sfgov-design-system/tailwind.preset')
  ],
  corePlugins: {
    order: true
  },
  variants: {
    extend: {
      backgroundColor: ['odd']
    }
  },
  content
}
