(function ($, Drupal) {
  Drupal.behaviors.powerBi = {
    attach: function (context, settings) {
      // Cache.
      const $charts = $('[data-powerbi]', context);

      // Handle window resize.
      toggleChart();
      $(window).on("resize", function () {
        toggleChart();
      })

      function toggleChart() {
        const show_device = $(window).outerWidth() > 767 ? "desktop" : "mobile";

        $charts.each(function () {
          const $chart = $(this);
          const $iframe = $chart.find('> iframe');
          const device = $chart.data().device
          const src = $chart.data().src

          if (device === show_device) {
            if (!$iframe.attr('src')) {
              $iframe.attr('src', src);
            }

            $chart.show();
          }
          else {
            $iframe.attr('src', '');
            $chart.hide();
          }
        })
      }

      // We cannot bind focus directly to the iframe.
      // Unless we do polling (which is not efficient).
      // See https://stackoverflow.com/questions/5456239/detecting-when-an-iframe-gets-or-loses-focus
      // Instead we bind to the wrapper container and then shift focus to the iframe.
      $charts.on('focus', function () {
        // Add the focus class to the iframe to show visual guides.
        $(this).find('> iframe')
          .addClass('focus')
          .focus();
        $('.sfgov-powerbi-embed__kbd').removeClass('hidden');
      })

      // Handles tabbing away from iframe.
      $(window).on('focus', function () {
        $('iframe.focus').removeClass('focus');
      })

    }
  };
})(jQuery, Drupal);
