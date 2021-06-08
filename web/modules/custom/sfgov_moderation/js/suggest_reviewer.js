(function ($) {
  Drupal.behaviors.reviewerWarning = {
    attach: function (context) {
      const $reviewer = $('[name="reviewer"]');
      const $moderationState = $('[name="moderation_state[0][state]"]');
      const $inputs = $(
        '[name="reviewer"], [name="moderation_state[0][state]"]'
      );

      $inputs.on("change", function () {
        const $message = $reviewer.next(".description");

        if (
          $reviewer.val() === "_none" &&
          $moderationState.val() === "ready_for_review" &&
          $reviewer.find("option").length > 1
        ) {
          $reviewer.addClass("error");
          $message.addClass("error");
        } else {
          $reviewer.removeClass("error");
          $message.removeClass("error");
        }
      });
    },
  };
})(jQuery);
