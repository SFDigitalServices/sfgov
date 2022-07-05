(function ($) {
  // Component container.
  const inPageMenuContainer = $('#sfgov-in-this-page')
  const animateDuration = 300

  // Check if container exists.
  if (inPageMenuContainer.length) {
    const inPageItems = []
    // For each heading.
    $('h2[role=heading]').each(function () {
      // Add heading to items array.
      inPageItems[$(this).attr('id')] = $(this).html()
    })

    const inPageItemsObject = Object.keys(inPageItems)
    // Proceed only if there is available items to display.
    if (inPageItemsObject.length) {
      // For each heading item.
      inPageItemsObject.forEach(key => {
        // Insert item into the container.
        $('ul', inPageMenuContainer).append(
          "<li><a href='#" + key + "'>" + inPageItems[key] + '</a></li>'
        )
      })

      inPageMenuContainer.show()

      // Scroll to heading when clicked.
      $('a', inPageMenuContainer).click(function (e) {
        scrollTo($(this).attr('href'))
      })

      if (window.location.hash) {
        scrollTo(window.location.hash)
      }
    }
  }

  function scrollTo (selector) {
    const $el = $(selector)

    // Get padding offset of the wrapper container.
    const elemSelectorPaddingTop = parseInt(
      $el
        .closest('.sfgov-in-this-page-target')
        .css('padding-top')
    )

    $('html, body').animate(
      {
        scrollTop: $el.offset().top - elemSelectorPaddingTop
      },
      animateDuration
    )
    return false
  }
})(jQuery)
