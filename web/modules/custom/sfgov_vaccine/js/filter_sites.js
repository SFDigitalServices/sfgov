(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.filterSites = {
    attach: function (context) {
      // @todo Banish the jquery!

      // Set media query and register event listener.
      const mediaQuery = window.matchMedia("(min-width: 768px)");
      mediaQuery.addListener(layoutChange);

      // Elements.
      const sectionCount = $(".vaccine-filter__count");
      const leftColumn = $(".group--left");
      const submitButton = $(".vaccine-filter-form #edit-submit", context);

      // Other variables.
      let groupByAvailability = false;
      const speed = "slow";
      const class_match_available = "match-available";

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

        if ($("[name=restrictions]").is(":checked") === true) {
          // show
          restrictions_chkBox.datatest = "0";
        } else {
          //hide
          restrictions_chkBox.datatest = "";
        }

        groupByAvailability = $("[name=available]").is(":checked");

        if ($("[name=wheelchair]").is(":checked") === true) {
          wheelchair_chkBox.datatest = "1";
        } else {
          wheelchair_chkBox.datatest = "";
        }

        let eligibily_datatests = ["sf", "hw", "ec", "af", "sd", "es"];
        let eligibility_select = [];

        for (let i in eligibily_datatests) {
          let eligibility_option = eligibily_datatests[i];

          // No eligibility selected.
          if ($(`[name^="eligibility"]:checked`).length === 0) {
            eligibility_select.push("all");
          }

          // this option in the array selected
          else if (
            $(`[name="eligibility[${eligibility_option}]"]`).is(":checked") ===
            true
          ) {
            eligibility_select.push(eligibility_option);

            // this option in the array not selected
          } else {
            eligibility_select.push("none");
          }
        }

        // `eligibility_select` should be an an array of strings.
        // ["none", "hw", "none", "none", "none", "none"]
        // ["all", "all", "all", "all", "all", "all"]
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
            if (groupByAvailability === true) {
              $(this).removeClass(class_match_available);
              if (
                $(this).attr("data-available") === "yes" ||
                $(this).find(".js-dropin").length !== 0
              ) {
                $(this).appendTo(".vaccine-filter__sites");
                $(this).addClass(class_match_available);
              } else if ($(this).attr("data-available") === "null") {
                $(this).appendTo(".vaccine-filter__other-sites");
                $(this).addClass(class_match_available);
              } else {
                $(this).removeClass(class_match_available);
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
              let language_other_regExtest = new RegExp("rt", "ig");
              language_other_test = $(this)
                .attr("data-language")
                .match(language_other_regExtest);
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

            // "Eligibility requirements" checkboxes.
            $(this).removeClass("eligible");
            for (const eligibility_option in eligibily_datatests) {
              // `eligibility_regExTest` should be a string /hw/gi, /none/gi, /all/gi
              let eligibility_regExTest = new RegExp(
                eligibility_select[eligibility_option],
                "ig"
              );

              const eligibility_test = $(this)
                .attr("data-eligibility")
                .match(eligibility_regExTest);

              if (eligibility_test) {
                $(this).addClass("eligible");
              }
            }

            rtnData =
              $(this).attr("data-restrictions").match(restrictions_regExTest) &&
              $(this).attr("data-wheelchair").match(wheelchair_regExTest) &&
              $(this).attr("data-access-mode").match(access_mode_regExTest) &&
              $(this).hasClass("eligible") &&
              $(this).hasClass("language-match") &&
              $(this).hasClass(class_match_available);

            return rtnData;
          })
          .sort(function (a, b) {
            const dataA = $(a).data("available");
            const dataB = $(b).data("available");
            return dataA < dataB;
          })
          .show()
          .addClass("included");
      }

      function showNoResultsMessage() {
        $(".vaccine-filter__empty").removeAttr("hidden");
      }

      function hideNoResultsMessage() {
        $(".vaccine-filter__empty").attr("hidden", true);
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

      function showOtherSites() {
        $(".vaccine-filter__other").show();
      }

      function hideOtherSites(speed) {
        $(".vaccine-filter__other").hide();
      }

      // This is the main function.
      function displaySites() {
        filterVaccineSites();

        if (
          // If there are no results.
          $(".vaccine-filter__other-sites .included").length === 0 &&
          $(".vaccine-filter__sites .included").length === 0
        ) {
          hideCount();
          hideSites();
          hideOtherSites();
          showNoResultsMessage();
        } else if (
          // If "Only show sites with available appointments" is checked and
          // there are sites that don't meet the selected criteria.
          groupByAvailability === true &&
          $(".vaccine-filter__other-sites .included").length > 0
        ) {
          showSites();
          showOtherSites();
          hideNoResultsMessage();
          // showCount() should be last because it depends on the other functions.
          showCount();
        } else {
          // If "Only show sites with available appointments" is not checked and
          // there are sites that meet the selected criteria.
          hideNoResultsMessage();
          showSites();
          hideOtherSites();
          // showCount() should be last because it depends on the other functions.
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
