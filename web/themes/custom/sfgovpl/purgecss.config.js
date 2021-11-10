module.exports = {
  rejected: true,
  content: [
    'templates/**/*.{twig,html}',
    'src/**/*.js',
    '../../../modules/custom/**/*.{html,inc,js,php,theme,twig}'
  ],
  safelist: {
    greedy: [
      // preserve all "basic" margin and padding utilities (for forms)
      /\b[mp][trblxy]?-\d+/
    ]
  }
}
