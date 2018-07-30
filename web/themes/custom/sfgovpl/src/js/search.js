(function ($) {
  Drupal.behaviors.searchJS = {
    attach: function (context) {

      var block_search = $('.block-views-exposed-filter-blocksearch-page-1 .form-text', context);
      if ($('body.path-frontpage').length) {
        $(block_search).attr({'placeholder': 'What are you looking for?'});
      } else {
        $(block_search).attr({'placeholder': 'Search'});
      }

      $('.sfgov-responsive--search-form .sfgov-search-form', context).clone().appendTo('.responsive-search--container').addClass('cloned-search');

      // Search Toggle.

      $('header .block-views-exposed-filter-blocksearch-page-1 .form-submit').on('click', function(e) {
        if($(window).width() < 770) {
          e.preventDefault();
          $('.responsive-search--container').toggle();
        }
      });

      $('.responsive-search--container .close', context).on('click', function() {
        $('.responsive-search--container').toggle();
      });

      $(window).on('resize', function() {
        $('.responsive-search--container').hide();
      });

      // Search Toggle.
    }
  }
})(jQuery);
