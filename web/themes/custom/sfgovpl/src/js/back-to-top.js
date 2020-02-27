/**
 * Back to top button. Functionality:
 * - Appear only if the user starts to scroll down
 * - Remain sticky at the bottom right if the user continues to scroll down
 * - Appear only after 1000px
 * - Disappear if the user starts to scroll up
 * - Disappear once the page hits the height of 1000px
 */
(function ($, Drupal) {
  Drupal.behaviors.backToTop = {
    attach: function (context) {
      var buttonText = Drupal.t('Back to top');
      $('body', context).once('backToTop').append('<a id="back-to-top" class="back-to-top">' + buttonText + '<span /></a>');
      var then = 0;
      var now = 0;
      $(window, context).once('scroll').on('scroll', function() {
        now = $(window, context).scrollTop();
        if (now > then && now > 1000) {
          $('#back-to-top', context).addClass('show');
        }
        else {
          $('#back-to-top', context).removeClass('show');
        }
        then = now;
      });
      $('#back-to-top', context).on('click', function(e) {
        e.preventDefault();
        $('html, body', context).animate({scrollTop: 0}, '300');
      });
    }
  };
})(jQuery, Drupal);
