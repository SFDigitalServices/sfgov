(function($) {

  // watch for dom mutations
  var observeElement = $('.head-right--container')[0];
  var config = { attributes: true, childList: true, subtree: true };
  var callback = function(mutationsList, observer) {
    var elem = null;
    for(var mutation of mutationsList) {
      if (mutation.type == 'childList') {
          if(mutation.target.id == ':0.targetLanguage') {
            elem = $('#block-gtranslate .gtranslate > select'); // catch the gtranslate dropdown
            break;
          }
      }
    }
    if(elem) { // muck around with the gtranslate dropdown
      $(elem)[0].setAttribute('onchange', ''); // take over onchange
      $(elem)[0].setAttribute('data-gtranslate', 'sfgov');
      setTimeout(function() { 
        // this dropdown list that gets added doesn't always have the english option
        // if a language option doesn't exist in this drop down, the first option is always
        // selected by default, which is problematic
        // so, always add the english option
        $('.goog-te-combo').attr('data-gtranslate', 'sfgov');
        $('.goog-te-combo').append('<option value="en">English</option>');
      }, 300)
      $(elem).change(function() { // attach our own change event
        checkLanguage($(this).val());
      });
    }
  }

  // check the current page's translations
  function checkLanguage(selectedLanguage) {
    var selectedLanguageArr = selectedLanguage.split('|');
    var currentDrupalLanguage = drupalSettings.sfgov_translations.node.current_language;
    if(selectedLanguageArr.length > 0) {
      var theSelectedLanguage = selectedLanguageArr[1];
      var drupalTranslation = getDrupalTranslation(theSelectedLanguage);
      if(drupalTranslation && !(drupalTranslation.lang == 'en' && currentDrupalLanguage == 'en')) {
        if(drupalTranslation.lang != currentDrupalLanguage) {
          $('body').hide();
          sfgovGtranslate('en|en'); // kill the gtranslate cookie
          setTimeout(function() {
            window.location.href = drupalTranslation.turl;
          },530);
        }
      } else {
        // go to english url, then set gtranslate cookie
        var enUrl = window.location.href.replace('/' + currentDrupalLanguage, '');
        $('body').hide();
        sfgovGtranslate(selectedLanguage);
        setTimeout(function() {
          window.location.href = enUrl;
        },530)
      }
    }
  }

  function checkCurrentLanguage() {
    // always prefer the language set by drupal
    var currentDrupalLanguage = drupalSettings.sfgov_translations.node.current_language;
    var gTranslateCookie = getCookie('googtrans');
    var drupalTranslation = getDrupalTranslation(currentDrupalLanguage);
    if(currentDrupalLanguage != 'en') { // current drupal language is not english
      if(!drupalTranslation) {
        var gTranslateLang = gTranslateCookie ? gTranslateCookie.split('/')[2] : '';
        if(currentDrupalLanguage != gTranslateLang) {
          sfgovGtranslate('en|' + currentDrupalLanguage);
        }
      } else {
        sfgovGtranslate('en|en'); // kill the gTranslateCookie
      }
      $('body').addClass('sfgov-translate-' + currentDrupalLanguage);
    } else {
      // current drupal language is english, check for gtranslate cookie
      if(gTranslateCookie) {
        var gTranslateLang = gTranslateCookie.split('/')[2];
        var drupalTranslation = getDrupalTranslation(gTranslateLang);
        if(gTranslateLang != 'en') {
          $('body').addClass('sfgov-translate-' + gTranslateLang);
          if(drupalTranslation) {
            $('body').hide();
            setTimeout(function() {
              sfgovGtranslate('en|en');
              window.location.href = drupalTranslation.turl;
            }, 350)
          }
        }
      }
    }
  }

  function sfgovGtranslate(languageValue) {
    setTimeout(function() {
      doGTranslate(languageValue);
    },200);
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

  function getDrupalTranslation(lang) {
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
  }

  $(document).ready(function() {
    checkCurrentLanguage();
  });

  var observer = new MutationObserver(callback);
  observer.observe(observeElement, config);

})(jQuery);