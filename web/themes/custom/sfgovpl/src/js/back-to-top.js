/**
 * Back to top button. Functionality:
 * - Appear only if the user starts to scroll up
 * - Remain sticky at the bottom right if the user continues to scroll up
 * - Appear only after 700px
 * - Disappear if the user starts to scroll down
 */
(function ($, Drupal) {
  Drupal.behaviors.backToTop = {
    attach: function (context) {
      var buttonText = Drupal.t('Back to top');
      $('body', context).once('backToTop').append('<a id="back-to-top" class="back-to-top">' + buttonText + '<span /></a>');
      $('#back-to-top', context).on('click', function(e) {
        e.preventDefault();
        $('html, body', context).animate({scrollTop: 0}, '300');
      });

      $(window, context).on('load', function() {
        var then = 0;
        var now = 0;
        $(window, context).once('scroll').on('scroll', function() {
          now = $(window, context).scrollTop();
          if (then > now && now > 700) {
            $('#back-to-top').addClass('show');
          }
          else {
            $('#back-to-top').removeClass('show');
          }
          then = now;
        });
      });
    }
  };
})(jQuery, Drupal);
