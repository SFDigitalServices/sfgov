(function ($, settings) {

  function initEvents() {
    var events = drupalSettings.amplitude.events;

    if (!events) {
      return;
    }

    events.forEach(function (event) {
      switch (event['event_trigger']) {
        case 'pageLoad':
          triggerEvent(event);
          break;

        case 'scroll':
          triggerScrollEvent(event)

        case 'other':
          triggerEventOn(event, event['event_trigger_other']);
          break;

        default:
          triggerEventOn(event, event['event_trigger']);
          break;
      }
    });
  }

  // Triggers an Amplitude event.
  function triggerEvent(event, elem) {
    var amplitude_instance = amplitude.getInstance();
    var event_name = event['name'];
    var event_properties = event['properties'];

    if (!event_properties) {
      amplitude_instance.logEvent(event_name);
      triggerEventDebug(event_name);
      return;
    }

    var event_properties_parsed = JSON.parse(event_properties);

    if (elem) {
      var event_trigger_data_capture = event['event_trigger_data_capture'];
      var event_trigger_data_capture_properties = event['event_trigger_data_capture_properties'];
      if(event_trigger_data_capture == 1 && event_trigger_data_capture_properties) {
        var event_trigger_data_capture_properties_parsed = JSON.parse(event_trigger_data_capture_properties);
        for(var prop in event_trigger_data_capture_properties_parsed) {
          var selector = event_trigger_data_capture_properties_parsed[prop];
          event_properties_parsed[prop] = selector ? $(elem).find(selector).text() : $(elem).text();
        }
      }
    }

    amplitude_instance.logEvent(event_name, event_properties_parsed);
    triggerEventDebug(event_name, event_properties_parsed);

  }

  // Triggers an Amplitude event on a given user event.
  function triggerEventOn(event, trigger) {
    $(event['event_trigger_selector']).on(trigger, function () {
      triggerEvent(event, $(this));
    });
  }

  // Triggers scroll event
  function triggerScrollEvent(event, trigger) {
    if(!event['event_trigger_scroll_depths']) return;
    var scrollDepths = event['event_trigger_scroll_depths'].split(',').map(function(item) {
      return item.trim();
    });
    // some code below taken from http://javascriptkit.com/javatutors/detect-user-scroll-amount.shtml
    var winheight, docheight, trackLength, throttlescroll
    var getDocHeight = function() {
      var D = document;
      return Math.max(
        D.body.scrollHeight, D.documentElement.scrollHeight,
        D.body.offsetHeight, D.documentElement.offsetHeight,
        D.body.clientHeight, D.documentElement.clientHeight
      );
    };
    var getMeasurements = function() {
      winheight= window.innerHeight || (document.documentElement || document.body).clientHeight;
      docheight = getDocHeight();
      trackLength = docheight - winheight;
    };
    var amountscrolled = function() {
      var scrollTop = window.pageYOffset || (document.documentElement || document.body.parentNode || document.body).scrollTop;
      var pctScrolled = Math.floor(scrollTop/trackLength * 100);
      for(var i = scrollDepths.length-1; i >= 0; i--) {
        if(pctScrolled >= scrollDepths[i]) {
          var eventProps = JSON.parse(event['properties']);
          eventProps['scroll_depth'] = scrollDepths[i];
          event['properties'] = JSON.stringify(eventProps);
          triggerEvent(event);
          scrollDepths.splice(i, 1);
        }
      }
    };
    getMeasurements();
    $(window).on('resize', getMeasurements);
    setTimeout(function() {
      $(window).on('scroll', function() {
        clearTimeout(throttlescroll);
        throttlescroll = setTimeout(function() {
          amountscrolled()
        }, 50);
      });
    }, 500);
  }

  // Logs event debug.
  function triggerEventDebug(event_name, event_properties_parsed){

    if (!settings.amplitude.debug) {
      return;
    }

    console.log('Triggered event ' + event_name);

    if (event_properties_parsed) {
      console.log('With properties:');
      console.log(event_properties_parsed);
    }
  }
  initEvents();

})(jQuery, drupalSettings);
