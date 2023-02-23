(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.sfgovToc = {
    attach (context, settings) {
      const $toc = $('.sfgov-toc', context)
      const $sticky = $toc.find('.sfgov-toc-sticky')

      if (!$toc.length) {
        return
      }

      const $closeButton = $toc.find('button.sfgov-toc-close-button')
      const $expandButton = $toc.find('button.sfgov-toc-expand-button')
      const $feedbackForm = $('.paragraph--formio--feedback')

      $expandButton.on('click', () => {
        $toc.addClass('toc-expanded')
        $closeButton.focus()
        $feedbackForm.addClass('toc-expanded-feedback')
      })

      $closeButton.on('click', () => {
        $toc.removeClass('toc-expanded')
        $expandButton.focus()
        $feedbackForm.removeClass('toc-expanded-feedback')
      })

      $(document, context).on('keyup', event => {
        if (event.key === 'Escape') {
          if ($toc.hasClass('toc-expanded')) {
            $toc.removeClass('toc-expanded')
            $expandButton.focus()
            $feedbackForm.removeClass('toc-expanded-feedback')
          }
        }
      })

      $(window, context).on('load', () => {
        let then = 0
        let now = 0
        $(window, context).once('scroll-toc').on('scroll', () => {
          now = $(window, context).scrollTop()
          if (then > now && now > 700) {
            $sticky.addClass('show')
          }
          else {
            $sticky.removeClass('show')
          }
          then = now
        })
      })

      const $anchors = $toc.find('a')
      const $content = $('.sfgov-section--content', context)

      // Set animation speed based on motion preference.
      const animationSpeed = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 1000

      const locationPath = filterPath(location.pathname)
      $anchors.each(function () {
        const thisPath = filterPath(this.pathname) || locationPath
        const hash = this.hash
        if ($('#' + hash.replace(/#/, '')).length) {
          if (locationPath === thisPath && (location.hostname === this.hostname || !this.hostname) && this.hash.replace(/#/, '')) {
            const $target = $(hash); const target = this.hash
            if (target) {
              $(this).click(event => {
                event.preventDefault()
                $('html, body').animate({ scrollTop: $target.offset().top }, animationSpeed, () => {
                  location.hash = target
                  $target.focus()
                  if ($target.is(':focus')) {
                    return !1
                  }
                  else {
                    $target.attr('tabindex', '-1')
                    $target.focus()
                  }
                })
              })
            }
          }
        }
      })

      $anchors.on('click', event => {
        if ($toc.hasClass('toc-expanded')) {
          $toc.removeClass('toc-expanded')
          $expandButton.focus()
          $feedbackForm.removeClass('toc-expanded-feedback')
        }
      })

      function filterPath (string) {
        return string
          .replace(/^\//, '')
          .replace(/(index|default).[a-zA-Z]{3,4}$/, '')
          .replace(/\/$/, '')
      }

      function setActiveAnchor (id) {
        $('.active-target').removeClass('active-target')
        $('.has-active-target').removeClass('has-active-target')
        $anchors.each(function () {
          if ($(this).attr('href') === `#${id}`) {
            $(this).addClass('active-target')
            $(this).parents('li').addClass('has-active-target')
          }
        })
      }

      const observer = new IntersectionObserver(
        entries => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              setActiveAnchor(entry.target.id)
            }
          })
        },
        { rootMargin: '0% 0% -80% 0%' }
      )

      $anchors.each(function () {
        const id = $(this).attr('href')
        const $target = $content.find(id)
        if (!$target.length) {
          return
        }

        observer.observe($target[0])
      })
    }
  }
})(jQuery, Drupal, drupalSettings)
