"use strict";

(function ($, Drupal) {
  Drupal.behaviors.powerBi = {
    attach: function attach(context, settings) {
      // Cache.
      var $charts = $('[data-powerbi]', context); // Handle window resize.
      
      $charts.each(function () {
        var $chart = $(this);
        var $iframe = $chart.find('> .iframe-container');
        var src = $chart.data().src;
        var iframecode = '';
        var title = $iframe.find('> .powerbi-title').attr('title');
        // Insert powerbi iframes
        iframecode = '<iframe class="powerbi-iframe" tabindex="0" loading="lazy" title="' + title + '" style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;" src="' + src + '"></iframe>';
        $iframe.append(iframecode);
      });

      toggleChart();
      $(window).on("resize", function () {
        toggleChart();
      });

      function toggleChart() {
        var show_device = $(window).outerWidth() > 767 ? "desktop" : "mobile";
        $charts.each(function () {
          var $chart = $(this);
          var $iframe = $chart.find('> .iframe-container');
          var device = $chart.data().device;
          var src = $chart.data().src;
          var iframecode = '';
          var title = $iframe.find('> .powerbi-title').attr('title');

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
