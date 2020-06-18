"use strict";
(function($) {
    var customAddCssClassById = function(classes){
      for (var key in classes) {
        var el = $('#'+key)
        var value = classes[key]
        el.addClass(value);
      }
    }

    var customHideElements = function(options){
      options.sfoptions["hide"].forEach(function(item, index){
          var hide = $('.'+item)
          for (var i=0; i<hide.length;i++){
            hide[i].style.display = "none";
          }
        })
    }

    var safeJSONParse = function(str) {
      var parsed = {}
      try {
        parsed = JSON.parse(str)
      } catch (error) {
        console.warn('Unable to parse form options:', str)
      }
      return parsed
    }

    $('document').ready(function(){
      var el = document.getElementById('formio') // keeping this as a DOM for patch.js
      var options = safeJSONParse(el.getAttribute('data-options'))
      Formio.createForm(el, el.getAttribute('data-source'), options)
        .then(function(form) {
        //perform sfoptions, hide certain elements
        if(options.sfoptions && options.sfoptions["hide"]){
          customHideElements(options)
        }
        // add css class to element(by id)
        if(options.sfoptions && options.sfoptions["addClass"]){
          customAddCssClassById(options.sfoptions["addClass"])
        }
        // What to do when the submit begins.
        form.on('submitDone', function(submission) {
            // custom options defined in Form.io render options field
            if(options.redirects instanceof Object){
              customHideElements(options)
              for (var key in options.redirects) {
                var value = options.redirects[key]
                // only one "redirect" should be "yes", this is set in the form.io form
                if(submission.data[key] === "yes"){
                  window.location = value
                  break
                }
              }
            }
          // if confirmation url is set, this is for backward compatibility
          if(confirmation_url)
            window.location = confirmation_url
        });
      });
    });
})(jQuery);
