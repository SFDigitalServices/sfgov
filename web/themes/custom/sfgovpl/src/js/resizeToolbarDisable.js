(function ($) {
  Drupal.behaviors.dieToolbardie = {
    attach (context) {
      window.matchMedia('(min-width: 975px)').addListener(event => {
        event.matches ? $('#toolbar-item-administration', context).click() : $('.toolbar-item.is-active', context).click()
      })
    }
  }
})(jQuery)
