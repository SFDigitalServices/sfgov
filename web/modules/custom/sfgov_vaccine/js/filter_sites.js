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

          const eligibility_options = ["65", " hw", "ec", "af", "sd", "es"];

          for (const eligibility_option of eligibility_options) {
            let eligibility_select = { eligibility_option: { datatest: null } };

            if (
              $(`[name="eligibility[${eligibility_option}]"]`).is(
                ":checked"
              ) === true
            ) {
              eligibility_select.eligibility_option.datatest = "1";
            } else {
              eligibility_select.eligibility_option.datatest = "";
            }
          }
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

              rtnData =
                $(this)
                  .attr("data-restrictions")
                  .match(restrictions_regExTest) &&
                $(this).attr("data-available").match(available_regExTest) &&
                $(this).attr("data-wheelchair").match(wheelchair_regExTest) &&
                $(this).attr("data-language").match(language_regExTest) &&
                $(this).attr("data-access-mode").match(access_mode_regExTest);

              return rtnData;
            })
            .show();
        }
      );
    },
  };
})(jQuery, Drupal);
