(function($, Drupal) {

  Drupal.behaviors.viewfield = {

    attach: function(context, settings) {
      $(Drupal.ajax.instances).once('disableFormElementsDuringAjaxCallback').each(function (index, instance) {
        if ($.inArray('ajaxGetDisplayOptions', instance.callback) !== -1) {
          instance.options.beforeSubmit = $.fn.ajaxDisableElements;
        }
      })
    }
  };

  // Disable form elements
  $.fn.ajaxDisableElements = function (form_values, form, options) {
    var element = options.extraData['_triggering_element_name'];
    var display_id = element.replace('target_id', 'display_id');
    $("[name='" + display_id + "']")
      .not(':disabled')
      .css('color', 'graytext')
      .attr('disabled', true);
  };

  // Enable form elements
  $.fn.ajaxEnableElements = function () {
    $(this)
      .css('color', '')
      .attr('disabled', false);
  };

})(jQuery, Drupal);