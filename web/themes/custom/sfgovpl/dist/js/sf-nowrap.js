(function ($) {
  const nowrap = function (oldPhrase) {
    const newPhrase = oldPhrase.replace(' ', '\xa0');
    $('body :not(script)').contents().filter(function () {
      return this.nodeType === 3;
    }).each(function () {
      if (this.nodeValue.indexOf(oldPhrase) >= 0) {
        this.nodeValue = this.nodeValue.replace(oldPhrase, newPhrase);
      }
    });
  };
  $('document').ready(() => {
    nowrap('San Francisco');
  });
})(jQuery);