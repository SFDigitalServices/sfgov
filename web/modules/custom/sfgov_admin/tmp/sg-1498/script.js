(function ($, Drupal) {

  'use strict';

  $(document).ready(function() {

    $('details .item-list ul').each(function() {
      let element = $(this);
      let parent = element.parent();

      element.append('<div class="load-more-wrapper"><div class="loadMore">' + Drupal.t('Load more') + '</div><div class="showLess">' + Drupal.t('Show less') + '</div></div>');

      let size_li = $('li', element).length
      let x = 2;
      $('li:lt(' + x + ')', element).show();
      $('.loadMore', parent).click(function () {
        x = ( x + 5 <= size_li) ? x + 5 : size_li;
        element.children('li:lt(' + x + ')').show();
      });
      $('.showLess', parent).click(function () {
        x = (x - 5 < 0) ? 2 : x - 5;
        $('li', element).not(':lt(' + x + ')').hide();
      });

    });
  });

})(jQuery, Drupal);
