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

        $.instagramFeed({
          username: profile,
          container: feed_container,
          display_profile: false,
          display_biography: false,
          display_gallery: true,
          callback: null,
          styling: true,
          items: 12,
          items_per_row: 4,
          margin: 2,
          image_size: 150,
        });
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
