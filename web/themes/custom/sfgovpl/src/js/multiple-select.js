(function($, Drupal) {
  'use strict';

  Drupal.behaviors.multipleSelect = {
    attach: function(context, settings) {
      $('select[data-multiple-select]').multipleSelect({
        // displayValues: true,
        // multipleWidth: 60,
      });
    },
  };
})(jQuery, Drupal);
