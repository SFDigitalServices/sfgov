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

        /*
         * Create a jump link in main nav for Elected Officials
         *
         */
        // Check for presence of the sfgov-paragraph-people classname.
        // Its existence means that a section exists with people content.
        // Assume only one section with people content exists.
        var peopleContent = document.getElementsByClassName("sfgov-paragraph-people")[0];
        if (peopleContent) {
            // Find the nearest ancestor h2 tag and add an id to it
            var sectionParent = $(peopleContent).closest("div.paragraph--type--section");
            var electedOfficialsSectionHeader = sectionParent.find("h2.sfgov-header-section");
            electedOfficialsSectionHeader.attr("id", "elected-officials");

            // Create Elected Officials nav item and inject it into the main nav
            var navItem = document.createElement("li");
            var anchor = document.createElement("a");
            anchor.setAttribute("href", "#elected-officials");
            anchor.innerHTML = "Elected Officials";
            navItem.appendChild(anchor);
            var mainNav = document.querySelector('nav.sfgov-main-navigation ul.menu');
            if (mainNav) {
                mainNav.appendChild(navItem);
            }
        }
    })
})(jQuery);
