(function ($, window, Drupal) {
  Drupal.behaviors.meetings = {
    attach (context) {
      const $form = $('.sfgov-filters form')

      // Note: that we have two buttons instead of one because the visual
      // placement of button changes depending on state which would produce a
      // confusing tab order with only one button.
      const $buttonShow = $('<button />')
        .addClass('filter-toggle filter-toggle--show')
        .append('<span>' + Drupal.t('Show filters') + '</span>')

      const $buttonHide = $('<button />')
        .addClass('filter-toggle filter-toggle--hide')
        .append('<span>' + Drupal.t('Hide filters') + '</span>')

      $('[data-filter-toggle-content]', context).each(function () {
        const $content = $(this)

        $buttonShow.insertBefore($form).once()
        $buttonHide.insertAfter($form).once()

        // Handle click events.
        $buttonHide.on('click', e => {
          e.preventDefault()
          toggleFilters($content, 'hide')
          // Focus the show button.
          $buttonShow.focus()
        })
        $buttonShow.on('click', e => {
          e.preventDefault()
          toggleFilters($content, 'show')
        })

        // Breakpoint at which sidebar becomes visible, and toggle functionality
        // is disabled.
        const breakpoint = window.matchMedia('(min-width: 768px)')

        function respondToBreakpoint (breakpoint) {
          if (breakpoint.matches) {
            // > $medium-screen
            toggleFilters($content, 'show')
            $buttonShow.hide()
            $buttonHide.hide()
            moveFilterTitle('medium-plus')
          } else {
            // < $medium-screen
            toggleFilters($content, 'hide')
            moveFilterTitle()
          }
        }

        // Initial page load.
        respondToBreakpoint(breakpoint)
        // Screen resizes.
        breakpoint.addListener(respondToBreakpoint)
      })

      function moveFilterTitle (size) {
        const $containerAttrStr = '[data-drupal-selector="edit-container"]'
        const $label = $form.find($containerAttrStr + ' > legend')

        // Adding class for css.
        $label.addClass('sfgov-filters-legend')

        if (size === 'medium-plus') {
          // Restore original location.
          $form.find($containerAttrStr).prepend($label)
        } else {
          // Move label.
          $label.insertBefore($buttonShow)
        }
      }

      function toggleFilters ($content, status) {
        const $filters = $content.parents('.sfgov-filters')
        $filters.removeClass('is-expanded is-collapsed')

        if (status === 'hide') {
          $content.attr({ hidden: '', 'aria-hidden': true })
          $buttonShow.attr('aria-expanded', false).show()
          $buttonHide.attr('aria-expanded', true).hide()
          $filters.addClass('is-collapsed')
        } else {
          $content.removeAttr('aria-hidden hidden')
          $buttonShow.attr('aria-expanded', true).hide()
          $buttonHide.attr('aria-expanded', false).show()
          $filters.addClass('is-expanded')
        }
      }
    }
  }
})(jQuery, window, Drupal)
