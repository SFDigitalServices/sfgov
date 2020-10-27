(function ($, Drupal) {
  // Add functionality to clear video field content on meeting nodes
  $('#edit-group-recording').each(function () {
    // Create clear all button
    var clearbtn = document.createElement("span");
    clearbtn.setAttribute('id', 'recording-clear');
    clearbtn.innerHTML = 'Clear all field values';
    this.prepend(clearbtn);
    $('#recording-clear').css({"border": "1px solid #a6a6a6", "border-radius": "5px", "padding": "10px", "margin-left": "20px", "font-weight": "bold", "background": "-webkit-linear-gradient(top, #f6f6f3, #e7e7df)"});
    
    // Clear all recording input fields
    $('#recording-clear').click(function(){
      $('#edit-field-video-option').val('_none');
      $('#edit-field-title-0-value').val('');
      $('#edit-field-intro-text-0-value').val('');
      $('#edit-field-video-embed-0-value').val('');
      $('#edit-field-url-0-uri').val('');
      $('#edit-field-url-0-title').val('');
      CKEDITOR.instances['edit-field-intro-text-0-value'].setData('');
    });
  });
})(jQuery, Drupal);
