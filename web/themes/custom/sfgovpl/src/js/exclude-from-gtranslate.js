(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.excludeFromGtranslate = {
    attach (context) {
      const do_not_translate = [
        'Mayor London Breed',
        'Mayor London N. Breed',
        'London Nicole Breed',
        'Mayor Breed',
        'London Breed',
        'Breed'
      ]

      $.each(do_not_translate, (index, value) => {
        $('P, A, SPAN, H1, H2, H3, H4, H5, H6, LI, div.field.__abstract, div.person-subtitle', context)
          .filter('*:contains(' + value + ')')
          .html((_, html) => {
            const regex = new RegExp('(?!<span class="notranslate\>)' + value + '(?!<\/span>)', 'g')
            return html.replace(regex, '<span class="notranslate">' + value + '</span>')
          })
      })
    }
  }
})(jQuery, Drupal, drupalSettings)

function SfGovExcludeFromTranslate (old_value) {
  const do_not_translate = [
    'Mayor London Breed',
    'Mayor London N. Breed',
    'London Nicole Breed',
    'Mayor Breed',
    'London Breed',
    'Breed'
  ]

  do_not_translate.forEach(value => {
    if (old_value.includes(value)) {
      const regex = new RegExp('(?!<span class="notranslate\>)' + value + '(?!<\/span>)', 'g')
      old_value = old_value.replace(regex, '<span class="notranslate">' + value + '</span>')
    }
  })

  return old_value
}
