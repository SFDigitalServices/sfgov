(function($) {
  var translations = drupalSettings.translations;
  // var gTranslateSelect = $('#block-gtranslate .gtranslate select');
  // console.log(gTranslateSelect);
  // $(gTranslateSelect).change(function() {
  //   alert('what');
  // })

  var gTranslateCallback = null;

  var observeElement = $('.head-right--container')[0];
  var config = { attributes: true, childList: true, subtree: true };
  var callback = function(mutationsList, observer) {
    var elem = null;
    for(var mutation of mutationsList) {
      if (mutation.type == 'childList') {
          // console.log('A child node has been added or removed.');
          // console.log(mutation);
          if(mutation.target.id == ':0.targetLanguage') {
            elem = $('#block-gtranslate .gtranslate > select');
            break;
          }
      }
      // else if (mutation.type == 'attributes') {
      //     console.log('The ' + mutation.attributeName + ' attribute was modified.');
      // }
    }
    // var changeCallback = $(elem)[0] ? $(elem)[0].onchange : null;
    // if(changeCallback) {
    //   console.log(elem);
    //   $(elem)[0].onchange = null;
    //   // changeCallback(elem[0]);
    //   // var f = function(e) {
    //   //   console.log(e);
    //   // };
    //   $(elem)[0].setAttribute('onchange', f);
      
    // }
    $(elem).change(function() {
      checkTranslations($(this).val());
    });
  }

  function checkTranslations(selectedTranslation) {
    var drupalTranslations = drupalSettings.translations;
    var selectedTranslation = selectedTranslation.split('|');
    if(selectedTranslation.length > 0) {
      var theTranslation = selectedTranslation[1];

      if(theTranslation == 'en' && drupalSettings.currentTranslation !== 'en') {
        window.location.href = drupalTranslations[0].url;
      } else {
        for(var i=0; i<drupalTranslations.length; i++) {
          console.log('selected:' + theTranslation + ', drupal:' + drupalTranslations[i].lang);
          if(drupalTranslations[i].lang == theTranslation && theTranslation !== 'en') {
            console.log('drupal translation found');
            console.log('drupal: ' + drupalTranslations[i].lang);
            console.log('selected: ' + theTranslation);
            console.log(drupalTranslations[i].url);
            $('body').html('');
            window.location.href = drupalTranslations[i].url;
            break;
          }
        }
      }
    }
  }

  var observer = new MutationObserver(callback);
  observer.observe(observeElement, config);

})(jQuery);