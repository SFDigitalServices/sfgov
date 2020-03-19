jQuery(function () {
  $(document).ready(function() {
    // Track if accordions have been activated
    var accordionActive = false;
    const mobileSize = 701;
    
    // Check for mobile, initialize accordions
    if ($(window).width() < mobileSize) {
      // Add accordions
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
    
    // Check accordion status on window resize, add/remove markup
    $(window).resize(function(){
      // disable mobile accordion styling
      if ($(window).width() >= mobileSize) {
        // Remove details accordion
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
        // Remove More Services accordion
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
      if ($(window).width() < mobileSize) {
        // Add accordions
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
    });
  });
});
