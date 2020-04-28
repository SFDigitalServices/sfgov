/**
 * @file
 * Javascript for Color Field.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Enables spectrum on color elements.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches a spectrum widget to a color input element.
   */
  Drupal.behaviors.color_field_spectrum = {
    attach: function (context, settings) {

      var $context = $(context);

      $context.find('.js-color-field-widget-spectrum').once('colorFieldSpectrum').each(function (index, element) {
        var $element = $(element);
        var $element_color = $element.find('.js-color-field-widget-spectrum__color');
        var $element_opacity = $element.find('.js-color-field-widget-spectrum__opacity');
        var spectrum_settings = settings.color_field.color_field_widget_spectrum[$element.attr('id')];

        // Hide the widget labels if the widgets are being shown.
        if (!spectrum_settings.show_input) {
          $('.js-color-field-widget-spectrum').find('label').hide();
          $element_opacity.hide();
        }

        $element_color.spectrum({
          showInitial: true,
          preferredFormat: "hex",
          showInput: spectrum_settings.show_input,
          showAlpha: spectrum_settings.show_alpha,
          showPalette: spectrum_settings.show_palette,
          showPaletteOnly: spectrum_settings.show_palette_only,
          palette:  spectrum_settings.palette,
          showButtons: spectrum_settings.show_buttons,
          allowEmpty: spectrum_settings.allow_empty,

          change: function (tinycolor) {
            var hexColor = '';
            var opacity = '';

            if (tinycolor) {
              hexColor = tinycolor.toHexString();
              opacity = tinycolor._roundA;
            }

            $element_color.val(hexColor);
            $element_opacity.val(opacity);
          }

        });

        // Set alpha value on load.
        if (!!spectrum_settings.show_alpha) {
          var tinycolor = $element_color.spectrum("get");
          var alpha = $element_opacity.val();
          if (alpha > 0) {
            tinycolor.setAlpha(alpha);
            $element_color.spectrum("set", tinycolor);
          }
        }

      });
    }
  };

})(jQuery, Drupal);
