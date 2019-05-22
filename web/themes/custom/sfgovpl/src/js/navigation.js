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

        var checkWindowSize = function() {
            var bp = 768;
            var windowWidth = $(window).width();
            if(windowWidth <= bp) {
                $('nav.sfgov-nav').css({height:$(window).height()+'px'});
            } else {
                $('nav.sfgov-nav').css({height:'auto'});
            }
        }

        $('button.sfgov-mobile-search').click(function() {
            $('header .sfgov-search-311-block').show();
            $('body').removeClass('sfgov-mobile_nav-active');
        });

        $('header .sfgov-search-311-block .sfgov-mobile-btn-close').click(function() {
            $('header .sfgov-search-311-block').attr("style", false);
        });

        $('button.sfgov-menu-btn').click(function() {
            $('header .sfgov-search-311-block').attr("style", false);
        });

        $('button.sfgov-mobile-translate').click(function() {
            $('#block-gtranslate').show();
        });

        $(window).on('resize', function() {
            checkWindowSize();
        });

        checkWindowSize();
    });
})(jQuery);
