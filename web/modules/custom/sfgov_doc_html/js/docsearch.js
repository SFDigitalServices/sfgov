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

      $input.on("focus", function () {
        $form.addClass('is-focused');
      }).on("blur", function () {
        $form.removeClass('is-focused');
      });

      $closeButton.on("click", function () {
        $input.val("");
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
        const keywords = $input.val().trim().replace(/(\s+)/,"(<[^>]+>)*$1(<[^>]+>)*");
        if (keywords.length <= 3) {
          reset();
          return;
        }

        const pattern = new RegExp("("+keywords+")","gi");
        $searchTargets.each(function (index, target) {
          let resultText = target.source.replace(pattern, "<mark>$1</mark>");
          resultText.replace(/(<mark>[^<>]*)((<[^>]+>)+)([^<>]*<\/mark>)/,"$1</mark>$2<mark>$4");
          target.el.html(resultText)
        });

        $form.addClass('has-results');
      }

      function reset() {
        $form.removeClass('has-results');
        $form.removeClass('has-input');
        $searchTargets.each(function (index, target) {
          target.el.html(target.source)
        })
      }

      $input.on("keyup", function (event) {
        search(event);
        if ($(this).val()) {
          $form.addClass('has-input');
        }
        else {
          $form.removeClass('has-input');
        }
      })

      $form.on('submit', function (event) {
        search(event);
        return false;
      });
    }
  };
}(jQuery, Drupal));
