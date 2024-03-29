(function($, Drupal) {

  Drupal.behaviors.sfgovDocsearch = {
    attach: function (context) {
      const $form = $("form#docsearch", context);
      if (!$form.length) {
        return;
      }

      const $searchTarget = $($form.data('search-target'), context);
      if (!$searchTarget.length) {
        return;
      }

      const $input = $form.find("input[name=\"keywords\"]");
      const $closeButton = $form.find("button");

      const $searchInfo = $form.find('.results-info');
      const $searchIndex = $searchInfo.find('.results-index');
      const $searchNav = $searchInfo.find('.results-nav');
      const $searchResultNext = $searchInfo.find('.results-index-next');
      const $searchResultPrev = $searchInfo.find('.results-index-prev');
      let currentText = '';
      let currentIndex = 1;

      $searchNav.hide();

      $input.on("focus", function () {
        $form.addClass('is-focused');
      }).on("blur", function () {
        $form.removeClass('is-focused');
      });

      $closeButton.on("click", function () {
        $('.report--full').find('mark').contents().unwrap();
        $searchInfo.hide();
        $input.val('');
        reset();
      });

      const $searchTargets = $searchTarget.find("a, p, h2, h3, h5, h5, li").map(function () {
        const $target = $(this);
        return {
          el: $target,
          source: $target.html(),
          text: $target.html()
        };
      });

      function search(event) {
        event.preventDefault();
        const keywords = $input.val().trim().replace(/(\s+)/, "(<[^>]+>)*$1(<[^>]+>)*");
        if (keywords.length <= 1) {
          reset();
          return;
        }

        $searchInfo.show();

        const pattern = new RegExp("(" + keywords + ")", "gi");
        if (currentText.length != 0 && currentText !== keywords) {
          currentIndex = 0;
        }
        $searchTargets.each(function (index, target) {
          let resultText = target.source.replace(pattern, "<mark>$1</mark>");
          resultText.replace(/(<mark>[^<>]*)((<[^>]+>)+)([^<>]*<\/mark>)/, "$1</mark>$2<mark>$4");
          target.el.html(resultText);
        });

        $form.addClass('has-results');
        $form.addClass('has-input');
      }

      function reset() {
        $form.removeClass('has-results');
        $form.removeClass('has-input');
        $searchTargets.each(function (index, target) {
          target.el.html(target.source)
        });
        $searchInfo.hide();
      }

      function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
      }

      function airaLabel(currentIndex, total) {
        return Drupal.t('Match @current of @total. Click to get to the previous match.', {'@current': currentIndex, '@total': numberWithCommas(total) });
      }

      function searchStatsText(currentIndex, total) {
        return Drupal.t('@current of @total', {'@current': currentIndex, '@total': numberWithCommas(total) });
      }

      function jumpToMatch(index) {
        const animationSpeed = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 500;
        const $el = $('mark').eq( (index == 0) ? index : index - 1 );
        $('mark').removeClass('current');
        $el.addClass('current');
        $('html, body').stop().animate({
          scrollTop: $el.offset().top - 100
        }, animationSpeed);
      }

      function resultsInfo() {
        const total = $('.report--full').find('mark').length;
        if (total == 0) {
          currentIndex = 0;
          $input.attr('aria-label', Drupal.t('No results. Please refine your keywords'));
          $searchNav.hide();
        }
        else {
          currentIndex = 1;
          if (total > 1) {
            $searchNav.show();
          }
        }
        $searchIndex.html(searchStatsText(currentIndex, total));
        jumpToMatch(currentIndex);
      }

      $searchResultNext.on('click', function(e) {
        e.preventDefault();
        const total = $('.report--full').find('mark').length;
        if (currentIndex == total) {
          currentIndex = 0;
        }
        currentIndex++;
        $(this).attr('aria-label', airaLabel(currentIndex, total));
        $searchIndex.html(searchStatsText(currentIndex, total));
        jumpToMatch(currentIndex);
      });

      $searchResultPrev.on('click', function(e) {
        e.preventDefault();
        const total = $('.report--full').find('mark').length;
        if (currentIndex < 2) {
          currentIndex = total;
        }
        else {
          currentIndex--;
        }
        $(this).attr('aria-label', airaLabel(currentIndex, total));
        $searchIndex.html(searchStatsText(currentIndex, total));
        jumpToMatch(currentIndex);
      });

      $input.on("keyup", function (event) {
        if (event.key == 'Escape') {
          $closeButton.trigger('click');
        }
        const keyword = $(this).val();
        if (currentText.length != 0 && currentText === keyword) {
          if (event.key == 'Enter' || event.key == 'ArrowDown') {
            $searchResultNext.trigger('click');
          }
          else if (event.key == 'ArrowUp') {
            $searchResultPrev.trigger('click');
          }
          $form.addClass('has-input');
        }
        else {
          $form.removeClass('has-input');
          search(event);
          resultsInfo();
          currentText = keyword;
        }
      })

      $form.on('submit', function (event) {
        event.preventDefault();
        //search(event);
        return false;
      });
    }
  };

}(jQuery, Drupal));
