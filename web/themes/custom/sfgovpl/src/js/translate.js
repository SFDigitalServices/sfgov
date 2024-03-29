function SFGovTranslate () {
  const that = this
  const $ = jQuery

  this.currentSelectedTranslation = null
  this.sfgovGTranslateFireEvent = function (a, b) {
    try {
      if (document.createEvent) {
        const c = document.createEvent('HTMLEvents')
        c.initEvent(b, true, true)
        a.dispatchEvent(c)
      }
      else {
        const c = document.createEventObject()
        a.fireEvent('on' + b, c)
      }
    }
    catch (e) {
    }
  }

  this.sfgovDoGTranslate = function (a) {
    $('body').hide()
    const deferred = jQuery.Deferred()
    if (!a) {
      return
    }
    const b = a.split('|')[1]
    let c

    const d = document.getElementsByTagName('select')
    for (let i = 0; i < d.length; i++) {
      if (d[i].className === 'goog-te-combo') {
        c = d[i]
      }
    }
    if (!document.getElementById('google_translate_element2') || !document.getElementById('google_translate_element2').innerHTML.length || !c.length || !c.innerHTML.length) {
      setTimeout(() => {
        that.sfgovDoGTranslate(a)
      // eslint-disable-next-line no-magic-numbers
      }, 500)
    }
    else {
      c.value = b
      that.sfgovGTranslateFireEvent(c, 'change')
      that.sfgovGTranslateFireEvent(c, 'change')
      $('body').show()
      that.addElementTranslationClass(b)
      deferred.resolve()
    }
    return deferred.promise()
  }

  this.addElementTranslationClass = function (translationVal) {
    const elementClass = 'sfgov-translate-lang-' + translationVal
    $('body').find('*').not('script, noscript, link, style, iframe, .goog-te-combo')
      .addClass(elementClass)
  }

  this.sfgovGTranslate = function (event) {
    event.preventDefault()
    const args = event.target.getAttribute('data-sfgov-translate')
    const lang = args.split('|')[1]
    const drupalTranslation = that.getDrupalTranslation(lang)

    $('body')
      .find('*')
      .not('script, noscript, link, style, iframe, .goog-te-combo')
      .removeClass((i, classNames) => {
        return classNames
          .split(' ')
          .filter(klass => klass.includes('sfgov-translate-lang-'))
          .join(' ')
      })

    if (drupalTranslation) {
      // drupal translation always wins
      // set gtranslate to english to kill the gtranslate cookie
      if (drupalTranslation.turl !== window.location.pathname) {
        $.when(that.sfgovDoGTranslate('en|en')).then(() => {
          window.location.href = drupalTranslation.turl
        })
      }
    }
    else { // no drupal translation exists, use gtranslate
      // TODO: Is this ever called? AFAICT drupalTranslation.turl always exists.
      that.sfgovDoGTranslate(args)
      that.currentSelectedTranslation = args
    }
  }

  this.getDrupalTranslation = function (lang) {
    const drupalTranslations = drupalSettings.sfgov_translations.page.translations
    if (drupalTranslations) {
      for (let i = 0; i < drupalTranslations.length; i++) {
        const someTranslation = drupalTranslations[i]
        if (someTranslation.lang === lang) {
          return someTranslation
        }
      }
    }
    return null
  }

  this.setDrupalTranslationUrls = function () {
    const currentDrupalLang = drupalSettings.sfgov_translations.page.current_language
    const drupalTranslations = drupalSettings.sfgov_translations.page.translations

    if (drupalTranslations) {
      for (let i = 0; i < drupalTranslations.length; i++) {
        const translation = drupalTranslations[i]
        $('.gtranslate-link[data-sfgov-translate$="|' + translation.lang + '"]')
          .attr('href', translation.turl)
          .attr('data-sfgov-translator', translation.status === true ? 'drupal' : 'gtranslate')

        if (translation.lang === currentDrupalLang) {
          $('.gtranslate-link[data-sfgov-translate$="|' + currentDrupalLang + '"]').addClass('is-active cursor-default font-medium hocus:no-underline')
        }
      }
    }
  }

  this.checkCurrentLanguage = function () {
    const currentDrupalLanguage = drupalSettings.sfgov_translations.page.current_language
    const gTranslateCookie = getCookie('googtrans')
    // eslint-disable-next-line no-magic-numbers
    const gTranslateLang = gTranslateCookie ? gTranslateCookie.split('/')[2] : null
    let drupalTranslation

    // If translation cookie lang is different from Drupal current language,
    // remove the cookie, as we are using language path prefixes and should
    // always show the same language as the URL.
    if (currentDrupalLanguage !== 'en' || currentDrupalLanguage !== gTranslateLang) {
      const drupalTranslation = that.getDrupalTranslation(currentDrupalLanguage)
      // Always translate page, even if a Drupal translation for the page exists.
      // If translated content is being shown on the page, it should be wrapped
      // in a container with class="notranslate" to allow other elements like
      // header and footer to be translated.
      if (drupalTranslation && drupalTranslation.status) {
        $('main[role="main"]').addClass('notranslate').attr('translate', 'no')
        $('title').attr('translate', 'no')
      }
      that.sfgovDoGTranslate('en|' + currentDrupalLanguage)
      that.addElementTranslationClass(currentDrupalLanguage)
      return
    }

    if (gTranslateLang && gTranslateLang !== 'en') { // gtranslate cookie exists, a page was gtranslated somewhere
      that.addElementTranslationClass(gTranslateLang)
      drupalTranslation = that.getDrupalTranslation(gTranslateLang)
      if (drupalTranslation && drupalTranslation.turl !== window.location.pathname) { // drupal translation exists
        $.when(that.sfgovDoGTranslate('en|en')).then(() => { // kill the cookie and redirect to the drupal translation
          window.location.href = drupalTranslation.turl
        })
      }
    }
  }
}

