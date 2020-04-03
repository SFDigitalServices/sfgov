function SFGovTranslate() {
  this.currentSelectedTranslation = null;
  this.sfgovGTranslateFireEvent = function (a, b) {
    try {
        if (document.createEvent) {
            var c = document.createEvent("HTMLEvents");
            c.initEvent(b, true, true);
            a.dispatchEvent(c)
        } else {
            var c = document.createEventObject();
            a.fireEvent('on' + b, c)
        }
    } catch (e) {}
  };

  this.sfgovDoGTranslate = function(a) {
    $('body').hide();
    var deferred = jQuery.Deferred();
    if (a == '')
        return;
    var b = a.split('|')[1];
    var c;

    var d = document.getElementsByTagName('select');
    for (var i = 0; i < d.length; i++)
        if (d[i].className == 'goog-te-combo')
            c = d[i];
    if (document.getElementById('google_translate_element2') == null || document.getElementById('google_translate_element2').innerHTML.length == 0 || c.length == 0 || c.innerHTML.length == 0) {
        setTimeout(function() {
          that.sfgovDoGTranslate(a)
        }, 500)
    } else {
        c.value = b;
        that.sfgovGTranslateFireEvent(c, 'change');
        that.sfgovGTranslateFireEvent(c, 'change');
        $('body').show();
        that.addElementTranslationClass(b);
        deferred.resolve();
    }
    return deferred.promise();
  };

  this.addElementTranslationClass = function(translationVal) {
    var elementClass = 'sfgov-translate-lang-' + translationVal;
    $('body').find('*').not('script, noscript, link, style, iframe, .goog-te-combo').addClass(elementClass);
  }

  this.sfgovGTranslate = function(event) {
    var lang = event.target.value.split('|')[1];
    if(!lang) {
      $(event.target).val(that.currentSelectedTranslation);
      return;
    }
    var drupalTranslation = that.getDrupalTranslation(lang);
    $('body').find('*').not('script, noscript, link, style, iframe, .goog-te-combo').removeClass(function(i, classNames) {
      var classes = classNames.split(' ');
      var classesToRemove = [];
      for(var i = 0; i<classes.length; i++) {
        if(classes[i].indexOf('sfgov-translate-lang-') >= 0) {
          classesToRemove.push(classes[i]);
        }
      }
      return classesToRemove.join(' ');
    });
    if(drupalTranslation) {
      // drupal translation always wins
      // set gtranslate to english to kill the gtranslate cookie
      $.when(that.sfgovDoGTranslate('en|en')).then(function() {
        window.location.href = drupalTranslation.turl;
      });
    } else { // no drupal translation exists, use gtranslate
      that.sfgovDoGTranslate(event.target.value);
      that.currentSelectedTranslation = event.target.value;
    }
  };

  this.getDrupalTranslation = function(lang) {
    var drupalTranslations = drupalSettings.sfgov_translations.node.translations;
    if(drupalTranslations) {
      for(var i=0; i<drupalTranslations.length; i++) {
        var someTranslation = drupalTranslations[i];
        if(someTranslation.lang == lang) {
          return someTranslation;
        }
      }
    }
    return null;
  };

  this.checkCurrentLanguage = function() {
    var currentDrupalLanguage = drupalSettings.sfgov_translations.node.current_language;
    var gTranslateCookie = getCookie('googtrans');
    var gTranslateLang = gTranslateCookie ? gTranslateCookie.split('/')[2] : null;
    if(gTranslateLang && gTranslateLang != 'en') { // gtranslate cookie exists, a page was gtranslated somewhere
      that.addElementTranslationClass(gTranslateLang);
      var drupalTranslation = that.getDrupalTranslation(gTranslateLang);
      if(drupalTranslation) { // drupal translation exists
        $.when(that.sfgovDoGTranslate('en|en')).then(function() { // kill the cookie and redirect to the drupal translation
          if(drupalTranslation.turl != window.location.pathname) {
            window.location.href = drupalTranslation.turl;
          }
        });
      }
    }
    if(currentDrupalLanguage != 'en') {
      // var drupalTranslation = that.getDrupalTranslation(currentDrupalLanguage);
      // Always translate page, even if a Drupal translation for the page exists.
      // If translated content is being shown on the page, it should be wrapped
      // in a container with class="notranslate".
      that.sfgovDoGTranslate('en|' + currentDrupalLanguage);
      that.addElementTranslationClass(currentDrupalLanguage);
    }
  }

  var that = this;
}

function getCookie(cookieName) {
  var cookiesArr = [];
  var cookies = {};
  if(document.cookie) {
    cookiesArr = document.cookie.split(';');
    for(var i=0; i<cookiesArr.length; i++) {
      var keyValPair = cookiesArr[i].split('=');
      cookies[keyValPair[0].replace(/\s/g, '')] = keyValPair[1];
    }
  }
  return cookies[cookieName];
}

(function($) {
  // watch for dom mutations
  var t = new SFGovTranslate();
  var observeElement = $('.head-right--container')[0];
  var config = { attributes: true, childList: true, subtree: true };
  var callback = function(mutationsList, observer) {
    var elem = null;
    for(var i = 0; i < mutationsList.length; i++) {
      var mutation = mutationsList[i];
      if (mutation.type == 'childList') {
          if(mutation.target.id == ':0.targetLanguage') {
            elem = $('#block-gtranslate .gtranslate > select'); // catch the gtranslate dropdown
            break;
          }
      }
    }
    if(elem) { // muck around with the gtranslate dropdown
      $(elem)[0].setAttribute('onchange', ''); // take over onchange
      $(elem)[0].onchange = t.sfgovGTranslate;
      $(elem)[0].setAttribute('data-gtranslate', 'sfgov');
      // this dropdown list that gets added doesn't always have the english option
      // if a language option doesn't exist in this drop down, the first option is always
      // selected by default, which is problematic
      // so, always add the english option
      setTimeout(function() {
        $('.goog-te-combo').attr('data-gtranslate', 'sfgov');
        $('.goog-te-combo').append('<option value="en">English</option>');
        t.checkCurrentLanguage(); // check the current language of the page AFTER english has been added
      }, 500);
      // add aria attributes
      $(elem)[0].setAttribute('id', 'sfgov-gtranslate-select');
      $(elem)[0].setAttribute('aria-label', 'Language Translate Widget');
    }
  }

  var observer = new MutationObserver(callback);
  observer.observe(observeElement, config);

})(jQuery);
