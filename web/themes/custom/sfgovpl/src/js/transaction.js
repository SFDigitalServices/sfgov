(function($) {
  $('.page-node-type-transaction').each(function() {
    if(SFGOV.util.getParam('from') === 'sbs') {
      $('.hero-banner-label').show();
    }
  });
}(jQuery));