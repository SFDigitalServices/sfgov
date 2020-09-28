function SFGovTranslate() {
  $ = jQuery;
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
        that.updateSelectedLang(b);
        deferred.resolve();
    }
    return deferred.promise();
  };

  this.addElementTranslationClass = function(translationVal) {
    var elementClass = 'sfgov-translate-lang-' + translationVal;
    $('body').find('*').not('script, noscript, link, style, iframe, .goog-te-combo').addClass(elementClass);
  }

  this.updateSelectedLang = function(translationVal) {
    // Set the current language as active.
    $('.gtranslate-link').removeClass('is-active');
    $('.gtranslate').find('a[data-sfgov-translate*="|'+ translationVal +'"]').addClass('is-active');
  }

  this.sfgovGTranslate = function(event) {
    var args = event.target.getAttribute('data-sfgov-translate');
    var lang = args.split('|')[1];
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
      if (drupalTranslation.turl != window.location.pathname) {
        $.when(that.sfgovDoGTranslate('en|en')).then(function() {
          window.location.href = drupalTranslation.turl;
        });
      }

    } else { // no drupal translation exists, use gtranslate
      that.sfgovDoGTranslate(args);
      that.currentSelectedTranslation = args;
    }
  };

  this.getDrupalTranslation = function(lang) {
    var drupalTranslations = drupalSettings.sfgov_translations.page.translations;
    if (drupalTranslations) {
      for(var i=0; i<drupalTranslations.length; i++) {
        var someTranslation = drupalTranslations[i];
        if (someTranslation.lang == lang) {
          return someTranslation;
        }
      }
    }
    return null;
  };

  this.checkCurrentLanguage = function() {
    var currentDrupalLanguage = drupalSettings.sfgov_translations.page.current_language;
    var gTranslateCookie = getCookie('googtrans');
    var gTranslateLang = gTranslateCookie ? gTranslateCookie.split('/')[2] : null;

    // If translation cookie lang is different from Drupal current language,
    // remove the cookie, as we are using language path prefixes and should
    // always show the same language as the URL.
    if(currentDrupalLanguage != 'en' || currentDrupalLanguage != gTranslateLang) {
      var drupalTranslation = that.getDrupalTranslation(currentDrupalLanguage);
      // Always translate page, even if a Drupal translation for the page exists.
      // If translated content is being shown on the page, it should be wrapped
      // in a container with class="notranslate" to allow other elements like
      // header and footer to be translated.
      if(drupalTranslation && drupalTranslation.status) {
        $('main[role="main"]').addClass('notranslate').attr('translate', 'no');
      }
      that.sfgovDoGTranslate('en|' + currentDrupalLanguage);
      that.addElementTranslationClass(currentDrupalLanguage);
      that.updateSelectedLang(currentDrupalLanguage);
      return;
    }

    if(gTranslateLang && gTranslateLang != 'en') { // gtranslate cookie exists, a page was gtranslated somewhere
      that.addElementTranslationClass(gTranslateLang);
      that.updateSelectedLang(gTranslateLang);
      var drupalTranslation = that.getDrupalTranslation(gTranslateLang);
      if (drupalTranslation && drupalTranslation.turl != window.location.pathname) { // drupal translation exists
        $.when(that.sfgovDoGTranslate('en|en')).then(function() { // kill the cookie and redirect to the drupal translation
          window.location.href = drupalTranslation.turl;
        });
      }
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
  var observeElement = $('.sfgov-top-container')[0];
  var config = { attributes: true, childList: true, subtree: true };
  var callback = function(mutationsList, observer) {
    var elem = null;
    for (var i = 0; i < mutationsList.length; i++) {
      var mutation = mutationsList[i];
      if (mutation.type == 'childList') {
        if (mutation.target.id == ':0.targetLanguage') {
          // catch the gtranslate dropdown
          elem = $('.gtranslate-link');
          break;
        }
      }
    }
      // this dropdown list that gets added doesn't always have the english option
      // if a language option doesn't exist in this drop down, the first option is always
      // selected by default, which is problematic
      // so, always add the english option
    if (elem) {
      // Attach click event.
      $(elem).on('click', t.sfgovGTranslate);

      setTimeout(function() {
        $('.goog-te-combo').append('<option value="en">English</option>');
        t.checkCurrentLanguage(); // check the current language of the page AFTER english has been added
      }, 1000);
    }
  }

  var observer = new MutationObserver(callback);
  observer.observe(observeElement, config);

})(jQuery);
