(function ($) {
  Drupal.behaviors.parent_departments = {
    attach: function (context, settings) {
      $('.field--name-field-departments').find('input[type="text"]').once('departments').on('autocompleteclose', function(event, node) {
        $('.field-department-submit-wrapper input').click();
        $('.field--name-field-departments .description').after('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div><div class="message">Please wait...</div></div>');
      });
    }
  };
})(jQuery);
