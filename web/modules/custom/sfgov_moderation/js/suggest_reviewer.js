(function ($) {
  Drupal.behaviors.reviewerWarning = {
    attach: function (context) {
      // Set variables.
      const $reviewerWrapper = $(
        '[data-drupal-selector="edit-reviewer-wrapper"]'
      );
      const $reviewerField = $reviewerWrapper.find('[name="reviewer"]');
      const $moderationStateField = $('[name="moderation_state[0][state]"]');
      const $inputs = $(
        '[name="reviewer"], [name="moderation_state[0][state]"]'
      );
      const $message = $reviewerField.next(".description");
      const $er = "error";

      // Evaluate state and respond.
      const reviewFieldState = () => {
        if (
          $moderationStateField.val() === "ready_for_review" &&
          $reviewerField.find("option").length > 1
        ) {
          // Show reviewer field.
          $reviewerWrapper.show();

          if ($reviewerField.val() === "_none") {
            // Highlight field.
            $reviewerField.addClass($er);
            $message.addClass($er);
          } else {
            // Remove field highlight.
            $reviewerField.removeClass($er);
            $message.removeClass($er);
          }
        } else {
          // Hide reviewer field.
          $reviewerWrapper.hide();
        }
      };

      // Run function on page load and on change.
      reviewFieldState();
      $inputs.on("change", reviewFieldState);
    },
  };
})(jQuery);
