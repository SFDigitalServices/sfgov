(function($) {
  $('.show-bio').click(function(e){
    e.preventDefault();
    $('.bio-trimmed').hide();
    $('.bio-full').show();
    $('.show-bio').hide();
    $('.hide-bio').show();
  });
  $('.hide-bio').click(function(e){
    e.preventDefault();
    $('.bio-trimmed').show();
    $('.bio-full').hide();
    $('.show-bio').show();
    $('.hide-bio').hide();
  });
})(jQuery);
