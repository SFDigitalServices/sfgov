(function ($, Drupal, drupalSettings) {
  // TODO: make this configurable!!
  const doNotTranslate = [
    'Mayor London Breed',
    'Mayor London N. Breed',
    'London Nicole Breed',
    'Mayor Breed',
    'London Breed',
    ' Breed'
  ].map(str => [
    str,
    new RegExp(`(?!<span class="notranslate>)${str}(?!</span>)`, 'g'),
    `<span class="notranslate">${str}</span>`
  ])

  Drupal.behaviors.excludeFromGtranslate = {
    attach (context) {
      for (const [str, find, replace] of doNotTranslate) {
        $('TITLE, P, A, SPAN, H1, H2, H3, H4, H5, H6, LI, div.field.__abstract, div.person-bio-summary', context)
          .filter(`*:contains(${str})`)
          .attr('translate', 'no')
      }
    }
  }

  window.SfGovExcludeFromTranslate = function (oldValue) {
    for (const [str, find, replace] of doNotTranslate) {
      if (oldValue.includes(str)) {
        oldValue = oldValue.replace(find, replace)
      }
    }
    return oldValue
  }
})(jQuery, Drupal, drupalSettings)
