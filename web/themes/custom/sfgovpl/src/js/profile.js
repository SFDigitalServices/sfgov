(function($) {
  $('.show-hide-bio').click(function(e){
    e.preventDefault();
    $('.bio-trimmed').hide();
    $('.bio-full').show();
    $('.show-hide-bio').hide();
  });
  
})(jQuery);
