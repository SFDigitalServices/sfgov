(function ($) {
  Drupal.behaviors.parent_departments = {
    attach: function (context, settings) {
      $(once('departments'), $(context).find('.field--name-field-departments input[type="text"]'))
        .on("autocompleteclose", function (event, node) {
          $(".field-department-submit-wrapper input").click()
          $(".field--name-field-departments .description").after(
            '<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div><div class="message">Please wait...</div></div>'
          )
        })
    },
  }
})(jQuery)
