(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.filterSites = {
    attach: function (context) {
      // @todo Banish the jquery!

      $(".vaccine-filter-form #edit-submit", context).on(
        "click",
        function (event) {
          event.preventDefault();

          let chkBox = { datatest: null };

          if ($("[name=restrictions]").is(":checked")) {
            chkBox.datatest = "1";
          } else {
            chkBox.datatest = "0";
          }

          $(".vaccine-site")
            .show()
            .filter(function () {
              let rtnData = "";

              // const regExName = new RegExp(
              //   $("[name=restrictions]").prop("checked"),
              //   "ig"
              // );

              // const regExA = new RegExp(
              //   $("[name=available]").val().trim(),
              //   "ig"
              // );
              // const regExB = new RegExp(
              //   $("[name=wheelchair]").val().trim(),
              //   "ig"
              // );
              const regExTest = new RegExp(chkBox.datatest, "ig");

              rtnData = $(this).attr("data-restrictions").match(regExTest); //&&
              // $(this).attr("data-available").match(regExA) &&
              // $(this).attr("data-wheelchair").match(regExB) &&
              // $(this).attr("data-language").match(regExTest)

              //console.log(rtnData);
              return rtnData;
            })
            .hide();
        }
      );
    },
  };
})(jQuery, Drupal);
