/**
 * Back to top button. Functionality:
 * - Appear only if the user starts to scroll up
 * - Remain sticky at the bottom right if the user continues to scroll up
 * - Appear only after 700px
 * - Disappear if the user starts to scroll down
 */
(function ($, Drupal) {
  Drupal.behaviors.backToTop = {
    attach (context) {
      $('#back-to-top', context).on('click', e => {
        e.preventDefault()
        $('html, body', context).animate({ scrollTop: 0 }, '300')
      })

      $(window, context).on('load', () => {
        let then = 0
        let now = 0
        $(window, context).once('scroll').on('scroll', () => {
          now = $(window, context).scrollTop()
          if (then > now && now > 700) {
            $('#back-to-top').addClass('show')
          }
          else {
            $('#back-to-top').removeClass('show')
          }
          then = now
        })
      })
    }
  }
})(jQuery, Drupal)
