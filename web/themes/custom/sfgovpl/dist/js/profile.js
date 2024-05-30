(function ($) {
  $('body.page-node-type-person').each(() => {
    const bioTrimmedLast = $('.bio-trimmed').children().last();
    const bioTrimmedLastText = $(bioTrimmedLast).text();
    $(bioTrimmedLast).text(bioTrimmedLastText.substr(0, bioTrimmedLastText.length - 3) + '...');
    $('.show-bio').click(e => {
      e.preventDefault();
      $('.bio-trimmed').css('display', 'none');
      $('.bio-full').css('display', 'inline-block');
      $('.show-bio').css('display', 'none');
      $('.hide-bio').css('display', 'inline-block');
    });
    $('.hide-bio').click(e => {
      e.preventDefault();
      $('.bio-trimmed').css('display', 'inline-block');
      $('.bio-full').css('display', 'none');
      $('.show-bio').css('display', 'inline-block');
      $('.hide-bio').css('display', 'none');
    });
  });
})(jQuery);