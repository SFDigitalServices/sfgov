(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.filterSites = {
    attach: function (context) {
      // @todo Banish the jquery!

      $(".vaccine-filter-form #edit-submit", context).on(
        "click",
        function (event) {
          event.preventDefault();

          let restrictions_chkBox = { datatest: null };
          let available_chkBox = { datatest: null };
          let wheelchair_chkBox = { datatest: null };

          if ($("[name=restrictions]").is(":checked") === true) {
            // show
            restrictions_chkBox.datatest = "0";
          } else {
            //hide
            restrictions_chkBox.datatest = "";
          }

          if ($("[name=available]").is(":checked") === true) {
            available_chkBox.datatest = "1";
          } else {
            available_chkBox.datatest = "";
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
              $(`[name="eligibility[${eligibility_option}]"]`).is(
                ":checked"
              ) === true
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
              let rtnData2 = "";

              const restrictions_regExTest = new RegExp(
                restrictions_chkBox.datatest,
                "ig"
              );
              const available_regExTest = new RegExp(
                available_chkBox.datatest,
                "ig"
              );

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

              let eligibility_tests = [];

              for (const eligibility_option in eligibily_datatests) {
                let eligibility_regExTest = new RegExp(
                  eligibility_select[eligibility_option],
                  "ig"
                );
                // `eligibility_regExTest` should be a string /hw/gi, /none/gi, /all/gi

                const eligibility_test = $(this)
                  .attr("data-eligibility")
                  .match(eligibility_regExTest);

                eligibility_tests.push(eligibility_test);
              }

              rtnData =
                $(this)
                  .attr("data-restrictions")
                  .match(restrictions_regExTest) &&
                $(this).attr("data-available").match(available_regExTest) &&
                $(this).attr("data-wheelchair").match(wheelchair_regExTest) &&
                $(this).attr("data-language").match(language_regExTest) &&
                $(this).attr("data-access-mode").match(access_mode_regExTest) &&
                eligibility_tests[1];

              return rtnData;
            })
            .show();
        }
      );
    },
  };
})(jQuery, Drupal);
