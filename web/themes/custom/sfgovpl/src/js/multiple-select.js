(function($, Drupal) {
  'use strict';

  Drupal.behaviors.multipleSelect = {
    attach: function(context) {
      $('[data-multiselect]', context).parents('fieldset.fieldgroup').toMultiSelect();
    },
  };

})(jQuery, Drupal);
