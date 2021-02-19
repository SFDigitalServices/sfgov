(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.filterSites = {
    attach: function (context) {
      // @todo Banish the jquery!
      const $button = $(".vaccine-filter-form #edit-submit", context);
      const $sites = $(".vaccine-site");
      const $checkboxFilters = ["available", "restrictions"];
      const $selectFilters = ["language", "access_mode"];

      function checkActiveSelectFilters(filter_label, y) {
        return $(`[name=${filter_label}]`).prop("checked");
      }

      function checkActiveCheckboxFilters(filter_label) {
        return $(`[name=${filter_label}]`).prop("selected");
      }

      // Click Apply.
      $button.on("click", function (event) {
        event.preventDefault();

        // @todo Change to if class exists in display().
        $sites.removeClass("invisible");

        const $active_filters = $checkboxFilters.filter(
          checkActiveCheckboxFilters
        );

        const $selectFilters = $checkboxFilters.filter(
          checkActiveSelectFilters
        );

        function isRemoved(value, site) {
          for (let filter_label of $active_filters) {
            return site.getAttribute(`data-${filter_label}`) === "false";
          }
        }

        function display(x, y) {
          if (typeof x[1] === "object" && x[1].classList !== undefined) {
            // @todo Use a different class or the hide attribute.
            x[1].classList.add("invisible");
          }
        }

        const filtered_sites = $sites.filter(isRemoved);
        Object.entries(filtered_sites).forEach(display);
      });
    },
  };
})(jQuery, Drupal);
