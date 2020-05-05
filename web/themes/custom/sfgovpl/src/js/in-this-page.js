(function($) {
  // Component container.
  var inPageMenuContainer = $('#sfgov-in-this-page');
  // Check if container exists.
  if (inPageMenuContainer.length) {
    var inPageItems = [];
    // For each heading.
    $('h2[role=heading]').each(function() {
      // Add heading to items array.
      inPageItems[$(this).attr('id')] = $(this).html();
    });

    var inPageItemsObject = Object.keys(inPageItems);
    // Proceed only if there is available items to display.
    if (inPageItemsObject.length) {
      // For each heading item.
      inPageItemsObject.forEach(function(key) {
        // Insert item into the container.
        $('ul', inPageMenuContainer).append(
          "<li><a href='#" + key + "'>" + inPageItems[key] + '</a></li>'
        );
      });

      inPageMenuContainer.show();

      // Scroll to heading when clicked.
      $('a', inPageMenuContainer).click(function(e) {
        scrollTo($(this).attr('href'));
      });

      var scrollTo = function(elemSelector) {
        console.log(elemSelector);
        // Get padding offset of the wrapper container.
        var elemSelectorPaddingTop = parseInt(
          $(elemSelector)
            .closest('.sfgov-in-this-page-target')
            .css('padding-top')
        );
        console.log(elemSelectorPaddingTop);

        $('html, body').animate(
          {
            scrollTop: $(elemSelector).offset().top - elemSelectorPaddingTop,
          },
          300
        );
        return false;
      };

      if (window.location.hash) {
        scrollTo(window.location.hash);
      }
    }
  }
})(jQuery);
