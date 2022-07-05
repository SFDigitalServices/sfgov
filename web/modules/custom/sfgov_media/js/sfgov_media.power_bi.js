(function ($, Drupal) {
  Drupal.behaviors.mediaPowerBi = {
    attach (context, settings) {
      // Cache.
      const $charts = $('[data-power-bi]', context)

      // Handle window resize.
      toggleChart()
      $(window).on('resize', () => {
        toggleChart()
      })

      function toggleChart () {
        const showDevice = $(window).outerWidth() > 767 ? 'desktop' : 'mobile'

        $charts.each(function () {
          const $chart = $(this)
          const $iframe = $chart.find('> iframe')
          const device = $chart.data().device
          const src = $chart.data().src

          if (device === showDevice) {
            if (!$iframe.attr('src')) {
              $iframe.attr('src', src)
            }

            $chart.show()
          } else {
            $iframe.attr('src', '')
            $chart.hide()
          }
        })
      }
    }
  }
})(jQuery, Drupal)
