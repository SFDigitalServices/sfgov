jQuery(function () {
  $(document).ready(function() {
    // Check for more services section
    if ($('.views-element-container .sfgov-dept-services-section-title').text() == 'More services') {
      var moreServices = true;
    } else {
      var moreServices = false;
    }
    // Track if accordions have been activated
    var accordionActive = false;
    const mobileSize = 701;
    // activate accordions
    if ($(window).width() < mobileSize) {
      $('.sfgov-services-section .sfgov-services').each(function(){
        $(this).addClass('accordion');
        $(this).accordion({
          active: false,
          collapsible: true,
          header: 'h5',
        });
      });
      // more services section
      $('.views-element-container .sfgov-services').each(function(){
        $(this).addClass('accordion');
        $(this).accordion({
          active: false,
          collapsible: true,
          header: 'h5',
        });
      });
      accordionActive = true;
    }
    
    $(window).resize(function(){
      // disable mobile accordion styling
      if ($(window).width() >= mobileSize) {
        $('div.sfgov-services .sfgov-services').each(function(){
          $(this).removeClass('accordion ui-accordion ui-widget ui-helper-reset');
          $(this).find('h5').removeClass('ui-accordion-header ui-corner-top ui-accordion-header-collapsed ui-corner-all ui-state-default ui-accordion-icons ui-state-active');
          $(this).find('.ui-icon').css('display', 'none');
          $(this).find('.sfgov-dept-services-section-title').removeClass('ui-accordion-icons');
          $(this).find('.sfgov-dept-services-section-content').removeClass('ui-accordion-content ui-corner-bottom ui-helper-reset ui-widget-content');
          $(this).find('.sfgov-dept-services-section-content').css({'display': 'flex', 'height': 'auto'});
        });
        $('.views-element-container .sfgov-services').each(function(){
          $(this).removeClass('accordion ui-accordion ui-widget ui-helper-reset');
          $(this).find('h5').removeClass('ui-accordion-header ui-corner-top ui-accordion-header-collapsed ui-corner-all ui-state-default ui-accordion-icons ui-state-active');
          $(this).find('.ui-icon').css('display', 'none');
          $(this).find('.sfgov-dept-services-section-title').removeClass('ui-accordion-icons');
          $(this).find('.sfgov-dept-services-section-content').removeClass('ui-accordion-content ui-corner-bottom ui-helper-reset ui-widget-content');
          $(this).find('.sfgov-container-three-column').css({'display': 'flex', 'height': 'auto'});
        });
      }
      if ($(window).width() < mobileSize) {
        // activate accordions for mobile if not already
        if (!accordionActive) {
          $('.sfgov-services-section .sfgov-services').each(function(){
            $(this).addClass('accordion');
            $(this).accordion({
              active: false,
              collapsible: true,
              header: 'h5',
            });
          });
            $('.views-element-container .sfgov-services').each(function(){
            $(this).addClass('accordion');
            $(this).accordion({
              active: false,
              collapsible: true,
              header: 'h5',
            });
          });
          accordionActive = true;
        }
        if (accordionActive) {
          // reinstate accordion styling for mobile
          $('div.sfgov-services .sfgov-services').each(function(){
            if (!$(this).hasClass('accordion')) {
              $(this).addClass('accordion ui-accordion ui-widget ui-helper-reset');
              $(this).find('h5').addClass('ui-accordion-header ui-corner-top ui-accordion-header-collapsed ui-corner-all ui-state-default ui-accordion-icons');
              $(this).find('.ui-icon').css('display', 'block');
              $(this).find('span.ui-icon').css('display', 'inline-block');
              $(this).find('.sfgov-dept-services-section-title').addClass('ui-accordion-icons');
              $(this).find('.sfgov-dept-services-section-content').addClass('ui-accordion-content ui-corner-bottom ui-helper-reset ui-widget-content');
              $(this).find('.sfgov-dept-services-section-content').css({'display': 'none', 'height': 'auto'});
              $(this).find('.sfgov-service-cards').addClass('ui-accordion-content ui-corner-bottom ui-helper-reset ui-widget-content');
            }
          });
          $('.views-element-container .sfgov-services').each(function(){
            if (!$(this).hasClass('accordion')) {
              $(this).addClass('accordion ui-accordion ui-widget ui-helper-reset');
              $(this).find('h5').addClass('ui-accordion-header ui-corner-top ui-accordion-header-collapsed ui-corner-all ui-state-default ui-accordion-icons');
              $(this).find('.ui-icon').css('display', 'block');
              $(this).find('span.ui-icon').css('display', 'inline-block');
              $(this).find('.sfgov-dept-services-section-title').addClass('ui-accordion-icons');
              $(this).find('.sfgov-dept-services-section-content').addClass('ui-accordion-content ui-corner-bottom ui-helper-reset ui-widget-content');
              $(this).find('.ui-accordion-content').css({'display': 'none', 'height': 'auto'});
              $(this).find('.sfgov-service-cards').addClass('ui-accordion-content ui-corner-bottom ui-helper-reset ui-widget-content');
            }
          });
        }
      }
    });
  });
});
