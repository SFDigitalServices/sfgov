"use strict";

(function ($, Drupal) {
  Drupal.behaviors.powerBi = {
    attach: function attach(context, settings) {
      // Cache.
      var $charts = $('[data-powerbi]', context); // Handle window resize.

      toggleChart();
      $(window).on("resize", function () {
        toggleChart();
      });

      function toggleChart() {
        var show_device = $(window).outerWidth() > 767 ? "desktop" : "mobile";
        $charts.each(function () {
          var $chart = $(this);
          var $iframe = $chart.find('> iframe');
          var device = $chart.data().device;
          var src = $chart.data().src;

          if (device === show_device) {
            if (!$iframe.attr('src')) {
              $iframe.attr('src', src);
            }

            $chart.show();
          } else {
            $iframe.attr('src', '');
            $chart.hide();
          }
        });
      } // We cannot bind focus directly to the iframe.
      // Unless we do polling (which is not efficient).
      // See https://stackoverflow.com/questions/5456239/detecting-when-an-iframe-gets-or-loses-focus
      // Instead we bind to the wrapper container and then shift focus to the iframe.


      $charts.on('focus', function () {
        var $this = $(this);
        var $iframe = $this.find('> iframe');
        var $kbd = $this.prev('.sfgov-powerbi-embed__kbd'); // Show all keyboard instructions.

        if ($kbd.hasClass('hidden')) {
          $('.sfgov-powerbi-embed__kbd').removeClass('hidden');
          $kbd.focus();
        }

        $iframe.addClass('focus').focus();
      }); // Handles tabbing away from iframe.

      $(window).on('focus', function () {
        $('iframe.focus').removeClass('focus');
      });
    }
  };
})(jQuery, Drupal);