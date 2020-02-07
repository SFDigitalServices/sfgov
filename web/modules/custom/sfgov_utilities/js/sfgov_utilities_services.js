(function ($, Drupal) {
  Drupal.behaviors.sfgovUtilitiesServices = {
    attach: function (context, settings) {
      if ($(window).width() < 701) {
        $('.sfgov-services-section .sfgov-services').each(function(){
          $(this).addClass('accordion');
          $(this).accordion({
            active: false,
            collapsible: true,
            header: 'h5',
          });
        });
      }
    }
  };
})(jQuery, Drupal);
