(function (Drupal, drupalSettings, $) {
  'use strict';
  Drupal.behaviors.simple_instagram_feed = {
    attach: function (context, settings) {
      // Avoid sending multiple requests to the Instagram.
      if (context !== document) {
        return;
      }
      var simple_instagram_feed_array = Object.keys(drupalSettings.simple_instagram_feed).map(function (e) {
        return drupalSettings.simple_instagram_feed[e]
      })

      for (var i = 0; i < simple_instagram_feed_array.length; i++) {
        var instagram_username = simple_instagram_feed_array[i].instagram_username;
        var display_profile = simple_instagram_feed_array[i].display_profile;
        var display_biography = simple_instagram_feed_array[i].display_biography;
        var items = simple_instagram_feed_array[i].items;
        var image_size = simple_instagram_feed_array[i].image_size;
        var styling = (simple_instagram_feed_array[i].styling === 'true' ? true : false);
        var unique_id = simple_instagram_feed_array[i].unique_id;
        var lazy_load = simple_instagram_feed_array[i].lazy_load;
        var captions = simple_instagram_feed_array[i].captions;
        // if captions are enabled, styling must be enabled by force.
        if (captions) {
           styling = true;
        }
        // Verify items per row.
        var items_per_row;
        if (simple_instagram_feed_array[i].items_per_row_type == 0) {
          items_per_row = simple_instagram_feed_array[i].items_per_row_default;
        } else {
          var screenWidth = $(window).width();
          if (screenWidth < 720) {
            items_per_row = simple_instagram_feed_array[i].items_per_row_l_720;
          } else if (screenWidth >= 720 && screenWidth < 960) {
            items_per_row = simple_instagram_feed_array[i].items_per_row_l_960;
          } else {
            items_per_row = simple_instagram_feed_array[i].items_per_row_h_960;
          }
        }

        var feed_settings = {
          host: 'https://images' + ~~(Math.random() * 3333) + '-focus-opensocial.googleusercontent.com/gadgets/proxy?container=none&url=https://www.instagram.com/',
          username: instagram_username,
          max_tries: 8,
          container: "#".concat(unique_id),
          display_profile: display_profile,
          display_biography: display_biography,
          display_captions: captions,
          display_gallery: true,
          callback: null,
          styling: styling,
          items: items,
          image_size: image_size,
          margin: 0.25,
          lazy_load: lazy_load,
        };

        if (styling) {
          feed_settings.items_per_row = items_per_row;
        }

        $.instagramFeed(feed_settings, context);

        $(window, context).resize(function () {
          screenWidth = $(window).width();
          var width;
          for (i = 0; i < simple_instagram_feed_array.length; i++) {
            if (simple_instagram_feed_array[i].items_per_row_type == 1) {
              if (screenWidth < 720) {
                width = (100 - 0.5 * 2 * simple_instagram_feed_array[i].items_per_row_l_720) / simple_instagram_feed_array[i].items_per_row_l_720;
              } else if (screenWidth >= 720 && screenWidth < 960) {
                width = (100 - 0.5 * 2 * simple_instagram_feed_array[i].items_per_row_l_960) / simple_instagram_feed_array[i].items_per_row_l_960;
              } else {
                width = (100 - 0.5 * 2 * simple_instagram_feed_array[i].items_per_row_h_960) / simple_instagram_feed_array[i].items_per_row_h_960;
              }
              var block_id = "block-" + simple_instagram_feed_array[i].block_instance;
              $("#" + block_id + " img", context).width(width + "%");
            }
          }
        });
      }
    }
  };
})(Drupal, drupalSettings, jQuery);
