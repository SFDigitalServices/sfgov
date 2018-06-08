/**
 * @file
 * General tweaks for the administrative user interface. This file is loaded on
 * all pages where the administrative theme is in use.
 */
(function ($, Drupal, document, window) {

  /**
   * Prevents the Administrative Toolbar from popping out as a sidebar when
   * screen width is reduced.
   */
  Drupal.behaviors.sfgovAdminToolbarReizeTweak = {
    attach: function (context) {
      window.matchMedia('(min-width: 975px)').addListener(function (event) {
        event.matches ? $('#toolbar-item-administration', context).click() : $('.toolbar-item.is-active', context).click();
      });
    }
  };

  /**
   * Adds a button that Toggles the Node edit form sidebar.
   */
  Drupal.behaviors.sfgovAdminSidebarToggle = {
    attach: function (context) {

      $('.layout-region-node-secondary', context).once('sidebarToggle').wrapInner('<div id="sidebar-toggle-content"/>').prepend('<button id="sidebar-toggle" class="sidebar-toggle"><span class="sidebar-toggle__text">' + Drupal.t('Toggle sidebar') + '</span><span class="sidebar-toggle__icon"></span></button>');

      $(document).on('click', '#sidebar-toggle', function (event) {
        event.preventDefault();
        var $container = $('.layout-node-form', context);
        var $sidebar = $('#sidebar-toggle-content', context);

        if ($container.hasClass('sidebar-toggle-active')) {
          $sidebar.show();
          $container.removeClass('sidebar-toggle-active');
        } else {
          $sidebar.hide();
          $container.addClass('sidebar-toggle-active');
        }
      });

      // This media query maps to the Seven theme's breakpoint for when the
      // sidebar appears in the right column. When the screen resized from a
      // large width, to a smaller width, and the sidebar appears below the
      // content, this initiates a click on the toggle to show the sidebar.
      window.matchMedia('screen and (max-width: 780px), (max-device-height: 780px) and (orientation: landscape)').addListener(function (event) {
        if (event.matches && $('#sidebar-toggle-content', context).not(':visible')) {
          $('#sidebar-toggle', context).click();
        }
      });
    }
  };
})(jQuery, Drupal, document, window);
//# sourceMappingURL=sfgov-admin.js.map
