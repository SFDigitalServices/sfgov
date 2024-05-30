(function ($, Drupal, drupalSettings) {
  function resizeFactItems() {
    $('body.page-node-type-campaign').each(() => {
      $('.campaign-facts').each(function () {
        const images = $(this).find('img');
        if (images.length > 0) {
          if (window.innerWidth > 768) {
            let tallestImage = images[0];
            for (let i = 1; i < images.length; i++) {
              if ($(images[i]).height() > $(tallestImage).height()) {
                tallestImage = images[i];
              }
            }
            const tallestHeight = $(tallestImage).height();
            $(this).find('.fact-item .item-wrapper').height(tallestHeight);
          } else {
            $(this).find('.fact-item .item-wrapper').css({
              height: 'auto'
            });
          }
        } else {
          $(this).find('.fact-item .image').hide();
        }
      });
    });
  }
  $(window).on('load resize', () => {
    resizeFactItems();
  });
})(jQuery, Drupal, drupalSettings);