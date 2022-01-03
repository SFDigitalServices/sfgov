"use strict";

(function ($, Drupal) {
  Drupal.behaviors.powerBi = {
    attach: function attach(context, settings) {
      // Cache.
      const $charts = $('[data-powerbi]', context); // Handle window resize.
      
      $charts.each(function () {
        const $chart = $(this);
        const $iframe = $chart.find('> .iframe-container');
        const src = $chart.data().src;
        const iframecode = '';
        const title = $iframe.find('> .powerbi-title').attr('title');
        // Insert powerbi iframes
        iframecode = '<iframe class="powerbi-iframe" tabindex="0" loading="lazy" title="' + title + '" style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;" src="' + src + '"></iframe>';
        $iframe.append(iframecode);
      });

      toggleChart();
      $(window).on("resize", function () {
        toggleChart();
      });

      function toggleChart() {
        const show_device = $(window).outerWidth() > 767 ? "desktop" : "mobile";
        $charts.each(function () {
          const $chart = $(this);
          const $iframe = $chart.find('> .iframe-container');
          const device = $chart.data().device;

          if (device === show_device) {
            $chart.show();
          } else {
            $chart.hide();
          }
        });
      } // We cannot bind focus directly to the iframe.
      // Unless we do polling (which is not efficient).
      // See https://stackoverflow.com/questions/5456239/detecting-when-an-iframe-gets-or-loses-focus
      // Instead we bind to the wrapper container and then shift focus to the iframe.


      $charts.on('focus', function () {
        const $this = $(this);
        const $iframe = $this.find('> iframe');
        const $kbd = $this.prev('.sfgov-powerbi-embed__kbd'); // Show all keyboard instructions.

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
