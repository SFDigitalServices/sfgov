jQuery(function () {
  $(document).ready(function() {
    // Track if accordions have been activated
    var accordionActive = false;
    const mobileSize = 701;
    const rgroup = $('.sfgov-resources .paragraph--type--other-info-card');
    const sgroup = $('.sfgov-services');
    var rtitle = null;
    var stitle = null;

    rgroup.each(function(){
      if ($(this).hasClass('no-title')) {
        return rtitle = false;
      } else {
        return rtitle = true;
      }
    });

    sgroup.each(function(){
      if ($(this).hasClass('no-title')) {
        return stitle = false;
      } else {
        return stitle = true;
      }
    });
    
    // Check for mobile, initialize accordions
    if ($(window).width() < mobileSize) {
      // Add accordions
      if (stitle == true) {
        $('.sfgov-services-section .sfgov-services').each(function(){
          if (!$(this).find('summary').length) {
            $(this).find('h3').wrap('<summary class="details__summary"></summary>');
            $(this).find('.sfgov-dept-services-section-content').wrap('<div class="details__content"></div>');
            $(this).wrapInner('<details></details>');
          }
        });
        // More services accordion
        $('.views-element-container .sfgov-services').each(function(){
          if (!$(this).find('summary').length) {
            $(this).find('h5').wrap('<summary class="details__summary"></summary>');
            $(this).find('.sfgov-container-three-column').wrap('<div class="details__content"></div>');
            $(this).wrapInner('<details></details>');
          }
        });
      }
      // Department/Topic resource mobile accordions
      if (rtitle == true) {
        $('.sfgov-resources .paragraph--type--other-info-card').each(function(){
          if (!$(this).find('summary').length) {
            $(this).find('.__title').wrap('<summary class="details__summary"></summary>');
            $(this).find('.__resources').wrap('<div class="details__content"></div>');
            $(this).wrapInner('<details></details>');
          }
        });
      }
    }
    
    // Check accordion status on window resize, add/remove markup
    $(window).resize(function(){
      // disable mobile accordion styling
      if ($(window).width() >= mobileSize) {
        // Remove details accordion
        if (stitle == true) {
          $('.sfgov-services-section .sfgov-services h3').each(function(){
            if ($(this).parent().is('summary')) {
              $(this).unwrap();
            }
            if ($(this).parent().is('details')) {
              $(this).unwrap();
            }
          });
          
          $('.sfgov-services-section .sfgov-services').each(function(){
            if ($(this).parent().is('summary')) {
              $(this).unwrap();
            }
            if ($(this).find('.sfgov-dept-services-section-content').parent().hasClass('details__content')) {
              $(this).find('.sfgov-dept-services-section-content').unwrap();
            }
          });
        }
        // Remove resources accordion
        if (rtitle == true) {
          $('.sfgov-resources .paragraph--type--other-info-card .__title').each(function(){
            if ($(this).parent().is('summary')) {
              $(this).unwrap();
            }
            if ($(this).parent().is('details')) {
              $(this).unwrap();
            }
          });
          $('.sfgov-resources .paragraph--type--other-info-card').each(function(){
            console.log($(this));
            if ($(this).find('.__resources').parent().hasClass('details__content')) {
              $(this).find('.__resources').unwrap();
            }
          });
        }
 
        // Remove More Services accordion
        if (stitle == true) {
          $('.views-element-container .sfgov-services h5').each(function(){
            if ($(this).parent().is('summary')) {
              $(this).unwrap();
            }
            if ($(this).parent().is('details')) {
              $(this).unwrap();
            }
          });
          
          $('.views-element-container .sfgov-services').each(function(){
            if ($(this).parent().is('summary')) {
              $(this).unwrap();
            }
            if ($(this).find('.sfgov-container-three-column').parent().hasClass('details__content')) {
              $(this).find('.sfgov-container-three-column').unwrap();
            }
          });
        }
      }
      if ($(window).width() < mobileSize) {
        // Add accordions
        if (stitle == true) {
          $('.sfgov-services-section .sfgov-services').each(function(){
            if (!$(this).find('summary').length) {
              $(this).find('h3').wrap('<summary class="details__summary"></summary>');
              $(this).find('.sfgov-dept-services-section-content').wrap('<div class="details__content"></div>');
              $(this).wrapInner('<details></details>');
            }
          });
          // More services accordion
          $('.views-element-container .sfgov-services').each(function(){
            if (!$(this).find('summary').length) {
              $(this).find('h5').wrap('<summary class="details__summary"></summary>');
              $(this).find('.sfgov-container-three-column').wrap('<div class="details__content"></div>');
              $(this).wrapInner('<details></details>');
            }
          });
        }
        // Add resources accordion
        if (rtitle == true) {
          $('.sfgov-resources .paragraph--type--other-info-card').each(function(){
            if (!$(this).find('summary').length) {
              $(this).find('.__title').wrap('<summary class="details__summary"></summary>');
              $(this).find('.__resources').wrap('<div class="details__content"></div>');
              $(this).wrapInner('<details></details>');
            }
          });
        }
      }
    });
  });
});
