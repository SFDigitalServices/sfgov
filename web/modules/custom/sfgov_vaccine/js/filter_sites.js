(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.filterSites = {
    attach: function (context, settings) {
      // @todo Banish the jquery!

      // Set media query and register event listener.
      const mediaQuery = window.matchMedia("(min-width: 768px)");
      mediaQuery.addListener(layoutChange);

      // Elements.
      const sectionCount = $(".vaccine-filter__count");
      const leftColumn = $(".group--left");
      const submitButton = $(".vaccine-filter-form #edit-submit", context);

      // Other variables.
      let filterByAvailability = false;
      const speed = "slow";
      const class_match_available = "match-available";
      const class_match_radius = "match-radius";

      // On load.
      displaySites();
      layoutChange(mediaQuery);

      // On Click.
      submitButton.on("click", function (event) {
        event.preventDefault();
        leftColumn.fadeOut(0);
        displaySites();
        leftColumn.fadeIn(speed);
        scrollUp(speed);
      });

      function filterVaccineSites() {
        let restrictions_chkBox = { datatest: null };
        let wheelchair_chkBox = { datatest: null };
        let locationInput = $("[name=location]");
        let radiusInput = $("[name=radius]");
        let userLocation = !!locationInput.val();

        if ($("[name=restrictions]").is(":checked") === true) {
          // show
          restrictions_chkBox.datatest = "0";
        } else {
          //hide
          restrictions_chkBox.datatest = "";
        }

        filterByAvailability = $("[name=available]").is(":checked");

        if ($("[name=wheelchair]").is(":checked") === true) {
          wheelchair_chkBox.datatest = "1";
        } else {
          wheelchair_chkBox.datatest = "";
        }

        // Test and filter.
        $(".vaccine-site")
          .hide()
          .removeClass("included")
          .filter(function () {
            let rtnData = "";

            // "Only show sites open to the general public" checkbox.
            const restrictions_regExTest = new RegExp(
              restrictions_chkBox.datatest,
              "ig"
            );

            // "Only show sites with available appointments" checkbox.
            if (filterByAvailability === true) {
              $(this).removeClass(class_match_available);
              if (
                $(this).attr("data-available") === "yes" ||
                $(this).find(".js-dropin").length !== 0
              ) {
                $(this).appendTo(".vaccine-filter__sites");
                $(this).addClass(class_match_available);
              }
            } else {
              $(this).appendTo(".vaccine-filter__sites");
              $(this).addClass(class_match_available);
            }

            // "Wheelchair accessible" checkbox.
            const wheelchair_regExTest = new RegExp(
              wheelchair_chkBox.datatest,
              "ig"
            );

            // Languages.
            $(this).removeClass("language-match");
            const language_selected = $("[name=language]").val().trim();
            let language_other_test = null;

            if (language_selected !== "en") {
              if (language_selected !== "asl") {
                let language_other_regExtest = new RegExp("rt", "ig");
                language_other_test = $(this)
                  .attr("data-language")
                  .match(language_other_regExtest);
              } else {
                language_other_test = $(this)[0].hasAttribute(
                  "data-remote-asl"
                );
              }
            }

            const language_regExTest = new RegExp(language_selected, "ig");

            const language_test = $(this)
              .attr("data-language")
              .match(language_regExTest);

            if (language_test || language_other_test) {
              $(this).addClass("language-match");
            }

            // "Drive-thru or walk-thru" select (Access mode).
            const access_mode_regExTest = new RegExp(
              $("[name=access_mode]").val().trim(),
              "ig"
            );

            // Distance.
            $(this).addClass(class_match_radius);
            const distance = getDistance(
              locationInput[0].getAttribute("data-lat"), //input lat
              locationInput[0].getAttribute("data-lng"), //input long
              $(this).data("lat"), // this lat
              $(this).data("lng") // this lng
            );

            if (distance > radiusInput.val()) {
              $(this).removeClass(class_match_radius);
            }

            $(this)
              .find("span.distance")
              .text(userLocation ? Math.round(distance * 10) / 10 + "mi" : "");

            // Return list of matching sites.
            rtnData =
              $(this).attr("data-restrictions").match(restrictions_regExTest) &&
              $(this).attr("data-wheelchair").match(wheelchair_regExTest) &&
              $(this).attr("data-access-mode").match(access_mode_regExTest) &&
              $(this).hasClass("language-match") &&
              $(this).hasClass(class_match_available) &&
              $(this).hasClass(class_match_radius);

            return rtnData;
          })
          .show()
          .addClass("included");
      }

      function showNoResultsMessage() {
        $(".vaccine-filter__empty").show();
      }

      function hideNoResultsMessage() {
        $(".vaccine-filter__empty").hide();
      }

      function showCount(speed) {
        let count = $(".vaccine-site.included").length;
        sectionCount.find("span").text(count);
        sectionCount.show();
      }

      function hideCount() {
        sectionCount.hide();
      }

      function showSites() {
        $(".vaccine-filter__sites").show();
      }

      function hideSites() {
        $(".vaccine-filter__sites").hide();
      }

      // @see https://en.wikipedia.org/wiki/Haversine_formula
      // @see https://simplemaps.com/resources/location-distance
      function getDistance(lat1, lng1, lat2, lng2) {
        function deg2rad(deg) {
          return deg * (Math.PI / 180);
        }
        function square(x) {
          return Math.pow(x, 2);
        }
        const r = 6371; // radius of the earth in km
        lat1 = deg2rad(lat1);
        lat2 = deg2rad(lat2);
        const lat_dif = lat2 - lat1;
        const lng_dif = deg2rad(lng2 - lng1);
        const a =
          square(Math.sin(lat_dif / 2)) +
          Math.cos(lat1) * Math.cos(lat2) * square(Math.sin(lng_dif / 2));
        let d = 2 * r * Math.asin(Math.sqrt(a));

        return d * 0.621371; // Return miles.
      }

      // This is the main function.
      function displaySites() {
        filterVaccineSites();

        if (
          // If there are no results.
          $(".vaccine-filter__sites .included").length === 0
        ) {
          hideCount();
          hideSites();
          showNoResultsMessage();
        } else {
          // If "Only show sites with available appointments" is not checked and
          // there are sites that meet the selected criteria.
          hideNoResultsMessage();
          showSites();
          showCount();
        }
      }

      // Responsive layout.
      function layoutChange(e) {
        if (e.matches) {
          $(".vaccine-filter__filters").appendTo(".group--right");
        } else {
          $(".vaccine-filter__filters").appendTo(
            ".vaccine-filter__filter-top > div"
          );
        }
      }

      // Scroll to Top.
      function scrollUp(speed) {
        let newPosition = sectionCount.offset().top - 150;
        $("html, body").animate({ scrollTop: newPosition }, speed);
      }
    },
  };
})(jQuery, Drupal);
