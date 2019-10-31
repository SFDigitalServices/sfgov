(function($) {
  $('.page-node-type-transaction').each(function() {
    if(SFGOV.util.getParam('from') === 'sbs' && SFGOV.util.getParam('txid') && SFGOV.util.getParam('sbsid')) {
      var txid = SFGOV.util.getParam('txid');
      var sbsid = SFGOV.util.getParam('sbsid');
      // this path is a rest export defined in the view 'transaction_step_by_step'
      $.ajax('/related-step/' + txid + '/' + sbsid, { 
        success: function(data) {
          var html = '';
          if(data && data.length > 0) {
            html += '<span>Part of </span><a href="' + data[0].url + '">Step by step: ' + data[0].title + '</a>';
          }
          $('.hero-banner--container').prepend('<div class="hero-banner-label">' + html + '</div>');
        }
      });

    }
  });
}(jQuery));