(function($, Drupal, drupalSettings) {
  'use strict';
  function resizeFactItems() {
    $('body.page-node-type-campaign').each(function() {
      $('.campaign-facts').each(function() {
        var images = $(this).find('img');
        if(images.length > 0) {
          var tallestImage = images[0];
          for(var i=1; i<images.length; i++) {
            if($(images[i]).height() > $(tallestImage).height()) {
              tallestImage = images[i];
            }
          }
          var tallestHeight = $(tallestImage).height();
          $(this).find('.fact-item .item-wrapper').height(tallestHeight);
        } else {
          $(this).find('.fact-item .image').hide();
        }
      });
    });
  }
  $(window).on('load resize', function() {
    resizeFactItems();
  });
})(jQuery, Drupal, drupalSettings);
