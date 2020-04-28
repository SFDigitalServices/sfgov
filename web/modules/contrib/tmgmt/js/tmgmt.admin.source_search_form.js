/**
 * @file
 * TMGMT admin source search form behaviors.
 */

(function ($, Drupal, debounce) {

  Drupal.behaviors.tmgmtAdminSourceSearchForm = {
    attach: function (context, settings) {
      var $checkUncheckAll = $('.tmgmt-source-checkout-wrapper .details-wrapper .form-item-check-target-languages .check-control a', context);

      $checkUncheckAll.on('click', function() {
        var $targetLanguagesCheckboxesWrapper = $('.tmgmt-source-checkout-wrapper .details-wrapper #edit-target-languages');
        var $targetLanguagesCheckboxes = $targetLanguagesCheckboxesWrapper.find('input[type=checkbox]');
        var checkAll = $targetLanguagesCheckboxesWrapper.find('input[type=checkbox]:checked').length;

        if (checkAll) {
          $targetLanguagesCheckboxes.prop('checked', false);
        }
        else {
          $targetLanguagesCheckboxes.prop('checked', true);
        }
      });
    }
  };

})(jQuery, Drupal, Drupal.debounce);
