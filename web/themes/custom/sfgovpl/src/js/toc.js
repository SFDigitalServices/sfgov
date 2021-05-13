(function($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.sfgovToc = {
    attach: function(context, settings) {
      const $toc = $('.sfgov-toc', context)

      if (!$toc.length) {
        return;
      }

      const $closeButton = $toc.find("button.sfgov-toc-close-button");
      const $expandButton = $toc.find("button.sfgov-toc-expand-button");
      const $feedbackForm = $('.paragraph--formio--feedback');

      $expandButton.on("click", function () {
        $toc.addClass("toc-expanded")
        $closeButton.focus();
        $feedbackForm.addClass('toc-expanded-feedback');
      });

      $closeButton.on("click", function () {
        $toc.removeClass("toc-expanded");
        $expandButton.focus();
        $feedbackForm.removeClass('toc-expanded-feedback');
      })

      $(document, context).on('keyup', function (event) {
        if (event.key === "Escape") {
          if ($toc.hasClass('toc-expanded')) {
            $toc.removeClass("toc-expanded");
            $expandButton.focus();
            $feedbackForm.removeClass('toc-expanded-feedback');
          }
        }
      });

      const $anchors = $toc.find('a');
      const $content = $('.sfgov-section--content', context);

      $anchors.on('click', function () {
        if ($toc.hasClass('toc-expanded')) {
          $toc.removeClass("toc-expanded");
          $expandButton.focus();
          $feedbackForm.removeClass('toc-expanded-feedback');
        }
      });

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
