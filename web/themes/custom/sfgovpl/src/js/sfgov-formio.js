"use strict";
(function($) {
    function customAddCssClassById(classes) {
      for (var key in classes) {
        var el = document.getElementById(key)
        el.classList.add(classes[key])
      }
    }

    function customHideElements(elements) {
      elements.forEach(function(klass, index) {
          var hide = document.getElementsByClassName(klass)
          for (var i = 0; i < hide.length; i++){
            hide[i].style.display = "none"
          }
        })
    }
    var setCookie = function(name, value){
      var today = new Date();
      var expiry = new Date(today.getTime() + 30 * 24 * 3600 * 1000); // plus 30 days
      document.cookie=name + "=" + escape(value) + "; path=/; expires=" + expiry.toGMTString();
    }
    var getCookie = function(name){
      var re = new RegExp(name + "=([^;]+)");
      var value = re.exec(document.cookie);
      return (value != null) ? unescape(value[1]) : null;
    }

    var setCheckboxRadioButton = function(field, cookieval, fieldtype) {
      if(!cookieval) return;

      if(cookieval.includes(field.value) ){
        field.prop('checked', true)
      }
    }

    var manageFormCookies = function(cookies, form, populateAll) {
      cookies.forEach(function(item, index){
        var field = $('input[name*="'+item+'"]') // use jquery to get dynamic field by name
        var fieldtype = field.prop('type')

        // special type: select, because formio renders select as divs
        var selectField = $('select[name*="'+item+'"]')[0]
        if(selectField){
          field = $(selectField) // convert to jQuery object
        }
        if(field || populateAll){
          var cookieval = getCookie(item)
          if(cookieval)
            form._submission.data[item] = cookieval //set submission data, form validation needs this

          if(field){ // populate fields with cookie values
              if(fieldtype !== undefined && (fieldtype === 'radio' || fieldtype === 'checkbox')){
                setCheckboxRadioButton(field, cookieval, fieldtype)
              }else{
                field.val(cookieval)
              }
          }else if(field){
            // set cookie for first time
            if(fieldtype !== undefined && (fieldtype === 'radio' || fieldtype === 'checkbox')){
              setCookie(item, field.prop('checked'))
            }
            else{
              setCookie(item, field.val())
            }
          } // updates cookie if value changed
          $(field).change( function(){
            console.log("setting cookie for " + item + " with value " + this.value)
            setCookie(item, this.value)
          })
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
        if (options.sfoptions && options.sfoptions.hide instanceof Object) {
          customHideElements(options.sfoptions.hide)
        }
        // add css class to element(by id)
        if (options.sfoptions && options.sfoptions.addClass) {
          customAddCssClassById(options.sfoptions.addClass)
        }
        // if cookies are defined, populate the form field with cookie value.
        if(options.sfoptions["cookies"]){
          manageFormCookies(options.sfoptions["cookies"], form, true)
        }
        // perform custom action on nextPage event
        form.on('nextPage', function(){
          // set form cookies, if there are any
          if(options.sfoptions["cookies"]){
            manageFormCookies(options.sfoptions["cookies"], form, false)
          }
        })
        // What to do when the submit begins.
        form.on('submitDone', function(submission) {
            // custom options defined in Form.io render options field
            if(options.redirects instanceof Object){
              if (options.sfoptions && options.sfoptions.hide instanceof Object) {
                customHideElements(options.sfoptions.hide)
              }
              console.log(submission.data)
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
          if (window.formioConfirmationURL)
            window.location = confirmation_url
        });
      });
    });
})(jQuery);
