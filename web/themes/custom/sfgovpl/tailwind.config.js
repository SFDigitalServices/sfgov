const { content, safelist } = require('./purgecss.config')

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
  safelist: safelist.greedy.map(pattern => {
    return { pattern }
  })
}
