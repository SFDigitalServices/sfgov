(function ($) {
  Drupal.behaviors.search404 = {
    attach: function () {
      
      var mq = window.matchMedia('(min-width: 1040px)');
      mq.addListener(switchPlaceholder);
      
      // Switch placeholder based on mq.
      function switchPlaceholder(mq) {
        var input = $('.region-fourofour input');
        
        mq.matches ? input.attr('placeholder', 'What are you looking for?') :
          input.attr('placeholder', 'Search');
      }
      
      // On load.
      switchPlaceholder(mq);
      
    }
  };
})(jQuery);
