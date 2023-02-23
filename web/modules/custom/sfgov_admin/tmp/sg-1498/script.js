(function ($, Drupal) {
  $(document).ready(() => {
    $('details .item-list ul').each(function () {
      const element = $(this)
      const parent = element.parent()

      element.append('<div class="load-more-wrapper"><div class="loadMore">' + Drupal.t('Load more') + '</div><div class="showLess">' + Drupal.t('Show less') + '</div></div>')

      const sizeLi = $('li', element).length
      let x = 2
      $('li:lt(' + x + ')', element).show()
      $('.loadMore', parent).click(() => {
        x = (x + 5 <= sizeLi) ? x + 5 : sizeLi // eslint-disable-line
        element.children('li:lt(' + x + ')').show()
      })
      $('.showLess', parent).click(() => {
        x = (x - 5 < 0) ? 2 : x - 5 // eslint-disable-line
        $('li', element).not(':lt(' + x + ')').hide()
      })
    })
  })
})(jQuery, Drupal)
