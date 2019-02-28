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
      $(elem)[0].setAttribute('data-gtranslate', '');
      setTimeout(function() { 
        // this dropdown list that gets added doesn't always have the english option
        // if a language option doesn't exist in this drop down, the first option is always
        // selected by default, which is problematic
        // so, always add the english option
        $('.goog-te-combo').append('<option value="en">English</option>');
      },100)
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
          },200);
        }
      } else {
        // go to english url, then set gtranslate cookie
        var enUrl = window.location.href.replace('/' + currentDrupalLanguage, '');
        $('body').hide();
        sfgovGtranslate(selectedLanguage);
        setTimeout(function() {
          window.location.href = enUrl;
        },200)
      }
      // displayTranslationNotice();
    }
    
  }

  function checkCurrentLanguage() {
    // always prefer the language set by drupal
    var currentDrupalLanguage = drupalSettings.sfgov_translations.node.current_language;
    var gTranslateCookie = getCookie('googtrans');
    var drupalTranslation = getDrupalTranslation(currentDrupalLanguage);
    var pathLanguage = window.location.pathname.split('/')[1];
    if(currentDrupalLanguage != 'en') { // current drupal language is not english
      if(!drupalTranslation) {
        var gTranslateLang = gTranslateCookie ? gTranslateCookie.split('/')[2] : '';
        if(currentDrupalLanguage != gTranslateLang) {
          sfgovGtranslate('en|' + currentDrupalLanguage);
        }
      } else {
        sfgovGtranslate('en|en'); // kill the gTranslateCookie
      }
    } else {
      // current drupal language is english, check for gtranslate cookie
      if(gTranslateCookie) {
        var gTranslateLang = gTranslateCookie.split('/')[2];
        var drupalTranslation = getDrupalTranslation(gTranslateLang);
        if(gTranslateLang != 'en') {
          if(drupalTranslation) {
            $('body').hide();
            sfgovGtranslate('en|en');
            window.location.href = drupalTranslation.turl;
          }
        }
      }
    }
    // displayTranslationNotice();
  }

  function displayTranslationNotice() {
    setTimeout(function() {
      var currentDrupalLanguage = drupalSettings.sfgov_translations.node.current_language;
      var gTranslateCookie = getCookie('googtrans');
      var drupalTranslation = getDrupalTranslation(currentDrupalLanguage);
      var translationNoticeStr = '';
      if(currentDrupalLanguage == 'en' && !gTranslateCookie) {
        translationNoticeStr = '';
      }
      else if(currentDrupalLanguage != 'en' && drupalTranslation) {
        translationNoticeStr = 'This page was human translated';
      } else if(currentDrupalLanguage == 'en' && gTranslateCookie) {
        if(gTranslateCookie.split('/')[2] !== 'en') {
          translationNoticeStr = 'This page was machine translated';
        }
      }
      else {
        translationNoticeStr = 'This page was machine translated';
      }
      if(translationNoticeStr.length > 0) {
        $('.sfgov-alpha-banner').after('<div>' + translationNoticeStr + '</div>');
      }
    },500);
  }

  function sfgovGtranslate(languageValue) {
    doGTranslate(languageValue);
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