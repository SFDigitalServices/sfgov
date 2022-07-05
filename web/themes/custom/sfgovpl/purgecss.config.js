module.exports = {
  rejected: true,
  content: [
    'templates/**/*.{twig,html}',
    'src/**/*.js',
    '../../../modules/custom/**/*.{html,inc,js,php,theme,twig}',
    '../../../../patches/**/*.patch'
  ],
  defaultExtractor: content => content.match(/[\w-:./]+(?<!:)/g) || [],
  safelist: {
    greedy: [
      // preserve all "basic" margin and padding utilities (for forms)
      /\b[mp][trblxy]?-\d+/,
      // background color utilities
      /bg-(black|white|slate|blue|green|red|purple|yellow|grey)/,
      // text color utilities
      /text-(black|white|slate|blue|green|red|purple|yellow|grey|secondary)/
    ]
  }
}
