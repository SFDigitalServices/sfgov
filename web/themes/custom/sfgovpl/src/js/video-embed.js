(function ($, Drupal) {
  Drupal.behaviors.video_embed = {
    attach (context, settings) {
      $('.toggle-transcript', context).click(function (e) {
        e.preventDefault()
        $(this)
          .closest('.video-embed-component')
          .toggleClass('js-opened')
        $(this)
          .find('span')
          .toggleClass('is-hidden')
      })
    }
  }
})(jQuery, Drupal)
