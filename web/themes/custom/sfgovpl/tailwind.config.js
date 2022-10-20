const { content, ...purgeOptions } = require('./purgecss.config')

/** @type {import('tailwindcss/types').Config} */
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
  content,
  purge: {
    options: purgeOptions
  }
}
