(function($, window, Drupal) {
  'use strict';

  Drupal.behaviors.multipleSelect = {
    attach: function(context) {
      // Initialize multi select plugin
      $('[data-multiselect]', context).parents('fieldset.fieldgroup').toMultiSelect();

      // Default mobile state: Add toggle button, hide filter content.
      $('[data-filter-toggle-container]', context)
        .prepend('<button data-filter-toggle-trigger id="filter-toggle" class="filter-toggle"><span class="filter-toggle__text">' + Drupal.t('Show filters') + '</span></button>')
        .addClass('filter-toggle-collapsed')
        .find('[data-filter-toggle-content]').hide();

      // Handle resizing. At 950px ($narrow-screen), hide the toggle button and
      // and show the content.
      window.matchMedia('(min-width: 950px)').addListener(function(event) {
        if (event.matches) {
          $('[data-filter-toggle-content]', context).show();
          $('[data-filter-toggle-trigger]', context).hide();
        }
        else {
          $('[data-filter-toggle-content]', context).hide();
          $('[data-filter-toggle-trigger]', context).show();
        }
      });

      $('[data-filter-toggle-trigger]', context).on('click', function(event) {
        event.preventDefault();
        var $button = $(this);
        var $container = $button.parent('[data-filter-toggle-container]');
        var $content = $('[data-filter-toggle-content]', $container);

        if ($content.is(':hidden')) {
          $content.show();
          $button.find('span').text(Drupal.t('Hide filters'));
          $container.removeClass('filter-toggle-collapsed');
        }
        else {
          $content.hide();
          $button.find('span').text(Drupal.t('Show filters'));
          $container.addClass('filter-toggle-collapsed');
        }
      });
    },
  };

})(jQuery, window, Drupal);
