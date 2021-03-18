(function($, window, Drupal) {
  'use strict';

  Drupal.behaviors.meetings = {
    attach: function(context) {

      $('[data-filter-toggle-content]', context).each(function () {
        var $content = $(this);

        // Note: that we have two buttons instead of one because the visual
        // placement of button changes depending on state which would produce a
        // confusing tab order with only one button.
        var $buttonShow = $('<button />')
          .addClass('filter-toggle filter-toggle--show')
          .append('<span>' + Drupal.t('Show filters') + '</span>');

        var $buttonHide = $('<button />')
          .addClass('filter-toggle filter-toggle--hide')
          .append('<span>' + Drupal.t('Hide filters') + '</span>');

        $buttonShow.insertBefore($content).once();
        $buttonHide.insertAfter($content).once();

        // Handle click events.
        $buttonHide.on('click', function(e) {
          e.preventDefault();
          toggleFilters($content, 'hide');
          // Focus the show button.
          $buttonShow.focus();
        });
        $buttonShow.on('click', function(e) {
          e.preventDefault();
          toggleFilters($content, 'show');
        });

        // Breakpoint at which sidebar becomes visible, and toggle functionality
        // is disabled.
        var breakpoint = window.matchMedia('(min-width: 768px)');

        function respondToBreakpoint(breakpoint) {
          if (breakpoint.matches) {
            toggleFilters($content, 'show');
            $buttonShow.hide();
            $buttonHide.hide();
          }
          else {
            toggleFilters($content, 'hide');
          }
        }

        // Initial page load.
        respondToBreakpoint(breakpoint);
        // Screen resizes.
        breakpoint.addListener(respondToBreakpoint);
      });

      function toggleFilters($content, status) {
        var $container = $content.parents('[data-filter-toggle-container]');
        var $buttonHide = $container.find('.filter-toggle--hide');
        var $buttonShow = $container.find('.filter-toggle--show');

        $container.removeClass('is-expanded is-collapsed')

        if (status === 'hide') {
          $content.attr({'hidden': '', 'aria-hidden': true});
          $buttonShow.attr('aria-expanded', false).show();
          $buttonHide.attr('aria-expanded', true).hide();
          $container.addClass('is-collapsed');
        }
        else {
          $content.removeAttr('aria-hidden hidden');
          $buttonShow.attr('aria-expanded', true).hide();
          $buttonHide.attr('aria-expanded', false).show();
          $container.addClass('is-expanded');
        }
      }

    },
  };

})(jQuery, window, Drupal);
