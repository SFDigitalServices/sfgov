/**
 * @file
 * TOC type options behavior.
 */

(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.tocTypeOptions = {
    attach: function (context) {
      $('.js-toc-type-options-header-min, .js-toc-type-options-header-max', context).once().change(toggleHeaders);
    }
  };
  toggleHeaders();

  function toggleHeaders() {
    var min = $('.js-toc-type-options-header-min').val();
    var max = $('.js-toc-type-options-header-max').val();

    for (var i = 1; i <= 6; i++) {
      // Having to use the id instead of $('.js-toc-type-options-header-h' + i).
      var $header = $('details[id$="-headers-h' + i + '"]');
      (i >= min && i <= max) ? $header.show() : $header.hide();
    }
  }

})(jQuery, Drupal);
