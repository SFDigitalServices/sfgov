module.exports = {
  rejected: true,
  content: [
    'templates/**/*.{twig,html}',
    'src/**/*.js',
    '../../../modules/custom/**/*.{html,inc,js,php,theme,twig}'
  ],
  safelist: {
    patterns: [
      // preserve all "basic" margin and padding utilities (for forms)
      /[mp][trblxy]*-\d+/
    ]
  }
}
