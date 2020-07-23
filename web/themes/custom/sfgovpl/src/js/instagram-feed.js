(function($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.instagramFeed = {
    attach: function(context, settings) {
      var items = settings.sfgov.instagram_feed;

      Object.keys(items).forEach(function(key) {
        var profile = items[key].instagram_profile;
        var paragraph_id = items[key].paragraph_id;
        var feed_container =
          '.paragraph--type--instagram-embed--' +
          paragraph_id +
          ' .__instagram-profile';
        var num_items = items[key].items;
        var items_per_row = items[key].items_per_row;
        var image_size = items[key].image_size;
        var styling = items[key].styling;
        $.instagramFeed({
          username: profile,
          container: feed_container,
          display_profile: false,
          display_biography: false,
          display_gallery: true,
          callback: null,
          styling: styling,
          items: num_items,
          items_per_row: items_per_row,
          margin: 2,
          image_size: image_size,
        });
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
