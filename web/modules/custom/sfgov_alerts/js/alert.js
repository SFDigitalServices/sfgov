(function ($) {
  Drupal.behaviors.applyAlertAttributesToLInk = {
    attach: function (context) {
      
      // Apply Alert data attributes to its link.
      const alert = $('.alert');
      const alertLink = $('.alert a');
      
      if (alert.length > 0 && alertLink.length > 0) {
        
        const alertStyle = alert.attr('data-style');
        const alertExp = alert.attr('data-exp');
        
        alertLink.attr({
          'data-style': alertStyle,
          'data-exp': alertExp,
        });
        
      }
      
    }
  };
})(jQuery);
