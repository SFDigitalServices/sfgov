(function ($) {
  'use strict';

  Drupal.behaviors.office_hours = {
    attach: function (context, settings) {

      // Hide every item above the max slots per day.
      $('.office-hours-hide').hide();

      // If the next time slot is shown, don't show the Add-link.
      $('.office-hours-add-link').each(function (i) {
          var $next_tr;

          $next_tr = $(this).closest('tr').next();
          if (!$next_tr.is(':hidden')) {
            $(this).hide();
          }
        })
        // Attach a function to each Add link to show the next slot if clicked upon.
        .bind('click', show_time_slot);

      fix_striping();

      // Clear the content of this slot, when user clicks "Clear/Remove".
      // Do this for both widgets.
      $('.office-hours-delete-link').bind('click', function (e) {
        e.preventDefault();
        // Clear the hours, minutes in the select box.
        $(this).parent().parent().find('.form-select').each(function () {
          $(this).val($("#target").find("option:first").val());
        });
        // Clear the hours, minutes in the HTML5 time element.
        $(this).parent().parent().find('.form-time').each(function () {
          $(this).val($("#target").find("option:first").val());
        });
        // Clear the comment.
        $(this).parent().parent().find('.form-text').each(function () {
          $(this).val($("#target").find("option:first").val());
        });
      });

      // Copy values from previous day, when user clicks "Copy previous day".
      $('.office-hours-copy-link').bind('click', function (e) {
        e.preventDefault();
        // @todo: current_day works for Table widget, not yet for List Widget.
        var current_day = parseInt($(this).closest('tr').find('input')[0].value);
        var previous_day = current_day - 1;
        if (current_day == 0) {
          previous_day = current_day + 6;
        }

        // Select current table.
        var tbody = $(this).closest('tbody');
        // Div's from previous day.
        var previous_selector = tbody.find('.office-hours-day-' + previous_day);
        // Div's from current day.
        var current_selector = tbody.find('.office-hours-day-' + current_day);

        // For better UX, first copy the comments, then hours and fadeIn
        // Copy the comment.
        previous_selector.find('.form-text').each(function (index, value) {
          set_time_slot_value(current_selector.find('.form-text').eq(index), $(this).val());
        });
        // Copy the hours, minutes in the select box.
        previous_selector.find('.form-select').each(function (index, value) {
          set_time_slot_value(current_selector.find('.form-select').eq(index), $(this).val());
        });
        // Copy the hours, minutes in the select list/HTML5 time element.
        previous_selector.find('.form-time').each(function (index, value) {
          set_time_slot_value(current_selector.find('.form-time').eq(index), $(this).val());
        });

        // Hide the Add time slot, after "Copy previous day".
        previous_selector.find('.office-hours-add-link').each(function (index, value) {
          current_selector.find('.office-hours-add-link').eq(index).hide();
          // Next tr's 'add-link'.
          var next_add_link = current_selector.next().find('.office-hours-add-link');
          if (next_add_link.is(':hidden') && current_selector.next().is(':hidden')) {
            current_selector.find('.office-hours-add-link').eq(index).show();
          }
        });

        // Set/Clear the value of a slot item, and show it slowly if a new item is filled.
        function set_time_slot_value(form_item, value) {
          form_item.val(value);
          if (value) {
            // Show the next item, slowly.
            form_item.closest('tr').fadeIn('slow');
          }
        }
      });

      // Show an office-hours-slot, when user clicks "Add more".
      function show_time_slot(e) {
        var $next_tr;
        e.preventDefault();

        // Hide the link, the user clicked upon.
        $(this).hide();

        // Show the next item, slowly.
        $next_tr = $(this).closest('tr').next();
        $next_tr.fadeIn('slow');

        fix_striping();
        return false;
      }

      // Function to traverse visible rows and apply even/odd classes.
      function fix_striping() {
        $('tbody tr:visible', context).each(function (i) {
          if (i % 2 === 0) {
            $(this).removeClass('odd').addClass('even');
          }
          else {
            $(this).removeClass('even').addClass('odd');
          }
        });
      }
    }
  };
})(jQuery);
