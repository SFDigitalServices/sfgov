(function($, Drupal) {
  'use strict';

  Drupal.behaviors.meeting = {
    attach: function(context, settings) {
      $('.meeting-list-filters-form .form-wrapper').addClass('closed');
      $('.meeting-list-filters-form .form-wrapper').once('meeting-view').append('<a class="meeting-list-filters-toggle"></a>');
      $('.meeting-list-filters-toggle').once('meeting-view').click(function() {
        $('.meeting-list-filters-form .form-wrapper').toggleClass('closed');
      });
    },
  };
})(jQuery, Drupal);
