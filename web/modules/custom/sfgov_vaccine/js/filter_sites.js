(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.filterSites = {
    attach: function (context) {
      // @todo Banish the jquery!
      const $button = $(".vaccine-filter-form #edit-submit");
      const $sites = $("[data-vaccine=results]");

      // Click the left nav button.
      $button.on("click", function (event) {
        event.preventDefault();

        const $available = $("[name=available]").val();

        if ($available == 1) {
          $("[data-available=false]").hide();
          $("[data-available=true]").show();
        }

        $sites.each();
      });
    },
  };
})(jQuery, Drupal);
