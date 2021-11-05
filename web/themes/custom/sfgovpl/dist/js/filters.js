"use strict";

;

(function ($, window, Drupal) {
  'use strict';

  Drupal.behaviors.meetings = {
    attach: function attach(context) {
      var $form = $('.sfgov-filters form'); // Note: that we have two buttons instead of one because the visual
      // placement of button changes depending on state which would produce a
      // confusing tab order with only one button.

      var $buttonShow = $('<button />').addClass('filter-toggle filter-toggle--show').append('<span>' + Drupal.t('Show filters') + '</span>');
      var $buttonHide = $('<button />').addClass('filter-toggle filter-toggle--hide').append('<span>' + Drupal.t('Hide filters') + '</span>');
      $('[data-filter-toggle-content]', context).each(function () {
        var $content = $(this);
        $buttonShow.insertBefore($form).once();
        $buttonHide.insertAfter($form).once(); // Handle click events.

        $buttonHide.on('click', function (e) {
          e.preventDefault();
          toggleFilters($content, 'hide'); // Focus the show button.

          $buttonShow.focus();
        });
        $buttonShow.on('click', function (e) {
          e.preventDefault();
          toggleFilters($content, 'show');
        }); // Breakpoint at which sidebar becomes visible, and toggle functionality
        // is disabled.

        var breakpoint = window.matchMedia('(min-width: 768px)');

        function respondToBreakpoint(breakpoint) {
          if (breakpoint.matches) {
            // > $medium-screen
            toggleFilters($content, 'show');
            $buttonShow.hide();
            $buttonHide.hide();
            moveFilterTitle('medium-plus');
          } else {
            // < $medium-screen
            toggleFilters($content, 'hide');
            moveFilterTitle();
          }
        } // Initial page load.


        respondToBreakpoint(breakpoint); // Screen resizes.

        breakpoint.addListener(respondToBreakpoint);
      });

      function moveFilterTitle(size) {
        var $containerAttrStr = '[data-drupal-selector="edit-container"]';
        var $label = $form.find($containerAttrStr + ' > legend'); // Adding class for css.

        $label.addClass('sfgov-filters-legend');

        if (size === 'medium-plus') {
          // Restore original location.
          $form.find($containerAttrStr).prepend($label);
        } else {
          // Move label.
          $label.insertBefore($buttonShow);
        }
      }

      function toggleFilters($content, status) {
        var $filter_container = $content.parents('.sfgov-filters');
        $filter_container.removeClass('is-expanded is-collapsed');

        if (status === 'hide') {
          $content.attr({
            hidden: '',
            'aria-hidden': true
          });
          $buttonShow.attr('aria-expanded', false).show();
          $buttonHide.attr('aria-expanded', true).hide();
          $filter_container.addClass('is-collapsed');
        } else {
          $content.removeAttr('aria-hidden hidden');
          $buttonShow.attr('aria-expanded', true).hide();
          $buttonHide.attr('aria-expanded', false).show();
          $filter_container.addClass('is-expanded');
        }
      }
    }
  };
})(jQuery, window, Drupal);