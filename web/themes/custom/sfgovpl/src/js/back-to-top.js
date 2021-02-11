/**
 * Back to top button. Functionality:
 * - Appear only if the user starts to scroll up
 * - Remain sticky at the bottom right if the user continues to scroll up
 * - Appear only after 700px
 * - Disappear if the user starts to scroll down
 */
;(function ($, Drupal) {
  Drupal.behaviors.backToTop = {
    attach: function (context) {
      const top = document.getElementById('back-to-top')
      const $win = $(window, context)
      const $root = $('html, body', context)

      top.addEventListener('click', function (e) {
        e.preventDefault()
        $root.animate({ scrollTop: 0 }, '300')
      })

      $win.on('load', function () {
        let then = 0
        let now = 0
        $win
          .once('scroll')
          .on('scroll', function () {
            now = $win.scrollTop()
            if (then > now && now > 700) {
              top.classList.add('show')
            } else {
              top.classList.remove('show')
            }
            then = now
          })
      })
    }
  }
})(jQuery, Drupal)
