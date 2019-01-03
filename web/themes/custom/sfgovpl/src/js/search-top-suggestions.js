(function($) {
  var topSearchSuggs = $('.sfgov-top-search-suggestion');
  if(topSearchSuggs.length > 0) {
    var containerId = 'sfgov-top-search-suggestions-container';
    var containerSelector = '#' + containerId;
    $('#edit-keyword').attr('autocomplete', 'off');
    $(topSearchSuggs[0]).before('<div id="' + containerId + '"><h4>Top Searches:</h4></div>');
    $(topSearchSuggs).each(function() {
      $(containerSelector).append($(this));
    });
    $(containerSelector).hide();
    $('#edit-keyword').focus(function() {
      $(containerSelector).show();
    });
    $('#edit-keyword').blur(function() {
      $(containerSelector).hide();
    })
  }

})(jQuery);