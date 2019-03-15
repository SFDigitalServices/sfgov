(function($) {
  var topSearchSuggsSelector = '.sfgov-top-search-suggestion';
  var topSearchSuggs = $(topSearchSuggsSelector);
  if(topSearchSuggs.length > 0) {
    var containerId = 'sfgov-top-search-suggestions-container';
    var containerSelector = '#' + containerId;
    
    $('#edit-keyword, #edit-sfgov-search-input').attr('autocomplete', 'off');
    
    $(topSearchSuggs[0]).before('<div id="' + containerId + '"><h4>Top searches:</h4></div>');
    $(containerSelector).hide();

    $(topSearchSuggs).each(function() {
      $(containerSelector).append($(this));
    });

    $('#views-exposed-form-search-page-1, .sfgov-search-form-311').append($(containerSelector));
    
    $('#edit-keyword, #edit-sfgov-search-input').focus(function() {
      if($(this).val().length <= 0) {
        $(containerSelector).show();
        $(topSearchSuggsSelector).show();
      }
    });

    $('#edit-keyword, #edit-sfgov-search-input').keyup(function() {
      if($(this).val().length <= 0) {
        $(containerSelector).show();
      } else {
        $(containerSelector).hide();
      }
    });

    $('body').click(function(e) {
      var clickTarget = $(e.target);
      if(clickTarget.attr('id') != 'edit-keyword' && $(clickTarget).attr('id') != 'edit-sfgov-search-input') {
        $(containerSelector).hide();
      }
    });
  }

})(jQuery);