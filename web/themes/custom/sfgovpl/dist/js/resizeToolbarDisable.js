"use strict";

(function ($) {
  Drupal.behaviors.dieToolbardie = {
    attach: function attach(context) {
      window.matchMedia('(min-width: 975px)').addListener(function (event) {
        event.matches ? $('#toolbar-item-administration', context).click() : $('.toolbar-item.is-active', context).click();
      });
    }
  };
})(jQuery);