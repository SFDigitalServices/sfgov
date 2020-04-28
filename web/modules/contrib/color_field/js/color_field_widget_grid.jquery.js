/**
 * @file
 * Javascript for Color Field.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Enables grid widget on color elements.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches a grid widget to a color input element.
   */
  Drupal.behaviors.color_field_jquery_simple_color = {
    attach: function (context, settings) {
      var $context = $(context);

      $context.find('.js-color-field-widget-grid__color').once('colorFieldJquerySimpleColor').each(function (index, element) {
        var $element = $(element);
        var widgetSettings = settings.color_field.color_field_widget_grid[$(this).attr('id')];

        $element.simpleColor({
          cellWidth: widgetSettings.cell_width,
          cellHeight: widgetSettings.cell_height,
          cellMargin: widgetSettings.cell_margin,
          boxWidth: widgetSettings.box_width,
          boxHeight: widgetSettings.box_height
        });
      });

    }
  };

})(jQuery, Drupal);
