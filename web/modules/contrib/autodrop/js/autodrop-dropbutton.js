/**
 * Indiscriminately expands drop buttons on mouse enter.
 **/

(function ($, Drupal) {
    Drupal.behaviors.autodropbutton = {
        attach: function attach(context, settings) {
            $('.dropbutton-wrapper', context).once('autodrop').on('mouseenter', null, function(e) {
                e.preventDefault();
                $(e.target).closest('.dropbutton-wrapper').toggleClass('open');
            });
        }
    };
})(jQuery, Drupal);
