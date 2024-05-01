(function ($) {
  $('body.page-node-type-department').each(() => {
    // Set animation speed based on motion preference.
    const animationSpeed = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 300;

    // an array of selectors for the dept homepage sections
    const sections = [{ selector: '#sfgov-dept-services', label: 'Services' }, { selector: '#sfgov-dept-news', label: 'News' }, { selector: '#sfgov-dept-events', label: 'Events' }, { selector: '#sfgov-dept-resources', label: 'Resources' }, { selector: '#sfgov-dept-about', label: 'About' }, { selector: '#sfgov-dept-contact', label: 'Contact' }];
    const $inPageMenuContainer = $('#sfgov-dept-in-this-page');
    const $inPageMenuList = $('#sfgov-dept-in-this-page ul');
    const $scrollElem = $('html, body');
    const scrollTo = function (elemSelector) {
      $scrollElem.animate({
        scrollTop: $(elemSelector).offset().top
      }, animationSpeed);
      return false;
    };

    // create the elements
    for (let i = 0; i < sections.length; i++) {
      const elem = $(sections[i].selector);
      // console.log(sections[i]);
      if (elem.length > 0) {
        $inPageMenuContainer.show();
        const li = document.createElement('li');
        const a = document.createElement('a');
        $(a).attr('href', '#' + sections[i].label.toLowerCase()).attr('class', 'in-page-link').attr('data-section', sections[i].selector).text(sections[i].label);
        $(li).append(a);
        $inPageMenuList.append(li);
      }
    }

    const $links = $inPageMenuList.find('a');
    $links.click(function () {
      const scrollToSelector = 'a[name="' + $(this).attr('href').replace('#', '') + '"]';
      scrollTo(scrollToSelector);
    });

    $(document).ready(() => {
      if (window.location.hash) {
        const selector = 'a[name="' + window.location.hash.replace('#', '') + '"]';
        scrollTo(selector);
      }
    });
  });
})(jQuery);