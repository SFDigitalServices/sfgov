(function ($) {
  $().ready(() => {
    /*
         * Iterate though navItems and check if it is in the href.
         * If yes, then add is-active class to corresponding menu link.
         */
    const href = document.location.href.toLowerCase();
    const navItems = ['services', 'departments'];

    for (let i = 0, l = navItems.length; i < l; i++) {
      const nav = navItems[i];
      if (new RegExp('/' + nav).test(href)) {
        $('.sfgov-main-navigation ul.menu a[data-drupal-link-system-path=' + nav + ']').addClass('is-active');
        break;
      }
    }

    $('button.sfgov-mobile-search').click(() => {
      $('header .sfgov-search-311-block').show();
      $('body').removeClass('sfgov-mobile_nav-active');
      $('.sf-gov-search-input-class').focus();
      $('button.sfgov-menu-btn').removeClass('is-active');
    });

    $('header .sfgov-search-311-block .sfgov-mobile-btn-close').click(() => {
      $('header .sfgov-search-311-block').hide();
    });

    $('button.sfgov-menu-btn').click(function () {
      $('header .sfgov-search-311-block').hide();
      $(this).toggleClass('is-active');
      $('body').toggleClass('sfgov-mobile_nav-active');
    });

    $('button.sfgov-mobile-translate').click(() => {
      $('#block-gtranslate').show();
    });

    // Search Clear Input functionality.
    const searchClearInput = function () {
      const input = $('[data-drupal-selector="edit-sfgov-search-input"]');
      const inputWrapper = $('.js-form-item-keys');
      const inputClearButton = '<span class="input-clear"></span>';

      console.log(input);
      console.log(inputWrapper);
      console.log(inputClearButton);
      inputWrapper.prepend(inputClearButton);

      input.keyup(function () {
        console.log('up');
        $(this).parent('.js-form-item-keys').toggleClass('is_typing', !!$(this).val());
      });

      $('.input-clear').click(() => {
        input.val('');
        inputWrapper.removeClass('is_typing');
      });
    };

    searchClearInput();
  });
})(jQuery);