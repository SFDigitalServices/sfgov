(function($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.dataStory = {
    attach: function(context, settings) {
      const $toc = $('.sfgov-toc', context)
      const $sticky = $toc.find('.sfgov-toc-sticky')

      if (!$toc.length) {
        return;
      }

      const $closeButton = $toc.find("button.sfgov-toc-close-button");
      const $expandButton = $toc.find("button.sfgov-toc-expand-button");

      $expandButton.on("click", function () {
        $toc.addClass("toc-expanded")
        $closeButton.focus();
      });

      $closeButton.on("click", function () {
        $toc.removeClass("toc-expanded");
        $expandButton.focus();
      })

      $(document, context).on('keyup', function (event) {
        if (event.key === "Escape") {
          if ($toc.hasClass('toc-expanded')) {
            $toc.removeClass("toc-expanded");
            $expandButton.focus();
          }
        }
      });

      $(window, context).on('load', function() {
        var then = 0;
        var now = 0;
        $(window, context).once('scroll-toc').on('scroll', function() {
          now = $(window, context).scrollTop();
          if (then > now && now > 700) {
            $sticky.addClass('show');
          }
          else {
            $sticky.removeClass('show');
          }
          then = now;
        });
      });

      const $anchors = $toc.find('a');
      const $content = $('.sfgov-section--content', context);

      const locationPath = filterPath(location.pathname);
      $anchors.each(function () {
        const thisPath = filterPath(this.pathname) || locationPath;
        const hash = this.hash;
        if ($("#" + hash.replace(/#/, '')).length) {
          if (locationPath === thisPath && (location.hostname === this.hostname || !this.hostname) && this.hash.replace(/#/, '')) {
            var $target = $(hash), target = this.hash;
            if (target) {
              $(this).click(function (event) {
                event.preventDefault();
                $('html, body').animate({scrollTop: $target.offset().top}, 1000, function() {
                  location.hash = target;
                  $target.focus();
                  if ($target.is(":focus")) {
                    return !1;
                  } else {
                    $target.attr('tabindex', '-1');
                    $target.focus()
                  }
                })
              });
            }
          }
        }
      })

      $anchors.on('click', function (event) {
        if ($toc.hasClass('toc-expanded')) {
          $toc.removeClass("toc-expanded");
          $expandButton.focus();
        }
      });

      function filterPath(string) {
        return string
          .replace(/^\//, '')
          .replace(/(index|default).[a-zA-Z]{3,4}$/, '')
          .replace(/\/$/, '');
      }

      function setActiveAnchor(id) {
        $('.active-target').removeClass('active-target');
        $('.has-active-target').removeClass('has-active-target');
        $anchors.each(function () {
          if ($(this).attr('href') === `#${id}`) {
            $(this).addClass('active-target');
            $(this).parents('li').addClass('has-active-target');
          }
        })
      }

      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              setActiveAnchor(entry.target.id);
            }
          })
        },
        { rootMargin: `0% 0% -80% 0%` }
      );

      $anchors.each(function() {
        const id = $(this).attr('href');
        const $target = $content.find(id);
        if (!$target.length) {
          return;
        }

        observer.observe($target[0])
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
