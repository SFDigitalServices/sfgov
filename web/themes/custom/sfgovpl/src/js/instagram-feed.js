(function($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.instagramFeed = {
    attach: function(context, settings) {
      var profile = settings.sfgov.instagram_feed.instagram_profile;
      var paragraph_id = settings.sfgov.instagram_feed.paragraph_id;

      $.instagramFeed({
        username: profile,
        container: '.paragraph--type--instagram-embed--' + paragraph_id,
        display_profile: false,
        display_biography: false,
        display_gallery: true,
        callback: null,
        styling: false,
        items: 8,
        items_per_row: 4,
        margin: 1,
        image_size: 150,
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
