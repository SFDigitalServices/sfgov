(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.paragraph_video = {
    attach: function (context, settings) {
      $('.toggle-transcript', context).click(function(e) {
        e.preventDefault();
        $(this).closest('.paragraph--type--video').toggleClass('js-opened');
        $(this).find('span').toggleClass('is-hidden');
      });
    }
  }
})(jQuery, Drupal);
