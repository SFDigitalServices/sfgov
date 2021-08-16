(function($) {
  var removeButton = $('.remove-field-delta--0');
  if (removeButton.length > 0) {
    var relatedField = $('div.js-form-item-field-related-content-0-target-id');
    relatedField.append('<p class="remove-button-warning">To remove a related item, remove the text and save the page.</p>');
  }
}(jQuery));