(function($) {
  var topSearchSuggsSelector = '.sfgov-top-search-suggestion';
  var topSearchSuggs = $(topSearchSuggsSelector);
  if(topSearchSuggs.length > 0) {
    var containerId = 'sfgov-top-search-suggestions-container';
    var containerSelector = '#' + containerId;
    
    $('#edit-keyword').attr('autocomplete', 'off');
    
    $(topSearchSuggs[0]).before('<div id="' + containerId + '"><h4>Top Searches:</h4></div>');
    $(containerSelector).hide();

    $(topSearchSuggs).each(function() {
      $(containerSelector).append($(this));
    });

    $('#views-exposed-form-search-page-1').after($(containerSelector));
    
    $('#edit-keyword').focus(function() {
      $(containerSelector).show();
      $(topSearchSuggsSelector).show();
    });

    $('body').click(function(e) {
      var clickTarget = $(e.target);
      if(clickTarget.attr('id') == 'edit-keyword') {

      } else {
        $(containerSelector).hide();
      }
    });
  }

})(jQuery);