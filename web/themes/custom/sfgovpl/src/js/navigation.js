(function($){
    $().ready(function(){
        /*
         * Iterate though navItems and check if it is in the href.
         * If yes, then add is-active class to corresponding menu link.
         */
        var href = document.location.href.toLowerCase();
        var navItems = ["services", "departments"];

        for (var i=0, l=navItems.length; i<l; i++) {
            var nav = navItems[i];
            if (new RegExp("/"+nav).test(href)){
                $('.sfgov-main-navigation ul.menu a[data-drupal-link-system-path='+nav+']').addClass('is-active');
                break;
            };
        };

        $('button.sfgov-mobile-search').click(function() {
          $('header .sfgov-search-311-block').show();
          $('body').removeClass('sfgov-mobile_nav-active');
          $('.sf-gov-search-input-class').focus();
          $('button.sfgov-menu-btn').removeClass('is-active');
        });

        $('header .sfgov-search-311-block .sfgov-mobile-btn-close').click(function() {
            $('header .sfgov-search-311-block').hide();
        });

        $('button.sfgov-menu-btn').click(function() {
            $('header .sfgov-search-311-block').hide();
            $(this).toggleClass('is-active');
          $('body').toggleClass('sfgov-mobile_nav-active');
        });

        $('button.sfgov-mobile-translate').click(function() {
            $('#block-gtranslate').show();
        });
  
      // Search Clear Input functionality.
      var searchClearInput = function () {
    
        var input = $('[data-drupal-selector="edit-sfgov-search-input"]');
        var inputWrapper = $('.form-item-sfgov-search-input');
        var inputClearButton = '<span class="input-clear"></span>';
    
        inputWrapper.prepend(inputClearButton);
    
        input.keyup(function () {
          var parent = $(this).parent('.form-item-sfgov-search-input')
      
          if ($(this).val() != '') {
            parent.addClass('is_typing');
          }
          else {
            parent.removeClass('is_typing');
          }
        });
    
        $('.input-clear').click(function () {
          input.val('');
          inputWrapper.removeClass('is_typing');
        });
      };
    
    searchClearInput();
  });
})(jQuery);