function getCookie (cookieName) {
  let cookiesArr = []
  const cookies = {}
  if (document.cookie) {
    cookiesArr = document.cookie.split(';')
    for (let i = 0; i < cookiesArr.length; i++) {
      const keyValPair = cookiesArr[i].split('=')
      cookies[keyValPair[0].replace(/\s/g, '')] = keyValPair[1]
    }
  }
  return cookies[cookieName]
}

(function ($) {
  // watch for dom mutations
  const t = new SFGovTranslate()
  const observeElement = $('.sfgov-top-container')[0]
  const config = { attributes: true, childList: true, subtree: true }

  const gtranslateLanguageMap = {
    tl: 'fil'
  }

  // Add href, determine active link.
  t.setDrupalTranslationUrls()

  const callback = function (mutationsList, observer) {
    let elem = null
    for (let i = 0; i < mutationsList.length; i++) {
      const mutation = mutationsList[i]
      if (mutation.type === 'childList') {
        if (mutation.target.id === ':0.targetLanguage') {
          // catch the gtranslate dropdown
          elem = $('.gtranslate-link')
          break
        }
      }
      if (mutation.type === 'attributes') {
        const mutationTarget = mutation.target
        if (mutationTarget.tagName === 'HTML') {
          const lang = mutationTarget.getAttribute('lang')
          if (gtranslateLanguageMap[lang]) {
            mutationTarget.setAttribute('lang', gtranslateLanguageMap[lang])
          }
        }
      }
    }
    if (elem) {
      // Attach click event.
      $(elem).on('click', t.sfgovGTranslate)

      // this dropdown list that gets added doesn't always have the english option
      // if a language option doesn't exist in this drop down, the first option is always
      // selected by default, which is problematic
      // so, always add the english option
      setTimeout(() => {
        $('.goog-te-combo').append('<option value="en">English</option>')
        t.checkCurrentLanguage() // check the current language of the page AFTER english has been added
      // eslint-disable-next-line no-magic-numbers
      }, 1000)
    }
  }

  if (observeElement) {
    const observer = new MutationObserver(callback)
    observer.observe(observeElement, config)
    observer.observe(document.querySelector('html'), config)
  }
})(jQuery)
