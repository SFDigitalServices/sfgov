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
      const sitesWrapper = $(".vaccine-filter__sites");
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
        
        // Clear GPS data, check for autocomplete entry
        if(!$("#edit-location")[0].getAttribute("data-place-changed")) {
		  // Remove previous GPS, ***BUG*** not working for invalid address handling
          document.getElementById("edit-location").removeAttribute("data-lat");
          document.getElementById("edit-location").removeAttribute("data-lng");
        }
        
        locationSubmit()
          .then(displaySites)
          .catch(error => console.error(error))
          .finally(() => {
            if($("[name=location]")[0].getAttribute("data-lat") && $("[name=location]")[0].getAttribute("data-lng")) {
              leftColumn.fadeIn(speed);
              scrollUp(speed);
            } else if($(".vaccine-filter__sites .included").length > 0) {
              leftColumn.fadeIn(speed);
              scrollUp(speed);
            }
            
          })
      });

      function filterVaccineSites() {
        let wheelchair_chkBox = { datatest: null };
        let kids5to11_chkBox = { datatest: null };
        let locationInput = $("[name=location]")
          .removeAttr('data-place-changed')
        let radiusInput = $("[name=radius]");
        let userLocation = !!locationInput.val();

        if ($("[name=kids5to11]").is(":checked")) {
          kids5to11_chkBox.datatest = '1'
        } else {
          kids5to11_chkBox.datatest = ''
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
            const $site = $(this)
            // "Only show sites open to the general public" checkbox.
            const kids5to11_regExTest = new RegExp(kids5to11_chkBox.datatest, "ig");

            // "Only show sites with available appointments" checkbox.
            if (filterByAvailability === true) {
              $site.removeClass(class_match_available);
              if (
                $site.attr("data-available") === "yes" ||
                $site.find(".js-dropin").length !== 0
              ) {
                $site
                  .addClass(class_match_available)
                  .appendTo(".vaccine-filter__sites");
              }
            } else {
              $site
                .addClass(class_match_available)
                .appendTo(".vaccine-filter__sites");
            }

            // "Wheelchair accessible" checkbox.
            const wheelchair_regExTest = new RegExp(
              wheelchair_chkBox.datatest,
              "ig"
            );

            // Languages.
            $site.removeClass("language-match");
            const language_selected = $("[name=language]").val().trim();
            let language_other_test = null;

            if (language_selected !== "en") {
              if (language_selected !== "asl") {
                let language_other_regExtest = new RegExp("rt", "ig");
                language_other_test = $site
                  .attr("data-language")
                  .match(language_other_regExtest);
              } else {
                language_other_test = $site[0].hasAttribute(
                  "data-remote-asl"
                );
              }
            }

            const language_regExTest = new RegExp(language_selected, "ig");

            const language_test = $site
              .attr("data-language")
              .match(language_regExTest);

            if (language_test || language_other_test) {
              $site.addClass("language-match");
            }

            // "Drive-thru or walk-thru" select (Access mode).
            const access_mode_regExTest = new RegExp(
              $("[name=access_mode]").val().trim(),
              "ig"
            );

            // Distance.
            $site.addClass(class_match_radius);
            if (userLocation) {
              if(locationInput[0].getAttribute("data-lat") && locationInput[0].getAttribute("data-lng")) {
                const distance = getDistance(
                  locationInput[0].getAttribute("data-lat"), //input lat
                  locationInput[0].getAttribute("data-lng"), //input long
                  $site.data("lat"), // this lat
                  $site.data("lng") // this lng
                );
  
                if (distance > radiusInput.val()) {
                  $site.removeClass(class_match_radius);
                }
                $site
                  .attr("data-distance", distance)
                  .find(".vaccine-site__distance")
                  .text(Math.round(distance * 10) / 10 + "mi");
                $site
                  .find(".vaccine-site__header")
                  .addClass("distance-visible");
              } else {
                $site.addClass('included');
              }
            } else {
              $site.find(".vaccine-site__distance").text("");
            }

            // Return list of matching sites.
            return (
              $site.attr("data-kids5to11").match(kids5to11_regExTest) &&
              $site.attr("data-wheelchair").match(wheelchair_regExTest) &&
              $site.attr("data-access-mode").match(access_mode_regExTest) &&
              $site.hasClass("language-match") &&
              $site.hasClass(class_match_available) &&
              $site.hasClass(class_match_radius)
            );
          })
          .sort(function (a, b) {
            const orderA = parseInt(a.getAttribute("data-order"));
            const orderB = parseInt(b.getAttribute("data-order"));

            let dataA = orderA;
            let dataB = orderB;

            // Sort by distance and then order if location is entered.
            if (userLocation) {
              dataA = a.getAttribute("data-distance");
              dataB = b.getAttribute("data-distance");

              if (dataA === dataB) {
                dataA = orderA;
                dataB = orderB;
              }
            }

            return dataA < dataB ? -1 : 1;
          })
          .show()
          .addClass("included")
          .appendTo(sitesWrapper);
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

        return Math.round(d * 0.621371 * 10) / 10; // Return miles.
      }

      // This is the main function.
      function displaySites() {
        // Check for long/lat coords from user location
        filterVaccineSites();
        
        if($("[name=location]")[0].getAttribute("data-lat") && $("[name=location]")[0].getAttribute("data-lat")) {
          // Location found, check results and show if valid
          if (
            // If there are no results.
            $(".vaccine-filter__sites .included").length === 0
          ) {
            hideCount();
            hideSites();
            showNoResultsMessage();   
          } else if(
            ($("[name=location]")[0].getAttribute("data-lng") < -122.93 ||
            $("[name=location]")[0].getAttribute("data-lng") > -121.54) && 
            ($("[name=location]")[0].getAttribute("data-lat") < 37.0000 ||
            $("[name=location]")[0].getAttribute("data-lat") > 38.0200)
          ) {
            // GPS coords out of bounds
            hideCount();
            hideSites();
            $('.vaccine-filter__count').html('<span>Invalid address or zip code entered</span>');
          } else {
            // If "Only show sites with available appointments" is not checked and
            // there are sites that meet the selected criteria.
            hideNoResultsMessage();
            showSites();
            showCount();
          }
        } else if($("#edit-location").val()) {
          // Invalid location entered, alert user
          hideSites();
          showCount();
          $(".vaccine-filter__sites").hide();
          $('.vaccine-filter__count').html('<span>Invalid address or zip code entered</span>');
        } else {
          // No location entered, show results
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
