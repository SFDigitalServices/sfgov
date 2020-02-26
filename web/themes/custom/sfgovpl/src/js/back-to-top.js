(function ($, Drupal) {
  Drupal.behaviors.backToTop = {
    attach: function (context) {
      var buttonText = Drupal.t('Back to top');
      $('body', context).once('backToTop').append('<a id="back-to-top" class="back-to-top">' + buttonText + '<span /></a>');
      var button = $('#back-to-top');
      $(window).scroll(function() {
        if ($(window).scrollTop() > 1000) {
          button.addClass('show');
        }
        else {
          button.removeClass('show');
        }
      });
      button.on('click', context, function(e) {
        e.preventDefault();
        $('html, body', context).animate({scrollTop: 0}, '300');
      });
    }
  };
})(jQuery, Drupal);
