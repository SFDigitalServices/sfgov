(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.instagramFeed = {
    attach (context, settings) {
      const items = settings.sfgov.instagram_feed

      Object.keys(items).forEach(key => {
        const profile = items[key].instagram_profile
        const paragraph_id = items[key].paragraph_id
        const feed_container =
          '.paragraph--type--instagram-embed--' +
          paragraph_id +
          ' .__instagram-profile'
        const num_items = items[key].items
        const items_per_row = items[key].items_per_row
        const image_size = items[key].image_size
        const styling = items[key].styling
        $.instagramFeed({
          username: profile,
          container: feed_container,
          display_profile: false,
          display_biography: false,
          display_gallery: true,
          callback: null,
          styling,
          items: num_items,
          items_per_row,
          margin: 2,
          image_size
        })
      })
    }
  }
})(jQuery, Drupal, drupalSettings)
