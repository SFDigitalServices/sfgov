(function($, Drupal) {
  'use strict';

  Drupal.behaviors.multipleSelect = {
    attach: function(context, settings) {
      $('select[data-multiple-select]')
        .once('multiple-select')
        .multipleSelect();
    },
  };
})(jQuery, Drupal);
