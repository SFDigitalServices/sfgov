(function ($) {
  Drupal.behaviors.get_reviewers_for_transactions = {
    attach: function (context, settings) {
      $(document)
        .once("getReviewers")
        .ajaxComplete(function (event, xhr, settings) {
          if (
            settings.extraData._triggering_element_name ===
            "sfgov_department_fetch"
          ) {
            let $el = $('[name="sfgov_reviewer_fetch"]');
            $el.click();
          }
        });
    },
  };
  Drupal.behaviors.parent_departments = {
    attach: function (context, settings) {
      $('.field--name-field-departments').find('input[type="text"]').once('departments').on('autocompleteclose', function(event, node) {
        $('.field-department-submit-wrapper input').click();
        $('.field--name-field-departments .description').after('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div><div class="message">Please wait...</div></div>');
      });
    }
  };
})(jQuery);
