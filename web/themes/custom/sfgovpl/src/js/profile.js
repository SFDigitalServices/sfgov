(function($) {
  $('.show-bio').click(function(e){
    e.preventDefault();
    $('.bio-trimmed').css("display", "none");
    $('.bio-full').css("display", "inline-block");  
    $('.show-bio').css("display", "none");
    $('.hide-bio').css("display", "inline-block");
  });
  $('.hide-bio').click(function(e){
    e.preventDefault();
    $('.bio-trimmed').css("display", "inline-block");
    $('.bio-full').css("display", "none");
    $('.show-bio').css("display", "inline-block");
    $('.hide-bio').css("display", "none");
  });
})(jQuery);
