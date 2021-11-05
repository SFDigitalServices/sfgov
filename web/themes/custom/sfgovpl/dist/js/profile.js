"use strict";

(function ($) {
  $('body.page-node-type-person').each(function () {
    var bioTrimmedLast = $('.bio-trimmed').children().last();
    var bioTrimmedLastText = $(bioTrimmedLast).text();
    $(bioTrimmedLast).text(bioTrimmedLastText.substr(0, bioTrimmedLastText.length - 3) + '...');
    $('.show-bio').click(function (e) {
      e.preventDefault();
      $('.bio-trimmed').css("display", "none");
      $('.bio-full').css("display", "inline-block");
      $('.show-bio').css("display", "none");
      $('.hide-bio').css("display", "inline-block");
    });
    $('.hide-bio').click(function (e) {
      e.preventDefault();
      $('.bio-trimmed').css("display", "inline-block");
      $('.bio-full').css("display", "none");
      $('.show-bio').css("display", "inline-block");
      $('.hide-bio').css("display", "none");
    });
  });
})(jQuery);