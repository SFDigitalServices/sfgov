/**
 * @file
 * TOC API menu behavior.
 */

(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.tocMenu = {
    attach: function (context) {
      $('form.toc-menu > select', context).change(function () {
        var value = $(this).val();
        if (value) {
          location.hash = value;
        }
        this.selectedIndex = 0;
      });
    }
  };

})(jQuery, Drupal);
