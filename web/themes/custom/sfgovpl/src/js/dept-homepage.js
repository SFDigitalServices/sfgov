(function($) {
  $('body.page-node-type-department').each(function() {
    // an array of selectors for the dept homepage sections
    var sections = [
      {"selector": "#sfgov-dept-services", "label":"Services"},
      {"selector": "#sfgov-dept-news", "label":"News"},
      {"selector": "#sfgov-dept-events", "label":"Events"},
      {"selector": "#sfgov-dept-resources", "label":"Resources"},
      {"selector": "#sfgov-dept-about", "label":"About"},
      {"selector": "#sfgov-dept-contact", "label":"Contact"},
    ];
    var $inPageMenuContainer = $('#sfgov-dept-in-this-page');
    var $inPageMenuList = $('#sfgov-dept-in-this-page ul');
    var $scrollElem = $('html, body');
    var scrollTo = function(elemSelector) {
      $scrollElem.animate({
        scrollTop: $(elemSelector).offset().top
      }, 300);
      return false;
    }

    // create the elements
    for(var i=0; i<sections.length; i++) {
      var elem = $(sections[i].selector);
      // console.log(sections[i]);
      if(elem.length > 0) {
        $inPageMenuContainer.show();
        var li = document.createElement('li');
        var a = document.createElement('a');
        $(a).attr('href', '#'+sections[i].label.toLowerCase()).attr('data-section', sections[i].selector).text(sections[i].label);
        $(li).append(a);
        $inPageMenuList.append(li);
      }
    }

    var $links = $inPageMenuList.find('a');
    $links.click(function() {
      var scrollToSelector = 'a[name="' + $(this).attr('href').replace('#', '') + '"]';
      scrollTo(scrollToSelector);
    });

    $(document).ready(function() {
      if(window.location.hash) {
        var selector = 'a[name="' + window.location.hash.replace('#','') + '"]';
        scrollTo(selector);
      }
      checkWindowSize();
    });
  });
})(jQuery);
