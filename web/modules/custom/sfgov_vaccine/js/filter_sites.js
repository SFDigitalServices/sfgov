(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.filterSites = {
    attach: function (context) {
      // @todo Banish the jquery!

      // Set media query.
      const mediaQuery = window.matchMedia("(min-width: 768px)");

      // On load.
      filterVaccineSites();
      layoutChange(mediaQuery);

      // Register event listener.
      mediaQuery.addListener(layoutChange);

      $(".vaccine-filter-form #edit-submit", context).on(
        "click",
        function (event) {
          event.preventDefault();
          filterVaccineSites();
          showNoResultsMessage();
        }
      );

      function filterVaccineSites() {
        let restrictions_chkBox = { datatest: null };
        let available_chkBox = { datatest: null };
        let wheelchair_chkBox = { datatest: null };
        let groupByAvailability = false;

        if ($("[name=restrictions]").is(":checked") === true) {
          // show
          restrictions_chkBox.datatest = "0";
        } else {
          //hide
          restrictions_chkBox.datatest = "";
        }

        if ($("[name=available]").is(":checked") === true) {
          groupByAvailability = true;
        }

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

        // `elibibility_select` should be an an array of strings
        // ["none", "hw", "none", "none", "none", "none"]
        // ["all", "all", "all", "all", "all", "all"]
        $(".vaccine-site")
          .hide()
          .filter(function () {
            let rtnData = "";

            const restrictions_regExTest = new RegExp(
              restrictions_chkBox.datatest,
              "ig"
            );
            const available_regExTest = new RegExp(
              available_chkBox.datatest,
              "ig"
            );

            if (groupByAvailability === true) {
              $(".vaccine-filter__other").removeAttr("hidden");
              if (
                $(this).attr("data-available") === "1" ||
                $(this).find(".dropin").length !== 0
              ) {
                $(this).appendTo(".vaccine-filter__sites");
              } else {
                $(this).appendTo(".vaccine-filter__other-sites");
              }
            } else {
              $(this).appendTo(".vaccine-filter__sites");
              $(".vaccine-filter__other").attr("hidden", true);
            }

            const wheelchair_regExTest = new RegExp(
              wheelchair_chkBox.datatest,
              "ig"
            );

            const language_regExTest = new RegExp(
              $("[name=language]").val().trim(),
              "ig"
            );

            const access_mode_regExTest = new RegExp(
              $("[name=access_mode]").val().trim(),
              "ig"
            );

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
              $(this).attr("data-language").match(language_regExTest) &&
              $(this).attr("data-access-mode").match(access_mode_regExTest) &&
              $(this).hasClass("eligible");

            return rtnData;
          })
          .sort(function (a, b) {
            const dataA = $(a).data("available");
            const dataB = $(b).data("available");
            return dataA < dataB;
          })
          .show();
      }

      function showNoResultsMessage() {
        if ($(".vaccine-site:visible").length === 0) {
          $(".vaccine-filter__empty").removeAttr("hidden");
        } else {
          $(".vaccine-filter__empty").attr("hidden", true);
        }
      }

      function layoutChange(e) {
        if (e.matches) {
          $(".vaccine-filter__filters").appendTo(".group--right");
        } else {
          $(".vaccine-filter__filters").appendTo(
            ".vaccine-filter__filter-top > div"
          );
        }
      }
    },
  };
})(jQuery, Drupal);
