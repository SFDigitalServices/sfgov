(function($, Drupal) {
  'use strict';

  Drupal.behaviors.multipleSelect = {
    attach: function(context, settings) {
      $('select[data-multiple-select]')
        .once('multiple-select')
        .multipleSelect();

      $('body').on('DOMSubtreeModified', '.ms-choice span', function() {
        if ($(this).html() == 'All selected') {
          $(this).html('All committees selected');
        }
      });

      if ($('.ms-choice span').html() == 'All selected') {
        $('.ms-choice span').html('All committees selected');
      }
    },
  };
})(jQuery, Drupal);
