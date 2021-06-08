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
    }
  };
})(jQuery, Drupal);
